<?
include('./_constants.php');
require_once('./_polyfill.php');
require_once('./_string_helpers.php');
require_once('./class_AtasciiFont.php');

class AGException extends Exception {}

class AtasciiGen {
	public $confFN='';
	private $screenDef='';
	protected $config;
	private $schemes;

	private $screenWidth,$screenHeight;
	private $curLineX,$curLineY,$curLineWidth,$curLineHeight;

	protected $currentLineData;
	private $elParams;

	public $params=[];
	public $usePalette=DEFAULT_PALETTE_FILE;
	public $palette=[];
	public $colorReg=["708"=>0,"709"=>15,"710"=>0,"711"=>0,"712"=>0];

	public function __construct($fn) {
		$this->confFN="";
		$configFile=@file_get_contents($fn);
		if ( $configFile===false ) throw new AGException("Can't open config file");
		$this->config=json_decode($configFile,true);
		if (json_last_error()!=0) throw new AGException(json_last_error_msg()." in config file");

		$this->confFN=$fn;

		// Optional: Check schemes definition
		if (@$this->config[CONFIG_ELEMENTSCHAMES]) $this->schemes=&$this->config[CONFIG_ELEMENTSCHAMES];
		$this->usePalette=$this->checkExist(@$this->config[ATTR_USEPALETTE],DEFAULT_PALETTE_FILE);

		// Checking the required configuration parameters
		// Layouts definition is required
		if (@!$this->config[CONFIG_LAYOUT]) throw new AGException("No layout defined");
		$this->layoutData=&$this->config[CONFIG_LAYOUT];
	}

	function getScoreboardEntry($place) {
		throw new AGException('Expand `AtasciGen` class and inherit method `getScoreboardEntry()`. Return associative array[place,date,nick,score]');
	}

	private function getParameter($val) {
		$paramPos=0;
		$paramPos=strpos($val,'%',$paramPos);
		if ($paramPos !== false) {
			$paramID=substr($val,$paramPos+1);
			if ( isset($this->params[$paramID]) ) {
				$val=$this->params[$paramID];
			} else {
				$val="%param%";
			}
		}
		return $val;
	}

	private function parseValue($val) {
		if ( is_int($val) ) return $val;
		if ( is_bool($val) ) return $val;
		if ( is_string($val) ) {
//			$val=trim($val);
			return $this->getParameter($val);
		}
		return null;
	}

	private function rangeCheck($value,$min,$max,$errMsg) {
		if ($value<$min || $value>$max)
			throw new AGException($errMsg."! Acceptable value is between {$min} and {$max})");
		return $value;
	}

	private function checkExist($value,$default=null,$errMsg="Some attribut is not specified") {
		if ( is_null($value) ) {
			if ( $default!==null ) {
				return $default;
			} else {
				throw new AGException($errMsg);
			}
		} else {
			return $value;
		}
	}

	//
	// parsers for...

