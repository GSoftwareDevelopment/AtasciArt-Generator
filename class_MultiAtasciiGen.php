<?
require_once('./class_AtasciiGen.php');    // general AtasciGen class - required

class MAGException extends AGException {}

class MultiAtasciiGen extends AtasciiGen {
	//
	// path constants

	const USER_CONFIG_PATH="./users_configs/";
	const DEFAULT_CONFIG_PATH="./default_configs/";
	const DEFAULT_CONFIG_FILE="defaults";
	const CONFIG_FILE_EXTENTION=".json";
	const CONFIG_LAYOUTS_DEFAULT="default";
	const CONFIG_LAYOUTS="layouts";

	private $gameID=null;
	private $layoutID=null;

	public $singleLayout;
	public $subLayouts=[];
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
		$this->singleLayout=true;

		try {
			parent::__construct($configFile);
		} catch (AGException $th) {
			if ($th->getMessage()!=="No layout defined")
				throw new AGException($th->getMessage());
			$this->singleLayout=false;
		}

		if (@!$this->config[self::CONFIG_LAYOUTS]) {
			if ( !$this->singleLayout ) {
				throw new MAGException("Layout(s) not defined");
			}
		} else {
//			$this->layoutData=&$this->config[self::CONFIG_LAYOUTS];

			foreach ($this->config[self::CONFIG_LAYOUTS] as $subId => $layout) {
				if ( isset($layout['unlisted']) ) continue;
				$this->subLayouts[]=$subId;
			}
		}
	}

	function fetchScoreboardFromDB() {
		throw new MAGException('Expand `MultiAtasciGen` class and inherit method `fetchScoreboardFromDB()`.');
	}

	function generate() {
		if ( $this->layoutID!==null ) {
			if ( isset($this->config[self::CONFIG_LAYOUTS][$this->layoutID]) ) {
				$this->layoutData=&$this->config[self::CONFIG_LAYOUTS][$this->layoutID];
			} else {
				throw new MAGException("Sub layout is not present.");
			}
		} else {
			if ( isset($this->config[CONFIG_LAYOUT]) ) {
				$this->layoutData=&$this->config[CONFIG_LAYOUT];
			} else {
				throw new MAGException("Layout section is not dafined.");
			}
		}

		return parent::generate();
	}

	//
	//
	//

	public function getLayoutColorsData() {
		$this->layoutData=&$this->config[self::CONFIG_LAYOUTS][$this->layoutID];
		$out="";
		foreach ($this->colorReg as $colId => $colVal) {
			$out.=chr($colVal);
		}
		return $out;
	}

	public function getLayoutInfoData() {
		$this->layoutData=&$this->config[self::CONFIG_LAYOUTS][$this->layoutID];
		$out="";
		$graphMode=0;
		$encode=0; // 0 - antic; 1 - atasci

		$out.=chr($graphMode);
		$out.=chr($encode);
		$out.=chr($this->layoutData['width']);
		$out.=chr($this->layoutData['height']);
		$out.=$this->getLayoutColorsData();
		$out.=leftStr(( !isset($this->params['title'] ) )?" ":$this->params['title'],40);
		$out.=leftStr(( !isset($this->params['mode'] ) )?" ":$this->params['mode'],40);
		$out.=leftStr(( !isset($this->config['author'] ) )?" ":$this->config['author'],40);

		return $out;
	}

	function getLayoutsList($includeAuthor=true) {
		$list=[];
		if ( isset($this->config[self::CONFIG_LAYOUTS]) ) {
			$generalAuthor=(isset($this->config['author']))?$this->config['author']:'';
			foreach ($this->config[self::CONFIG_LAYOUTS] as $subId => $layout) {
				if ( isset($layout['unlisted']) ) continue;
				$layoutName=(isset($layout['name']))?$layout['name']:$subId;
				$layoutAuthor=(isset($layout['author']))?$layout['author']:$generalAuthor;
				$list[$subId]=$layoutName.($includeAuthor?' ('.$layoutAuthor.')':'');
			}

		}
		return json_encode($list);
	}
}
?>