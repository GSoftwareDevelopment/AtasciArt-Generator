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
	$gen=new HSCGenerator("1");

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

// lets generate PNG image
	$gen->makeImage('test.png');
} catch (Exception $th) {
	echo "Error: ".$th->getMessage();
}

?>