	// ...attributers of layout definition

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
			$len=$this->screenWidth*$this->screenHeight;
			$this->screenDef=str_pad("",$len,$ch);
		}
	}

	protected function parseLayoutBefore(&$layoutData) {
		// check required parameters for layout
		if ( is_int($this->parseValue(@$layoutData[ATTR_WIDTH])) ) {
			$this->screenWidth=$this->rangeCheck(
				$this->checkExist($this->parseValue($this->layoutData[ATTR_WIDTH]),40),
				1,48,'Layout width is out of range.');
		} else {
			switch ( $this->parseValue($layoutData[ATTR_WIDTH]) ) {
				case 'narrow': $this->screenWidth=32; break;
				case 'normal': $this->screenWidth=40; break;
				case 'wide':   $this->screenWidth=48; break;
				default:
					throw new AGException("Screen width value not recognized");
			}
		}
		$this->usePalette=$this->checkExist(@$this->layoutData[ATTR_USEPALETTE],$this->usePalette);
		$this->screenHeight=$this->rangeCheck(
			$this->checkExist($this->parseValue($layoutData[ATTR_HEIGHT]),24),
			1,30,'Layout height is out of range.');

		// get optional screen data or screen fill character
		$this->getScreenDataFromLayout();
	}

	// ...attributes of line schema

	protected function buildLineSchema(&$lineDef) {
		$currentSchema=[];

		// build schema
		if ( @isset($lineDef[ATTR_USESCHEMA]) ) {
			$schemaName=$this->parseValue($lineDef[ATTR_USESCHEMA]);
			if ( @isset($this->schemes[$schemaName]) ) {
				$currentSchema=$this->schemes[$schemaName];
			} else {
				throw new AGException("Schema '".$schemaName."' is not defined!");
			}
		}
		return array_merge_recursive_distinct($currentSchema,$lineDef);
	}

	// ...attributes of line definition

	protected function parseLineBefore(&$currentSchema) {
		// Checking base parameters for element
		$this->curLineX=$this->rangeCheck(
			$this->checkExist($this->parseValue(@$currentSchema[ATTR_X]),null,"Element {ATTR_X} is not specified"),
			0,47,'Line column is out of range.');
		$this->curLineY=$this->rangeCheck(
			$this->checkExist($this->parseValue(@$currentSchema[ATTR_Y]),null,"Element {ATTR_Y} is not specified"),
			0,39,'Line row is out of range.');
		$this->curLineWidth=$this->rangeCheck(
			$this->checkExist($this->parseValue(@$currentSchema[ATTR_WIDTH]),$this->screenWidth-$this->curLineX),
			1,48,'Line width is out of range.');
		$this->curLineHeight=$this->rangeCheck(
			$this->checkExist($this->parseValue(@$currentSchema[ATTR_HEIGHT]),1),
			1,30,'Line height is out of range.');

		$ch=!isset($currentSchema[ATTR_FILLCHAR])?' ':$this->parseValue($currentSchema[ATTR_FILLCHAR]);
		$this->currentLineData=str_repeat($ch,$this->curLineWidth*$this->curLineHeight);

		if ( isset($currentSchema[ATTR_ISENTRY]) ) {
			$this->isEntry=$this->parseValue($currentSchema[ATTR_ISENTRY]);
		} else {
			$this->isEntry=true;
		}
		if ( $this->isEntry ) {
			if ( is_int($this->isEntry) ) {
				$this->curPlace=$this->isEntry;
			}
			$this->curEntry=$this->getScoreboardEntry($this->curPlace);
		}
	}

	protected function parseLineAfter(&$layoutData,&$currentSchema) {
		// general parameters
		if (@$this->parseValue($currentSchema[ATTR_INVERS])) { strInvert($this->currentLineData); }

		// global parameters
		// Conversion of entry lines into ANTIC codes (if specified in the configuration)
		switch ($this->parseValue($this->layoutData[CONFIG_LAYOUTS_ENCODEELEMENTAS])) {
			case 'antic': strASCII2ANTIC($this->currentLineData); break;
			default:
		}
	}

	//
	// layout generator

	public function generate() {
		$this->curPlace=1;
		$this->parseLayoutBefore($this->layoutData);

		foreach ($this->layoutData[CONFIG_LAYOUT_LINES] as $lineIndex => $lineDef) {
			$currentSchema=$this->buildLineSchema($lineDef);

			$this->parseLineBefore($currentSchema);

			// parse elements in current line definition
			foreach ($currentSchema as $elType => $this->elParams) {
				$label=null;
				$labelPos=strpos($elType,LABEL_SEPARATOR);
				if ($labelPos!==false) {
					$elType=substr($elType,0,$labelPos-1);
					$label=substr($elType,$labelPos+1);
				}
				$this->parseElement($elType,@$this->curEntry,$label);
			}

			$this->parseLineAfter($this->layoutData,$currentSchema);

			// Paste the finished score line into the screen definition.
			$screenOffset=$this->curLineX+$this->curLineY*$this->screenWidth;
			for ($dataLine=0;$dataLine<$this->curLineHeight;$dataLine++) {
				$lineOffset=$dataLine*$this->curLineWidth;
				$lineData=substr($this->currentLineData,$lineOffset,$this->curLineWidth);
				putStr($lineData,$this->screenDef,$screenOffset);
				$screenOffset+=$this->screenWidth;
			}

			if ( $this->isEntry ) $this->curPlace++;
		}

		return $this->screenDef;
	}

