<?
const HEX_CHARS="0123456789ABCDEF";

function hexToStr($dataLine) {
	$data='';
	$dstOffset=0;
	$srcOffset=0;
	$srcLen=strlen($dataLine);
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
	return $data;
}

function hexString2Data($hexData) {
	$data='';
	$dstOffset=0;

	if ( is_string($hexData) ) {
		return hexToStr($hexData);
	} elseif ( is_array($hexData) ) {
		foreach ( $hexData as $lineIndex => $hexLine ) {
			$data.=hexToStr($hexLine);
		}
		return $data;
	} else {
		return null;
	}
}

function strANTIC2ASCII(&$src) {
	for ($i=0;$i<strlen($src);$i++) {
		$ch=ord($src[$i]);
		$inv=$ch & 0x80;
		$ch=$ch & 0x7f;
		if ($ch>=0 and $ch<=63)
			$ch+=32;
		else if ($ch>=64 and $ch<=95)
			$ch-=64;
		$src[$i]=chr($ch | $inv);
	}
}

function strASCII2ANTIC(&$src) {
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

function putStr($src,&$dst,$index) {
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

function limitChars($value,$limitChars,$replaceChar) {
	for ($i=0;$i<strlen($value);$i++) {
		$ch=$value[$i];
		if ( strpos($limitChars,$ch)===false ) {
			$value[$i]=$replaceChar[0];
		}
	}
	return $value;
}

function strInvert(&$line) {
	for ($i=0;$i<strlen($line);$i++) {
		$ch=ord($line[$i]);
		$line[$i]=chr($ch ^ 128);
	}
}

function formatTime($format,$seconds,$fraction) {
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

?>