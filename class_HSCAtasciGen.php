<?
class AtasciGen {
	public $confFN='';
	private $screenDef='';
	private $isScreenDefined=false;
	private $config;
	protected $currentLineData;
	private $elParams;
	private $schemes;

	function __construct($fn) {
		return $this->loadConfig($fn);
	}

	private function loadConfig($fn) {
		$this->confFN="";
		$configFile=@file_get_contents($fn);
		if ( $configFile===false ) throw new Exception("Can't open config file");
		$this->config=json_decode($configFile,true);
		if (json_last_error()!=0) throw new Exception(json_last_error_msg()." in config file");
		$this->confFN=$fn;

		// Checking the required configuration parameters
		// Screen data or file is required
		if (@$this->config['screenData']) {
			$this->screenDef=$this->hexString2Data($this->config['screenData']);
			$this->isScreenDefined=(strlen($this->screenDef)>0);

		} else if (@$this->config['screenFile']) {
			// Reading screen content from a file
			if (!file_exists($this->config['screenFile'])) {
				throw new Exception("Can't find screen file");
			}
			$this->screenDef=file_get_contents($this->config['screenFile']);
			$this->isScreenDefined=($this->screenDef!==false && strlen($this->screenDef)>0);
		}

		if (!$this->isScreenDefined) throw new Exception("No screen file or data definition");

		// Score list definition is required
		if (@!$this->config['layout']) throw new Exception("No score list definition");
		if (@$this->config['schemes']) $this->schemes=$this->config['schemes'];
	}

	function getScoreboardEntry($place) {
		return [];
	}

	public function generate() {
		foreach ($this->config['layout'] as $lineIndex => $lineDef) {
			$currentSchema=[];

			// build schema
			if ( @isset($lineDef['useSchema']) ) {
				$schemaName=$lineDef['useSchema'];
				if ( @isset($this->schemes[$schemaName]) ) {
					$currentSchema=$this->schemes[$schemaName];
				} else {
					throw new Exception("Schema '".$schemaName."' is not defined!");
				}
			}
			$currentSchema=$lineDef+$currentSchema;

			// Checking required parameters
			if ( !isset($currentSchema['x']) || // No column defined
					 !isset($currentSchema['y']) || // No row defined
					 !isset($currentSchema['width']) )
				throw new Exception("Parameters 'x', 'y' and 'width' are required in object definition");

			// array destructuring (in PHP version >=7.0)
			// ['x'=>$lineX,'y'=>$lineY,'width'=>$lineWidth]=$currentSchema;

			// classic thinking (<7.0)
			$lineX=$currentSchema['x'];
			$lineY=$currentSchema['y'];
			$lineWidth=$currentSchema['width'];

			$place=$lineIndex+1;

			$this->currentLineData=str_pad('',$lineWidth,!isset($currentSchema['fillChar'])?' ':$currentSchema['fillChar']);

			// parse elements
			foreach ($currentSchema as $elType => $this->elParams) {
				$this->parseElement($elType,$this->getScoreboardEntry($place));
			}

			// general parameters
			if (@$currentSchema['inversLine']) { $this->strInvert($this->currentLineData); }

			// global parameters
			// Conversion of entry lines into ANTIC codes (if specified in the configuration)
			switch ($this->config['encodeAs']) {
				case 'antic': $this->strASCII2ANTIC($this->currentLineData); break;
			}

			// Paste the finished score line into the screen definition.
			$screenOffset=$lineX+$lineY*$this->config['width'];
			$this->putStr($this->currentLineData,$this->screenDef,$screenOffset);
		}

		return $this->screenDef;
	}

	protected function parseElement($elType,$scoreEntry) {
		switch ($elType) {
			case "place": $this->createElement($scoreEntry['place']); break;
			case "nick": $this->createElement($scoreEntry['nick']); break;
			case "score": $this->createElement($this->parseScore($scoreEntry['score'])); break;
			case "date": $this->createElement($this->parseDate($scoreEntry['date'])); break;
		}
	}

