<?
// defaults for image generator
const DEFAULT_FONT_FILE="./atari_16.png";
const DEFAULT_CHAR_WIDTH=16;
const DEFAULT_CHAR_HEIGHT=16;

include('./class_AtasciiFont.php');

//
// Run
//

try {
	$txt=new AtasciiFont('handwrite.json');
	$txt->makeImage('Hello Atarians','test.png');
} catch (Exception $th) {
	echo "Error: ".$th->getMessage();
}

?>