<?
include('_constants.php');
require_once('_polyfill.php');

class AtasciGen {
	public $confFN='';
	private $screenDef='';
	private $config;
	protected $currentLineData;
	private $elParams;
	private $schemes;

	public function __construct($fn) {
		$this->confFN="";
		$configFile=@file_get_contents($fn);
		if ( $configFile===false ) throw new Exception("Can't open config file");
		$this->config=json_decode($configFile,true);
		if (json_last_error()!=0) throw new Exception(json_last_error_msg()." in config file");
		$this->confFN=$fn;

		// Checking the required configuration parameters
		// Layouts definition is required
		if (@!$this->config[CONFIG_LAYOUTS]) throw new Exception("No layouts definition");
		$this->layoutData=&$this->config[CONFIG_LAYOUTS];

		// Optional: Check schemes definition
		if (@$this->config[CONFIG_ELEMENTSCHAMES]) $this->schemes=&$this->config[CONFIG_ELEMENTSCHAMES];
	}

	function getScoreboardEntry($place) {
		throw new Exception('Expand `AtasciGen` class and inherit method `getScoreboardEntry()`. Return associative array[place,date,nick,score]');
	}

	private function getScreenDataFromLayout() {
		if ( isset($this->layoutData[CONFIG_LAYOUTS_SCREENDATA]) ) {
			$this->screenDef=$this->hexString2Data($this->layoutData[CONFIG_LAYOUTS_SCREENDATA]);
			$isScreenDefined=(strlen($this->screenDef)>0);
		} else {
			$isScreenDefined=false;
		}

		if (!$isScreenDefined) {
			if ( isset($this->layoutData[CONFIG_SCREENFILL]) ) {
				$ch=$this->layoutData[CONFIG_SCREENFILL];
			}	else {
			  $ch=chr(0);
			}
			$len=$this->layoutData[ATTR_WIDTH]*$this->layoutData[ATTR_HEIGHT];
			$this->screenDef=str_pad("",$len,$ch);
		}
	}

	public function generate() {
		// check required parameters for layout
		if ( !isset($this->layoutData[ATTR_WIDTH]) || // No layout width
				 !isset($this->layoutData[ATTR_HEIGHT]) ) // No layout height
 			throw new Exception("The definition of a layout MUST HAVE `".ATTR_WIDTH."` and `".ATTR_HEIGHT."` parameters specified.");
		// get optional screen data or screen fill character
		$this->getScreenDataFromLayout();

		foreach ($this->layoutData[CONFIG_LAYOUTS_ELEMENTS] as $lineIndex => $lineDef) {
			$currentSchema=[];

			// build schema
			if ( @isset($lineDef[ATTR_USESCHEMA]) ) {
				$schemaName=$lineDef[ATTR_USESCHEMA];
				if ( @isset($this->schemes[$schemaName]) ) {
					$currentSchema=$this->schemes[$schemaName];
				} else {
					throw new Exception("Schema '".$schemaName."' is not defined!");
				}
			}
			$currentSchema=$lineDef+$currentSchema;

			// Checking required parameters
			if ( !isset($currentSchema[ATTR_X]) || // No column defined
					 !isset($currentSchema[ATTR_Y]) || // No row defined
					 !isset($currentSchema[ATTR_WIDTH]) )
				throw new Exception("Parameters '".ATTR_X."', '".ATTR_Y."' and '".ATTR_WIDTH."' are required in element definition");

			$lineX=$currentSchema[ATTR_X];
			$lineY=$currentSchema[ATTR_Y];
			$lineWidth=$currentSchema[ATTR_WIDTH];

			$place=$lineIndex+1;

			$this->currentLineData=str_pad('',$lineWidth,!isset($currentSchema[ATTR_FILLCHAR])?' ':$currentSchema[ATTR_FILLCHAR]);

			// parse elements
			foreach ($currentSchema as $elType => $this->elParams) {
				$label=null;
				$labelPos=strpos($elType,LABEL_SEPARATOR);
				if ($labelPos!==false) {
					$elType=substr($elType,0,$labelPos-1);
					$label=substr($elType,$labelPos+1);
				}
				$this->parseElement($elType,$this->getScoreboardEntry($place),$label);
			}

			// general parameters
			if (@$currentSchema[ATTR_INVERS]) { $this->strInvert($this->currentLineData); }

			// global parameters
			// Conversion of entry lines into ANTIC codes (if specified in the configuration)
			switch ($this->layoutData[CONFIG_LAYOUTS_ENCODEELEMENTAS]) {
				case 'antic': $this->strASCII2ANTIC($this->currentLineData); break;
				default:
			}

			// Paste the finished score line into the screen definition.
			$screenOffset=$lineX+$lineY*$this->layoutData[ATTR_WIDTH];
			$this->putStr($this->currentLineData,$this->screenDef,$screenOffset);
		}

		return $this->screenDef;
	}

//
//
//

