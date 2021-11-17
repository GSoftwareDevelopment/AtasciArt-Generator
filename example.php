<?

include('./class_HSCAtasciGen.php');

// extend class
class HSCGenerator extends AtasciGen {
	private $gameId;

	public $scoreboard=[];

	public function __construct($fn, $gameId = null) {
		if ($gameId===null) throw new Exception("GameID must be defined!");
		// przed pobraniem danych z bazy, sprawdź obecność $gameId
		$this->$gameId=$gameId;
		// tu pobierasz dane z bazy do zminnej #scoreboard
		$this->scoreboard=array(
		["date"=>0, "nick"=>"PeBe", "score"=>12345],
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
		// HINT: wywołanie nadrzędnej metody (z klasy AtasciGen) wczytania szablonu
		parent::__construct($fn);
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

//
// example
//

$gen=new HSCGenerator('screens/kret.json',109); // 109 is game_id
echo $gen->generate();
$gen->makeXEX('out.xex',0xbc40);

?>