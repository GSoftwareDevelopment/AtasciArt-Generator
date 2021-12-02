<?
require_once('./class_MultiAtasciiGen.php');    // general AtasciGen class - required

class ExampleGenerator extends MultiAtasciiGen {

	function fetchScoreboardFromDB() {
		// here is place for fetchin scoreboard data from database
		$this->scoreboard=array(
			["date"=>0, "nick"=>"PeBe", "score"=>"ABC"],
			["date"=>0, "nick"=>"", "score"=>""],
			["date"=>0, "nick"=>"", "score"=>""],
			["date"=>0, "nick"=>"", "score"=>""],
			["date"=>0, "nick"=>"", "score"=>""],
			["date"=>0, "nick"=>"", "score"=>""],
			["date"=>0, "nick"=>"", "score"=>""],
			["date"=>0, "nick"=>"", "score"=>""],
			["date"=>0, "nick"=>"", "score"=>""],
			["date"=>0, "nick"=>"", "score"=>""]
		);
	}

	function getScoreboardEntry($place) {
		// This is where the scoreboard data is retrieved :)
		return [
			"place"=>$place,
			"nick"=>$this->scoreboard[$place-1]["nick"],
			"score"=>$this->scoreboard[$place-1]["score"]
		];
	}
}
?>