	private function createElement($val) {
		// Create a string based on definition parameters
		$str=$this->makeStr($val);

		// clip string to width length
		$str=substr($str,0,$this->elParams[ATTR_WIDTH]);

		// Paste the created string into a string representing the defined line.
		$this->putStr($str,$this->currentLineData,$this->elParams[ATTR_XOFFSET]);
	}

	protected function parseElement($elType,$scoreEntry,$label=null) {
		switch ($elType) {
			case ELEMENT_PLACE: $this->createElement($scoreEntry['place']); break;
			case ELEMENT_NICK: $this->createElement($scoreEntry['nick']); break;
			case ELEMENT_SCORE: $this->createElement($this->parseScore($scoreEntry['score'])); break;
			case ELEMENT_DATE: $this->createElement($this->parseDate($scoreEntry['date'])); break;
		}
	}

	private function parseScore($val) {
		if ( is_int($val) ) {
			if (isset($this->elParams[ATTR_SHOWSCOREAS])) {
				switch ($this->elParams[ATTR_SHOWSCOREAS]) {
					case 'time':
						if ( isset($this->elParams[ATTR_PRECISION]) ) {
							$precision=$this->elParams[ATTR_PRECISION];
						} else {
							$precision=1;
						}
						$seconds=intdiv($val,$precision);
						$fraction=(($val % $precision)/$precision)*100;
						if ( isset($this->elParams[ATTR_TIMEFORMAT]) )
							$format=trim($this->elParams[ATTR_TIMEFORMAT]);
						else {
							$format=DEFAULT_TIMEFORMAT;
						}
						return $this->formatTime($format,$seconds,$fraction);
					break;
				}
			} else {
				return $val;
			}
		} else {
			return "";
		}
	}

	private function parseDate($date) {
		if ( is_int($date) ) {
			if ( isset($this->elParams[ATTR_DATEFORMAT]) ) {
				return date($this->elParams[ATTR_DATEFORMAT],$date);
			} else {
				return date(DEFAULT_DATEFORMAT,$date);
			}
		} else {
			return "";
		}
	}

//
//
//

	private function hexString2Data($hexData) {
		$data='';
		$dstOffset=0;
		foreach ( $hexData as $lineIndex => $dataLine ) {
			$srcOffset=0; $srcLen=strlen($dataLine);
			if ( $srcLen!=0 ) {
				$dataLine=strtoupper($dataLine);
				while ($srcOffset<$srcLen) {
					$chHi=$dataLine[$srcOffset]; $srcOffset++;

					if ( strpos(HEX_CHARS,$chHi)===false ) continue;

					if ($srcOffset<$srcLen) {
						$chLo=$dataLine[$srcOffset]; $srcOffset++;
					} else $chLo='';

					if ( strpos(HEX_CHARS,$chLo)===false ) {
						$val=hexdec($chHi);
					} else {
						$val=hexdec($chHi.$chLo);
					}

					$data.=chr($val); $dstOffset++;
				}
			}
		}
		return $data;
	}

	private function strANTIC2ASCII(&$src) {
		for ($i=0;$i<strlen($src);$i++) {
			$ch=ord($src[$i]);
			if ($ch>=0 and $ch<=63)
				$ch+=32;
			else if ($ch>=64 and $ch<=95)
				$ch-=64;
			$src[$i]=chr($ch);
		}
	}

