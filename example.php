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
	$gen=new HSCGenerator(109);

// for specified layout (name 'game') of dedicated config file
//	$gen=new HSCGenerator(109,'game');

// like above, but specified layout has name '1'
//	$gen=new HSCGenerator(109,'1');

// generate screen - its needed for make image process
	$gen->generate();

// lets generate PNG image
	$gen->makeImage('test.png');
} catch (Exception $th) {
	echo "Error: ".$th->getMessage();
}

?>