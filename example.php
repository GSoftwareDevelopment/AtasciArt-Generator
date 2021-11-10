<?

include('./class_HSCAtasciGen.php');

// expand class
class HSCGenerator extends AtasciGen {
	public $scoreboard=array(
		["date"=>0, "nick"=>"PeBe", "score"=>12345],
		["date"=>0, "nick"=>"", "score"=>""],
		["date"=>0, "nick"=>"", "score"=>""],
		["date"=>0, "nick"=>"", "score"=>""],
		["date"=>0, "nick"=>"", "score"=>""],
		["date"=>0, "nick"=>"", "score"=>""],
		["date"=>0, "nick"=>"", "score"=>""]
	);

	function getScoreboardEntry($place) {
		// This is where the scoreboard data is retrieved :)
		return [
			"place"=>$place,
			"nick"=>$this->scoreboard[$place-1]["nick"],
			"score"=>$this->scoreboard[$place-1]["score"]
		];
	}
}
//
// example
//

$gen=new HSCGenerator('screen_kret__.json');
echo $gen->generate();
$gen->makeXEX('out.xex',0xbc40);

?>