	private function createElement($val) {
		// Create a string based on definition parameters
		$str=$this->makeStr($val);

		// clip string
		$str=substr($str,0,$this->elParams['width']);

		// Paste the created string into a string representing the defined line.
		$this->putStr($str,$this->currentLineData,$this->elParams['shift']);
	}
//
//
//

	private function parseScore($val) {
		if ( is_int($val) ) {
			if (isset($this->elParams['showScoreAs'])) {
				switch ($this->elParams['showScoreAs']) {
					case 'time':
						if ( isset($this->elParams['precision']) ) {
							$precision=$this->elParams['precision'];
						} else {
							$precision=1;
						}
						$seconds=intdiv($val,$precision);
						$fraction=(($val % $precision)/$precision)*100;
						if ( isset($this->elParams['timeFormat']) )
							$format=trim($this->elParams['timeFormat']);
						else {
							$format="m:s";
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
			if ( isset($this->elParams['dateFormat']) ) {
				return date($this->elParams['dateFormat'],$date);
			} else {
				return date('Y.m.d',$date);
			}
		} else {
			return "";
		}
	}
//
//
//

	static function hexString2Data($hexData) {
		$data='';
		$dstOffset=0;
		foreach ( $hexData as $lineIndex => $dataLine ) {
			$srcOffset=0; $srcLen=strlen($dataLine);
			if ( $srcLen!=0 ) {
				$dataLine=strtoupper($dataLine);
				while ($srcOffset<$srcLen) {
					$chHi=$dataLine[$srcOffset]; $srcOffset++;

					if ( strpos('0123456789ABCDEF',$chHi)===false ) continue;

					if ($srcOffset<$srcLen) {
						$chLo=$dataLine[$srcOffset]; $srcOffset++;
					} else $chLo='';

					if ( strpos('0123456789ABCDEF',$chLo)===false ) {
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

	public function makeXEX($fn,$start) {
		$f=fopen($fn,'w');
	//	$start=0xbc40;
		$startLo=$start & 255;
		$startHi=$start >> 8;
		$size=$this->config['width']*$this->config['height'];
		$end=$start+$size;
		$endLo=$end & 255;
		$endHi=$end >> 8;
		fwrite($f,chr(255).chr(255).chr($startLo).chr($startHi).chr($endLo).chr($endHi));
		fwrite($f,$this->generate());
		fclose($f);
	}

	static function strANTIC2ASCII(&$src) {
		for ($i=0;$i<strlen($src);$i++) {
			$ch=ord($src[$i]);
			if ($ch>=0 and $ch<=63)
				$ch+=32;
			else if ($ch>=64 and $ch<=95)
				$ch-=64;
			$src[$i]=chr($ch);
		}
	}

	static function strASCII2ANTIC(&$src) {
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
		switch (@$this->elParams['align']) {
			case 'left': $align=STR_PAD_RIGHT; break;
			case 'center': $align=STR_PAD_BOTH; break;
			default:
				$align=STR_PAD_LEFT;
		}

		if ( @($this->elParams['uppercase']) ) { $value=strtoupper($value); }
		if ( @($this->elParams['lowercase']) ) { $value=strtolower($value); }

		if ( @($this->elParams['limitChars']) ) {
			$value=$this->limitChars($value,$this->elParams['limitChars'],
				isset($this->elParams['replaceOutsideChars'])?$this->elParams['replaceOutsideChars']:' ');
		}

		if ( @($this->elParams['invert']) ) { $this->strInvert($value); }

		return str_pad($value,$this->elParams['width'],!isset($this->elParams['fillChar'])?' ':$this->elParams['fillChar'],$align);
	}

	static function limitChars($value,$limitChars,$replaceChar) {
		for ($i=0;$i<strlen($value);$i++) {
			$ch=$value[$i];
			if ( strpos($limitChars,$ch)===false ) {
				$value[$i]=$replaceChar[0];
			}
		}
		return $value;
	}

	static function strInvert(&$line) {
		for ($i=0;$i<strlen($line);$i++) {
			$ch=ord($line[$i]);
			$line[$i]=chr($ch ^ 128);
		}
	}

	static function formatTime($format,$seconds,$fraction) {
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