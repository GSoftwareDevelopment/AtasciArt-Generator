<?
include('./class-AtasciiFont.php');

//
// Run
//

try {
	$txt=new AtasciiFont('./AtasciiFonts/handwrite.json');
	$txt->makeImage('Hello Atarians','test.png');
} catch (Exception $th) {
	echo "Error: ".$th->getMessage();
}

?>