//
//
//

	private function createElement($val) {
		if ( @($this->elParams[ATTR_USEATASCIFONT]) ) {
			$fontName=$this->parseValue($this->elParams[ATTR_USEATASCIFONT]);
			$AFnt=new AtasciiFont($fontName);
			$useAtasciiFont=true;
		} else {
//			$valWidth=strlen($val); $valHeight=1;
			$useAtasciiFont=false;
		}

		$offsetX=$this->rangeCheck(
			$this->checkExist($this->parseValue(@$this->elParams[ATTR_XOFFSET]),0),
			0,$this->curLineWidth-1,'Element column offset is out of range');
		$offsetY=$this->rangeCheck(
			$this->checkExist($this->parseValue(@$this->elParams[ATTR_YOFFSET]),0),
			0,$this->curLineHeight-1,'Element row offset is out of range');
		$elWidth=$this->rangeCheck(
			$this->checkExist($this->parseValue(@$this->elParams[ATTR_WIDTH]),$this->curLineWidth),
			1,48,'Element width is out of range');
		$elHeight=$this->rangeCheck(
			$this->checkExist($this->parseValue(@$this->elParams[ATTR_HEIGHT]),$this->curLineHeight),
			1,30,'Element height is out of range');

	// Create a string based on definition parameters
		switch ($this->parseValue(@$this->elParams[ATTR_ALIGN])) {
			case 'left': $align=STR_PAD_RIGHT; break;
			case 'center': $align=STR_PAD_BOTH; break;
			default:
				$align=STR_PAD_LEFT;
		}

		switch($this->parseValue(@$this->elParams[ATTR_LETTERCASE])) {
			case "uppercase": $val=strtoupper($val); break;
			case "lowercase": $val=strtolower($val); break;
			default:
		}

		if ( @($this->elParams[ATTR_LIMITCHAR]) ) {
			$val=limitChars($val,$this->parseValue($this->elParams[ATTR_LIMITCHAR]),
				isset($this->elParams[ATTR_REPLACEOUTSIDECHAR])
					?$this->parseValue($this->elParams[ATTR_REPLACEOUTSIDECHAR])
					:' ');
		}

		$fillChar=!isset($this->elParams[ATTR_FILLCHAR])
			?' '
			:$this->parseValue($this->elParams[ATTR_FILLCHAR]);

		if ( $useAtasciiFont ) {
			$textLines=$AFnt->makeText($val,ENCODE_ATASCII);

		} else {
			unset($textLines);
			$textLines[]=$val;
		}

		for ($line=0;$line<count($textLines);$line++) {
			$ln=str_pad($textLines[$line],$elWidth,$fillChar,$align);
			$ln=substr($ln,0,$elWidth);
			if ( $this->parseValue(@($this->elParams[ATTR_INVERS])) ) { strInvert($ln); }
			$outOffset=$offsetX+($this->curLineWidth*($offsetY+$line));
			putStr($ln,$this->currentLineData,$outOffset);
		}

	}

	protected function parseElement($elType,$scoreEntry,$label=null) {
		switch ($elType) {
			case ELEMENT_PLACE: $this->createElement($scoreEntry['place']); break;
			case ELEMENT_NICK: $this->createElement($scoreEntry['nick']); break;
			case ELEMENT_SCORE: $this->createElement($scoreEntry['score']); break;
			case ELEMENT_DATE: $this->createElement($this->parseDate($scoreEntry['date'])); break;
			case ELEMENT_TEXT: $this->createElement($this->parseText()); break;
			case ELEMENT_GENTIME:	$this->createElement($this->parseGenerationTime()); break;
		}
	}

	//
	// content parsers

	private function parseGenerationTime() {
		if ( @($this->elParams[ATTR_FORMAT]) ) {
			$format=$this->parseValue($this->elParams[ATTR_FORMAT]);
		} else {
			$format=DEFAULT_GENTIME_FORMAT;
		}
		return date($format);
	}

	private function parseText() {
		if ( @($this->elParams[ATTR_CONTENT]) ) {
			$val=$this->parseValue($this->elParams[ATTR_CONTENT]);
			return $val;
		} else {
			return "";
		}
	}

	private function parseScore($val) {
		if (isset($this->elParams[ATTR_SHOWSCOREAS])) {
			$type=$this->parseValue($this->elParams[ATTR_SHOWSCOREAS]);
		} else {
			$type="score";
		}

		switch ( $type ) {
			case 'score': return $val; break;
			case 'timeBCD':
				$hours=(int) substr($val,0,2);
				$minutes=(int) substr($val,2,2);
				$seconds=(int) substr($val,4,2);
				$seconds=$seconds+($minutes*60)+($hours*3600);
				if ( isset($this->elParams[ATTR_FORMAT]) )
					$format=$this->parseValue($this->elParams[ATTR_FORMAT]);
				else {
					$format=DEFAULT_BCDTIMEFORMAT;
				}
				return formatTime($format,$seconds,1);
			break;
			case 'timeINT':
				if ( is_int($val) ) {
					if ( isset($this->elParams[ATTR_PRECISION]) ) {
						$precision=$this->parseValue($this->elParams[ATTR_PRECISION]);
					} else {
						$precision=1;
					}
					$seconds=intdiv($val,$precision);
					$fraction=(($val % $precision)/$precision)*100;
					if ( isset($this->elParams[ATTR_FORMAT]) )
						$format=$this->parseValue($this->elParams[ATTR_FORMAT]);
					else {
						$format=DEFAULT_INTTIMEFORMAT;
					}
					return formatTime($format,$seconds,$fraction);
				} else {
					return "#type#";
				}
			break;
		}
	}

	private function parseDate($date) {
		if ( is_int($date) ) {
			if ( isset($this->elParams[ATTR_FORMAT]) ) {
				return date($this->parseValue($this->elParams[ATTR_FORMAT]),$date);
			} else {
				return date(DEFAULT_DATEFORMAT,$date);
			}
		} else {
			return "";
		}
	}

	//
	//

	function loadFontPNG($fn,$charW,$charH) {
		$fnt=@imagecreatefrompng($fn);
		if ( $fnt===false ) die('Cannot load Atascii Fontset image');

		if ( !isset($this->layoutData['colors']) ) return $fnt;

		// before colorize, make table of used characters
		$usedChars=[];
		for ($scrOfs=0;$scrOfs<strlen($this->screenDef);$scrOfs++) {
			$ch=ord($this->screenDef[$scrOfs]);
			if ( !isset($usedChars[$ch]) ) {
				$usedChars[$ch]=true;
			}
		}

//		$w = imagesx($fnt);
//    $h = imagesy($fnt);

		$col709=$this->palette[$this->colorReg['709']];
		$col710=$this->palette[$this->colorReg['710']];

//		$index710 = imagecolorallocatealpha($fnt, $col710[0], $col710[1], $col710[2], 1);
//		$index709 = imagecolorallocatealpha($fnt, $col709[0], $col709[1], $col709[2], 1);

		$col[0] = imagecolorallocatealpha($fnt, $col710[0], $col710[1], $col710[2], 1);
		$col[1] = imagecolorallocatealpha($fnt, $col709[0], $col709[1], $col709[2], 1);

// colorize only used characters
		foreach ($usedChars as $char=>$set) {
			$xofs=($char & 0x1f)*$charW;
			$yofs=($char >> 5)*$charH;

			// Work through pixels
			for($y=$yofs;$y<$yofs+$charH;$y++) {
					for($x=$xofs;$x<$xofs+$charW;$x++) {
							imagesetpixel ($fnt, $x, $y, $col[imagecolorat($fnt, $x, $y)]);
		//            imagesetpixel ($fnt, $x, $y, (imagecolorat($fnt, $x, $y)===0)?$index710:$index709);
					}
			}
		}
		return $fnt;
	}

	public function makeImage($imageFile=null, $fontFile=DEFAULT_FONT_FILE,
	                          $charWidth=DEFAULT_CHAR_WIDTH,$charHeight=DEFAULT_CHAR_HEIGHT) {

		$this->setLayoutColors();
		$this->loadPalette(DEFAULT_PALETTE_PATH.$this->usePalette.'.act');
		$fnt=$this->LoadFontPNG($fontFile,$charWidth,$charHeight);
//		$this->remapColors($fnt);

		$width=$this->screenWidth;
		$height=$this->screenHeight;
		$img=@imagecreate(($width*$charWidth),($height*$charHeight))
			or die("Cannot Initialize new GD image stream");
		for ($y=0;$y<$height;$y++) {
			$offset=$y*$width;
			for ($x=0;$x<$width;$x++) {
				$ch=ord($this->screenDef[$offset+$x]);
				$chx=$ch & 0x1f;
				$chy=$ch >> 5;
				imagecopy($img,$fnt,
				          $x*$charWidth,$y*$charHeight,
				          $chx*$charWidth,$chy*$charHeight,
				          $charWidth,$charHeight);
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

	private function loadPalette($fn) {
		$palData=@file_get_contents($fn);
		if ( $palData===false ) throw new AGException("Can't open palette file");

		unset($this->palette); $this->palette=[];
		for ($nCol=0;$nCol<256;$nCol++) {
			$colOfs=$nCol*3;
			$rVal=ord($palData[$colOfs+0]);
			$gVal=ord($palData[$colOfs+1]);
			$bVal=ord($palData[$colOfs+2]);
			$this->palette[$nCol]=array($rVal,$gVal,$bVal);
		}
	}

	private function setLayoutColors() {
		if ( !isset($this->layoutData['colors']) ) {
			return false;
		}
		$reg=708;
		foreach ($this->layoutData['colors'] as $colId => $colVal) {
			if ($reg>712) break;
			$this->colorReg[$reg++]=$colVal;
		}
		return true;
	}

	public function getLayoutColorsData() {
		$out="";
		foreach ($this->colorReg as $colReg => $colVal) {
			$out.=chr($colVal);
		}
		return $out;
	}

	public function getLayoutInfoData() {
		$out="";
		$graphMode=0;
		$encode=0; // 0 - antic; 1 - atasci

		$out.=chr($graphMode);
		$out.=chr($encode);
		$out.=chr($this->layoutData['width']);
		$out.=chr($this->layoutData['height']);
		$out.=$this->getLayoutColorsData();
		$out.=leftStr(( !isset($this->params['title'] ) )?" ":$this->params['title'],40);
		$out.=leftStr(( !isset($this->params['mode'] ) )?" ":$this->params['mode'],40);
		$out.=leftStr(( !isset($this->config['author'] ) )?" ":$this->config['author'],40);

		return $out;
	}
}
?>