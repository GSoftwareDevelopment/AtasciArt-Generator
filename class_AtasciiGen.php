<?
include('./_constants.php');
require_once('./_polyfill.php');
require_once('./_string_helpers.php');
require_once('./class_AtasciiFont.php');

class AtasciiGen {
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
			$this->screenDef=hexString2Data($this->layoutData[CONFIG_LAYOUTS_SCREENDATA]);
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

	private function rangeCheck($value,$min,$max,$errMsg) {
		if ($value<$min || $value>$max)
			throw new Exception($errMsg."! Acceptable value is between {$min} and {$max})");
		return $value;
	}

	private function checkExist($value,$default=null,$errMsg="Some attribut is not specified") {
		if ( is_null($value) ) {
			if ( $default!==null ) {
				return $default;
			} else {
				throw new Exception($errMsg);
			}
		} else {
			return $value;
		}
	}
	public function generate() {
		// check required parameters for layout
		if ( !isset($this->layoutData[ATTR_WIDTH]) || // No layout width
				 !isset($this->layoutData[ATTR_HEIGHT]) ) // No layout height
 			throw new Exception("The definition of a layout MUST HAVE `".ATTR_WIDTH."` and `".ATTR_HEIGHT."` parameters specified.");
		// get optional screen data or screen fill character
		$this->getScreenDataFromLayout();

		foreach ($this->layoutData[CONFIG_LAYOUTS_LINES] as $lineIndex => $lineDef) {
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

			// Checking required parameters for element
//			if ( !isset($currentSchema[ATTR_X]) ) throw new Exception("Element '".ATTR_X."', is not defined");
//			if ( !isset($currentSchema[ATTR_Y]) ) throw new Exception("Element '".ATTR_Y."', is not defined");
//			if ( !isset($currentSchema[ATTR_WIDTH]) ) throw new Exception("Element '".ATTR_WIDTH."', is not defined");

			$lineX=$this->rangeCheck(
				$this->checkExist($currentSchema[ATTR_X],null,"Element {ATTR_X} is not defined"),
				0,39,'Line column is out of range.');
			$lineY=$this->rangeCheck(
				$this->checkExist($currentSchema[ATTR_Y],0),
				0,23,'Line row is out of range.');
			$lineWidth=$this->rangeCheck(
				$this->checkExist($currentSchema[ATTR_WIDTH],40),
				1,40,'Line width is out of range.');
			$lineHeight=$this->rangeCheck(
				$this->checkExist(@$currentSchema[ATTR_HEIGHT],1),
				0,23,'Line height is out of range.');

			$place=$lineIndex+1;

			$ch=!isset($currentSchema[ATTR_FILLCHAR])?' ':$currentSchema[ATTR_FILLCHAR];
			$this->currentLineData=str_repeat($ch,$lineWidth*$lineHeight);

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
			if (@$currentSchema[ATTR_INVERS]) { strInvert($this->currentLineData); }

			// global parameters
			// Conversion of entry lines into ANTIC codes (if specified in the configuration)
			switch ($this->layoutData[CONFIG_LAYOUTS_ENCODEELEMENTAS]) {
				case 'antic': strASCII2ANTIC($this->currentLineData); break;
				default:
			}

			// Paste the finished score line into the screen definition.
			$screenOffset=$lineX+$lineY*$this->layoutData[ATTR_WIDTH];
			for ($dataLine=0;$dataLine<$lineHeight;$dataLine++) {
				$lineOffset=$dataLine*$lineWidth;
				$lineData=substr($this->currentLineData,$lineOffset,$lineWidth);
				putStr($lineData,$this->screenDef,$screenOffset);
			}
		}

		return $this->screenDef;
	}

//
//
//

