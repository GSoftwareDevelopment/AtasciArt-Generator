<?
//
const DEFAULT_FONT_FILE="./atari_16.png";
const DEFAULT_CHAR_WIDTH=16;
const DEFAULT_CHAR_HEIGHT=16;

require_once('./_polyfill.php');
require_once('./_string_helpers.php');

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

	public function __construct($fontFile) {
		$cnt=@file_get_contents($fontFile);
		if ( $cnt===false ) throw new Exception("Can't open AtasciiFont file");
		$font=json_decode($cnt,true);
		if (json_last_error()!=0) throw new Exception(json_last_error_msg()." in AtasciiFont file");
		$this->fn=$fontFile;

		$this->width=$font['width'];
		$this->height=$font['height'];
		$this->letterSpace=$font['letterSpacing'];
		$this->lineSpace=$font['linesSpacing'];
		$this->spaceWidth=@$font['spaceWidth'] or $this->letterSpace;

		switch (@$font['horizontalPositioning']) {
			case self::HPOS_WHOLE: $this->HPos=true; break;
			case self::HPOS_HALF: $this->HPos=false; break;
			default:
				$this->HPos=true;
		}

		$this->charDef=&$font['charsDefinition'];
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

	public function makeText($str) {
		$outLines=[]; $textHeight=0;

		for ($strOfs=0;$strOfs<strlen($str);$strOfs++) {
			$ch=$str[$strOfs];
			if ($ch===chr(32)) {
				for ($line=0;$line<$textHeight;$line++) {
					@$outLines[$line].=str_repeat(chr(0),$this->spaceWidth);
				}
			} else {
				list($curCharWidth,$curCharHeight,$curCharDef)=$this->getCharData($ch,$strOfs);
				if ( $curCharDef===null ) continue;
				if ($curCharHeight>$textHeight) $textHeight=$curCharHeight;
				for ($line=0;$line<$textHeight;$line++) {
					if ($line<$curCharHeight) {
						@$outLines[$line].=substr($curCharDef,$line*$curCharWidth,$curCharWidth);
					} else {
						@$outLines[$line].=str_repeat(chr(0),$curCharHeight);
					}
					if ($this->letterSpace>0) {
						@$outLines[$line].=str_repeat(chr(0),$this->letterSpace);
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