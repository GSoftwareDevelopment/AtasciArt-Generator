<?
require_once('./_polyfill.php');
require_once('./_string_helpers.php');

const ATASCII_FONT_PATH="./AtasciiFonts/";
const ENCODE_ANTIC=false;
const ENCODE_ATASCII=true;

class AtasciiFont {
	const HPOS_WHOLE="wholeChar";
	const HPOS_HALF="halfChar";

	public $fn=null;
	public $name=null;
	private $charDef=null;

	public $width,$height; // Character dimentions
	public $letterSpace; // Space between letters
	public $lineSpace; // Space between lines
	public $HPos; // Horizontal positioning - true-WholeChar; false-halfChar
	public $dataEncode; // encode method for character data

	public function __construct($fontFile) {
		$cnt=@file_get_contents(ATASCII_FONT_PATH.$fontFile);
		if ( $cnt===false ) throw new Exception("Can't open AtasciiFont file");
		$font=json_decode($cnt,true);
		if (json_last_error()!=0) throw new Exception(json_last_error_msg()." in AtasciiFont file");
		$this->fn=$fontFile;

		$this->name=@$font['name'] or $fontFile;
		if ( !(isset($font['width'])) || !(isset($font['height'])) ) {
			throw new Exception('AtasciiFont file dimentions not speciied.');
		}
		$this->width=$font['width'];
		$this->height=$font['height'];
		$this->letterSpace=$font['letterSpacing'] or 1;
		$this->lineSpace=$font['linesSpacing'] or 1;
		$this->spaceWidth=@$font['spaceWidth'] or $this->letterSpace;

		switch (@$font['dataEncode']) {
			case 'antic': $this->dataEncode=ENCODE_ANTIC; break;
			case 'atascii': $this->dataEncode=ENCODE_ATASCII; break;
			default:
				$this->dataEncode=ENCODE_ANTIC;
		}

		if ( !isset($font['charsDefinition']) ) {
			throw new Exception('No character definition section found in AtasciiFont file');
		}
		$this->charDef=&$font['charsDefinition'];

		switch (@$font['horizontalPositioning']) {
			case self::HPOS_WHOLE: $this->HPos=true; break;
			case self::HPOS_HALF: $this->HPos=false; break;
			default:
				$this->HPos=true;
		}

		if ( !$this->HPos && isset($font['horizontalPositioning']) ) {
			if ( !(isset($font['charsDefinition']['even'])) ||
			     !(isset($font['charsDefinition']['odd'])) ) {
						 throw new Exception('AtasciiFont file has incorrect characters definition');
					 }
		}
	}

	public function getCharData($ch,$pos=0) {
			// get character definition
			if (!$this->HPos) { // for half positionig get...
				if ( ($pos % 2)===0 ) {
					$subFont='even'; // ...even definitions
				} else {
					$subFont='odd'; //...odd definitions
				}
				if ( isset($this->charDef[$subFont][$ch]) ) {
					$curFDef=$this->charDef[$subFont][$ch];
				} else {
					return null;
				}
			} else {
				if ( isset($this->charDef[$ch]) ) {
					$curFDef=$this->charDef[$ch];
				} else {
					return null;
				}
			}

		return [$curFDef['width'],$curFDef['height'],hexString2Data($curFDef['data'])];
	}

	public function makeText($str,$encode=null) {
		$outLines=[]; $textHeight=0;
		if ($encode!==null) {
			if ($encode) $spaceCh=chr(32); else $spaceCh=chr(0);
		} else {
			if ($this->dataEncode) $spaceCh=chr(32); else $spaceCh=chr(0);
		}
		$strLen=strLen($str);
		for ($strOfs=0;$strOfs<$strLen;$strOfs++) {
			$ch=$str[$strOfs];
			if ($ch===chr(32)) {
				for ($line=0;$line<$textHeight;$line++) {
					@$outLines[$line].=str_repeat($spaceCh,$this->spaceWidth);
				}
			} else {
				list($curCharWidth,$curCharHeight,$curCharDef)=$this->getCharData($ch,$strOfs);
				if ( $curCharDef===null ) continue;
				if ($curCharHeight>$textHeight) $textHeight=$curCharHeight;
				for ($line=0;$line<$textHeight;$line++) {
					if ($line<$curCharHeight) {
						$chLine=substr($curCharDef,$line*$curCharWidth,$curCharWidth);
						if ($encode!==false) {
							if ( $this->dataEncode===ENCODE_ANTIC && $encode===ENCODE_ATASCII ) {
								strANTIC2ASCII($chLine);
							} elseif ( $this->dataEncode===ENCODE_ATASCII && $encode===ENCODE_ANTIC ) {
								strASCII2ANTIC($chLine);
							}
						}
						@$outLines[$line].=$chLine;
					} else {
						@$outLines[$line].=str_repeat($spaceCh,$curCharHeight);
					}

					// letter spacing add
					if ( $strOfs+1<$strLen ) { // ...but only, if it is not last character in string
						if ($this->letterSpace>0) {
							@$outLines[$line].=str_repeat($spaceCh,$this->letterSpace);
						}
					}
				}
			}
		}
		return $outLines;
	}

	public function makeImage($str, $imageFile=null, $fontFile=DEFAULT_FONT_FILE,
	                          $defaultCharWidth=DEFAULT_CHAR_WIDTH,$defaultCharHeight=DEFAULT_CHAR_HEIGHT) {
		$Data=$this->makeText($str);
		$fnt=@imagecreatefrompng($fontFile);
		if ( $fnt===false ) die('Cannot load Atascii Fontset image');
		$width=strlen($Data[0]);
		$height=count($Data);
		$img=@imagecreate(($width*$defaultCharWidth),($height*$defaultCharHeight))
			or die("Cannot Initialize new GD image stream");
		for ($y=0;$y<$height;$y++) {
			$offset=$y*$width;
			for ($x=0;$x<$width;$x++) {
				$ch=ord($Data[$y][$x]);
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