	private function createElement($val) {
		$offsetX=$this->rangeCheck(
			$this->checkExist(@$this->elParams[ATTR_XOFFSET],0),
			0,39,'Element column offset is out of range.');
		$offsetY=$this->rangeCheck(
			$this->checkExist(@$this->elParams[ATTR_YOFFSET],0),
			0,23,'Element row offset is out of range.');
		$elWidth=$this->rangeCheck(
			$this->checkExist(@$this->elParams[ATTR_WIDTH],strlen($val)),
			0,39,'Element width is out of range.');

	// Create a string based on definition parameters
		switch (@$this->elParams[ATTR_ALIGN]) {
			case 'left': $align=STR_PAD_RIGHT; break;
			case 'center': $align=STR_PAD_BOTH; break;
			default:
				$align=STR_PAD_LEFT;
		}

		switch(@$this->elParams[ATTR_LETTERCASE]) {
			case "uppercase": $val=strtoupper($val); break;
			case "lowercase": $val=strtolower($val); break;
			default:
		}

		if ( @($this->elParams[ATTR_LIMITCHAR]) ) {
			$val=limitChars($val,$this->elParams[ATTR_LIMITCHAR],
				isset($this->elParams[ATTR_REPLACEOUTSIDECHAR])?$this->elParams[ATTR_REPLACEOUTSIDECHAR]:' ');
		}

		if ( @($this->elParams[ATTR_USEATASCIFONT]) ) {
			$ch=!isset($this->elParams[ATTR_FILLCHAR])?' ':$this->elParams[ATTR_FILLCHAR];
			$val=str_pad($val,$this->elParams[ATTR_WIDTH],$ch,$align);

			$fontName=$this->elParams[ATTR_USEATASCIFONT];
			$AFnt=new AtasciiFont($fontName);
			$textLines=$AFnt->makeText($val);

			for ($line=0;$line<count($textLines);$line++) {
				$lineLen=strlen($textLines[$line]);
				$ch=" ";
				$val=str_pad($textLines[$line],$elWidth,$ch,$align);
				$outLineOfs=$offsetX+($elWidth*($offsetY+$line));
				putStr($val,$this->currentLineData,$outLineOfs);
			}
		} else {
			if ( @($this->elParams[ATTR_INVERS]) ) { strInvert($val); }

			$ch=!isset($this->elParams[ATTR_FILLCHAR])?' ':$this->elParams[ATTR_FILLCHAR];
			$val=str_pad($val,$elWidth,$ch,$align);

			// clip string to width length
			$val=substr($val,0,$elWidth);

			// Paste the created string into a string representing the defined line.
			$outOffset=$offsetX+$offsetY*$elWidth;
			putStr($val,$this->currentLineData,$outOffset);
		}
	}

	protected function parseElement($elType,$scoreEntry,$label=null) {
		switch ($elType) {
			case ELEMENT_PLACE: $this->createElement($scoreEntry['place']); break;
			case ELEMENT_NICK: $this->createElement($scoreEntry['nick']); break;
			case ELEMENT_SCORE: $this->createElement($this->parseScore($scoreEntry['score'])); break;
			case ELEMENT_DATE: $this->createElement($this->parseDate($scoreEntry['date'])); break;
			case ELEMENT_TEXT: $this->createElement($this->parseText()); break;
			case ELEMENT_GENTIME:	$this->createElement($this->parseGenerationTime()); break;
		}
	}

	private function parseGenerationTime() {
		if ( @($this->elParams[ATTR_FORMAT]) ) {
			$format=$this->elParams[ATTR_FORMAT];
		} else {
			$format=DEFAULT_GENTIME_FORMAT;
		}
		return date($format);
	}

	private function parseText() {
		if ( @($this->elParams[ATTR_CONTENT]) ) {
			return $this->elParams[ATTR_CONTENT];
		} else {
			return "";
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
						if ( isset($this->elParams[ATTR_FORMAT]) )
							$format=trim($this->elParams[ATTR_FORMAT]);
						else {
							$format=DEFAULT_TIMEFORMAT;
						}
						return formatTime($format,$seconds,$fraction);
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
			if ( isset($this->elParams[ATTR_FORMAT]) ) {
				return date($this->elParams[ATTR_FORMAT],$date);
			} else {
				return date(DEFAULT_DATEFORMAT,$date);
			}
		} else {
			return "";
		}
	}

	public function makeImage($imageFile=null, $fontFile=DEFAULT_FONT_FILE,
	                          $defaultCharWidth=DEFAULT_CHAR_WIDTH,$defaultCharHeight=DEFAULT_CHAR_HEIGHT) {
		$fnt=@imagecreatefrompng($fontFile);
		if ( $fnt===false ) die('Cannot load Atascii Fontset image');
		$width=$this->layoutData['width'];
		$height=$this->layoutData['height'];
		$img=@imagecreate(($width*$defaultCharWidth),($height*$defaultCharHeight))
			or die("Cannot Initialize new GD image stream");
		for ($y=0;$y<$height;$y++) {
			$offset=$y*$width;
			for ($x=0;$x<$width;$x++) {
				$ch=ord($this->screenDef[$offset+$x]);
				$chx=$ch & 0x1f;
				$chy=$ch >> 5;
				imagecopy($img,$fnt,
				          $x*$defaultCharWidth,$y*$defaultCharHeight,
				          $chx*$defaultCharWidth,$chy*$defaultCharHeight,
				          $defaultCharWidth,$defaultCharHeight);
			}
		}
		if ($imageFile!==null) {
			imagepng($img,$imageFile);
		} else {
			imagepng($img);
		}
		imagedestroy($img);
		imagedestroy($fnt);
	}
}
?>