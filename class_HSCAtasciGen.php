<?
class AtasciGen {
	public $confFN='';
	private $screenDef='';
	private $isScreenDefined=false;
	private $config;

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
			$this->hexString2Data($this->config['screenData'],$this->screenDef);
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
		if (@!$this->config['scoreList']) throw new Exception("No score list definition");
	}

	function getScoreboardEntry($place) {
		return [];
	}

	public function generate() {
		foreach ($this->config['scoreList'] as $lineIndex => $lineDef) {
			// Checking required parameters
			if ( !isset($lineDef['x']) || // No column defined
					 !isset($lineDef['y']) || // No row defined
					 !isset($lineDef['width']) )
				throw new Exception("Parameters 'x', 'y' and 'width' are required in line definition");

			$scoreLine=str_pad('',$lineDef['width'],!isset($lineDef['fillChar'])?' ':$lineDef['fillChar']);

			unset($elements);
			$elements=[]; // list of line definition elements

			// Checking for the occurrence of an element and adding it to the list of elements
			if (@$lineDef['place']) { $elements[]='place'; }
			if (@$lineDef['nick']) { $elements[]='nick'; }
			if (@$lineDef['score']) { $elements[]='score'; }
			if (@$lineDef['date']) { $elements[]='date'; }

			// Processing line definition elements
			if (count($elements)>0) {
				$place=$lineIndex+1;
				$scoreEntry=$this->getScoreboardEntry($place);

				// parse elements
				foreach ($elements as $elIndex => $element) {
					$elementDef=$lineDef[$element];

					switch ($element) {
						case "place": $val=$scoreEntry['place']; break;
						case "nick": $val=$scoreEntry['nick']; break;
						case "score": $val=$this->parseScore($scoreEntry['score'],$elementDef); break;
						case "date": $val=$this->parseDate($scoreEntry['date'],$elementDef); break;
					}

					// Create a string based on definition parameters
					$str=$this->makeStr($val,$elementDef);

					// clip string
					$str=substr($str,0,$elementDef['width']);

					// Paste the created string into a string representing the defined line.
					$this->putStr($str,$scoreLine,$elementDef['shift']);
				}

				// Optional parameter
				if (@$lineDef['inversLine']) { $this->strInvert($scoreLine); }

				// Conversion of entry lines into ANTIC codes (if specified in the configuration)
				switch ($this->config['encodeAs']) {
					case 'antic': $this->strASCII2ANTIC($scoreLine); break;
				}

				// Paste the finished score line into the screen definition.
				$this->putStr($scoreLine,$this->screenDef,$lineDef['x']+$lineDef['y']*$this->config['width']);
			} else {
				// empty line
			}

		}

		return $this->screenDef;
	}

//
//
//

	private function parseScore($val,$elementDef) {
		if ( is_int($val) ) {
			if (isset($elementDef['showScoreAs'])) {
				switch ($elementDef['showScoreAs']) {
					case 'time':
						if ( isset($elementDef['precision']) ) {
							$precision=$elementDef['precision'];
						} else {
							$precision=1;
						}
						$seconds=intdiv($val,$precision);
						$fraction=(($val % $precision)/$precision)*100;
						if ( isset($elementDef['timeFormat']) )
							$format=trim($elementDef['timeFormat']);
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

	private function parseDate($date,$elementDef) {
		if ( is_int($date) ) {
			if ( isset($elementDef['dateFormat']) ) {
				return date($elementDef['dateFormat'],$date);
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

	static function hexString2Data(array $hexData, string &$data) {
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

					$data[$dstOffset]=chr($val); $dstOffset++;
				}
			}
		}
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

	private function makeStr($value, array $def) {
		switch (@$def['align']) {
			case 'left': $align=STR_PAD_RIGHT; break;
			case 'center': $align=STR_PAD_BOTH; break;
			default:
				$align=STR_PAD_LEFT;
		}

		if ( @($def['uppercase']) ) { $value=strtoupper($value); }
		if ( @($def['lowercase']) ) { $value=strtolower($value); }

		if ( @($def['limitChars']) ) {
			$value=$this->limitChars($value,$def['limitChars'],
				isset($def['replaceOutsideChars'])?$def['replaceOutsideChars']:' ');
		}

		if ( @($def['invert']) ) { strInvert($value); }

		return str_pad($value,$def['width'],!isset($def['fillChar'])?' ':$def['fillChar'],$align);
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