	private function strASCII2ANTIC(&$src) {
		for ($i=0;$i<strlen($src);$i++) {
			$ch=ord($src[$i]);
			$inv=$ch & 0x80;
			$ch=$ch & 0x7f;
			if ($ch>=0 and $ch<=31)
				$ch+=64;
			else if ($ch>=32 and $ch<=95)
				$ch-=32;

			$src[$i]=chr($ch | $inv);
		}
	}

	private function putStr($src,&$dst,$index) {
		// clip string if longer than lengtho od $dst
		if (strlen($src)>strlen($dst)) {
			$len=strlen($dst);
		} else {
			$len=strlen($src);
		}

		for ($i=0;$i<$len;$i++) {
			$dst[$index]=$src[$i]; $index++;
		}
	}

	private function makeStr($value) {
		switch (@$this->elParams[ATTR_ALIGN]) {
			case 'left': $align=STR_PAD_RIGHT; break;
			case 'center': $align=STR_PAD_BOTH; break;
			default:
				$align=STR_PAD_LEFT;
		}

		switch(@$this->elParams[ATTR_LETTERCASE]) {
			case "uppercase": break;
			case "lowercase": break;
			default:
		}

		if ( @($this->elParams[ATTR_LIMITCHAR]) ) {
			$value=$this->limitChars($value,$this->elParams[ATTR_LIMITCHAR],
				isset($this->elParams[ATTR_REPLACEOUTSIDECHAR])?$this->elParams[ATTR_REPLACEOUTSIDECHAR]:' ');
		}

		if ( @($this->elParams[ATTR_INVERS]) ) { $this->strInvert($value); }

		return str_pad($value,$this->elParams[ATTR_WIDTH],!isset($this->elParams[ATTR_FILLCHAR])?' ':$this->elParams[ATTR_FILLCHAR],$align);
	}

	private function limitChars($value,$limitChars,$replaceChar) {
		for ($i=0;$i<strlen($value);$i++) {
			$ch=$value[$i];
			if ( strpos($limitChars,$ch)===false ) {
				$value[$i]=$replaceChar[0];
			}
		}
		return $value;
	}

	private function strInvert(&$line) {
		for ($i=0;$i<strlen($line);$i++) {
			$ch=ord($line[$i]);
			$line[$i]=chr($ch ^ 128);
		}
	}

	private function formatTime($format,$seconds,$fraction) {
		$formatIndex=0; $formatLen=strlen($format);
		$out="";
		while ($formatIndex<$formatLen) {
			$ch=$format[$formatIndex]; $formatIndex++;
			switch ($ch) {
				case "H":
					if ($formatIndex<$formatLen) {
						$leadzeros=$format[$formatIndex]; $formatIndex++;
						if ( strpos('123456789',$leadzeros)!==false )
							$out.=substr(str_pad(intdiv($seconds,3600),$leadzeros,'0',STR_PAD_LEFT),0,$leadzeros);
						else
							$formatIndex--;
					}
				break;
				case "h": $out.=intdiv($seconds,3600); break;
				case "m":
					$min=intdiv($seconds % 3600,60);
					if ($min<10) $out.='0';
					$out.=$min;
					break;
				case "s":
					$sec=$seconds % 60;
					if ($sec<10) $out.='0';
					$out.=$sec;
					break;
				case "f":
					$out.=substr($fraction,0,strlen($fraction)-strpos($fraction,'.'));
					break;
				case "F":
					if ($formatIndex<$formatLen) {
						$leadzeros=$format[$formatIndex]; $formatIndex++;
						if ( strpos('12',$leadzeros)!==false )
							$out.=substr(str_pad(substr($fraction,0,strlen($fraction)-strpos($fraction,'.')),$leadzeros,'0',STR_PAD_LEFT),0,$leadzeros);
						else
							$formatIndex--;
					}
				break;
				default:
					$out.=$ch;
			}
		}
		return $out;
	}

}
?>