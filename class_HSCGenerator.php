<?
include('./class_AtasciiGen.php');    // general AtasciGen class - required

class HSCGenerator extends AtasciiGen {
	//
	// path constants

	const USER_CONFIG_PATH="./users_configs/";
	const DEFAULT_CONFIG_PATH="./default_configs/";
	const DEFAULT_CONFIG_FILE="defaults";
	const CONFIG_FILE_EXTENTION=".json";
	const CONFIG_LAYOUTS_DEFAULT="default";

	private $gameID=null;
	private $layoutID=null;

	public $scoreboard=[];

	public function __construct($gameID = null, $layoutID = self::CONFIG_LAYOUTS_DEFAULT) {
		if ($gameID===null) throw new Exception("GameID must be defined!");

		$this->gameID=$gameID;
		$this->layoutID=$layoutID;

		// choice configure file to load (between dedicated file and default)
		$configFile=
			self::USER_CONFIG_PATH.
			$this->gameID.
			self::CONFIG_FILE_EXTENTION;
		// check game config file is exist
		if ( !file_exists($configFile) ) {
			// if not, check default config file is exist
			$configFile=
				self::DEFAULT_CONFIG_PATH.
				self::DEFAULT_CONFIG_FILE.
				self::CONFIG_FILE_EXTENTION;
			if ( !file_exists($configFile) ) {
				// if default file is not exist, throw exception
				throw new Exception("Default config file not exist!");
			}
//			$this->layoutID=self::CONFIG_LAYOUTS_DEFAULT;
		}

		$this->fetchScoreboardFromDB();

		parent::__construct($configFile);
	}

	function fetchScoreboardFromDB() {
		// here is place for fetchin scoreboard data from database
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
	}

	function getScoreboardEntry($place) {
		// This is where the scoreboard data is retrieved :)
		return [
			"place"=>$place,
			"nick"=>$this->scoreboard[$place-1]["nick"],
			"score"=>$this->scoreboard[$place-1]["score"]
		];
	}

	function generate() {
		if ($this->layoutID!==null) {
			if ( isset($this->layoutData[$this->layoutID]) ) {
				$this->layoutData=&$this->layoutData[$this->layoutID];
			} else {
				throw new Exception("The requested definition is not present.");
			}
		}

		return parent::generate();
	}
}
?>