<?

include('./class_HSCAtasciGen.php');

// expand class
class HSCGenerator extends AtasciGen {
	private $gameId;

	public $scoreboard=[];

	public function loadConfig(string $fn, int $gameId = null) {
		// PL: można tak zrobić, gdyż zakres zmiennyh parametrów funkcji jest inny niz zakres zminnych klasy ($this!)
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
		// wywołanie nadrzędnej metody wczytania szablonu
		parent::loadConfig($fn);
	}

	function getScoreboardEntry($place) {
		// This is where the scoreboard data is retrieved :)
		// skoro pobrałes dane w loadConfig, można przenieść dane
		// dotyczące wyniky z tablicy wyników do tablicy asocjacyjnej.
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

$gen=new HSCGenerator();
$gen->loadConfig('screens/kret.json',109); // 109 is game_id
echo $gen->generate();
$gen->makeXEX('out.xex',0xbc40);

?>