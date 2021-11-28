<?
include('./class_HSCGenerator.php'); // class implementation - this is required to use

//
// Run
//

try {
// construcor call examples.
// for default layout of dedicated config file.
// If the game has no dedicated configuration file
// default configuration file is specified in HSCGenerator::DEFAULT_CONFIG_FILE
	$gen=new HSCGenerator("1","3");

// for specified layout (name 'game') of dedicated config file
//	$gen=new HSCGenerator(109,'game');

// like above, but specified layout has name '1'
//	$gen=new HSCGenerator(109,'1');

// add parameters
	$gen->params["title"]="Stunt Car Racer";
	$gen->params['mode']="The Little Ramp \x2d Lap Time"; // tis is wired
	$gen->params['showScoreAs']='score';
// The above parameters can be used in a text element.
// The parameter identifier must be provided in the 'content' attribute,
// preceded by a percent sign, e.g.
// content:"%1"
// content:"%name"

// generate screen - its needed for make image process
	$gen->generate();

// the following line, takes the color register settings (708-712) and
//  puts them in a text string (one byte/character=one register)
	$colorRegs=$gen->getLayoutColorsData();

// lets generate PNG image...
// First, load palette
	$gen->loadPalette('./palette/altirra.act');
// At the end, make PNG image
// by default, use 16x16 character set font
	$gen->makeImage('test.png');

// You can set, font file to use
// as the secound parameter, specifie font image to use (only PNG)
// next parameters, defines character size (width and height) in font image
// INFO: Font image file, it must have a layout of 32x8 characters,
// of which lines 5-8 must be in Invers mode.
// $gen->makeImage('test.png','./atari-8.png',8,8);

} catch (Exception $th) {
	echo "Error: ".$th->getMessage();
}

?>