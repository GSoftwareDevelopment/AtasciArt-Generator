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
	$txt=new AtasciiFont('cosmic-line');
//	$txt->makeImage('Hello Atarians','test.png');
	$txt->makeImage('ABCabc123!#$','test.png');
} catch (Exception $th) {
	echo "Error: ".$th->getMessage();
}

?>