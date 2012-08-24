<?php

class breadCrumbs {

	private $engine  = NULL;
	public $pattern  = "/\{breadCrumbs\s+(.+?)\}/";
	public $function = "breadCrumbs::templateMatches";

	function __construct() {
		$this->engine = EngineAPI::singleton();
		$this->engine->defTempPattern($this->pattern,$this->function,$this);
	}

	public static function templateMatches($matches) {
		$engine   = EngineAPI::singleton();

		$obj      = $engine->retTempObj("breadCrumbs");

		$attPairs = attPairs($matches[1]);

		return($obj->breadCrumbs($attPairs));

	}

	public function breadCrumbs($attPairs) {

		$engine   = EngineAPI::singleton();

		global $engineVars;

		$callingFunction        = array("breadCrumbs","breadCrumbs");
		$tempParams             = array();
		$tempParams['attPairs'] = $attPairs;
		$trail                  = $engine->execFunctionExtension($callingFunction,$tempParams,"before");

		if ($trail) {
			return($trail);
		}

		/* setup initial variables */
		$str2upper = FALSE;
		if (isset($attPairs['titlecase'])) {
			if (strtoupper($attPairs['titlecase']) == "TRUE") {
				$str2upper = TRUE;
			}
		}

		$ellipse = (isset($engineVars['breadCrumbsEllipse']))?$engineVars['breadCrumbsEllipse']:" &#133; ";
		$ellipse = (isset($attPairs['ellipse']))?$attPairs['ellipse']:$ellipse;

		$spacer = (isset($engineVars['breadCrumbsSpacer']))?$engineVars['breadCrumbsSpacer']:">>";
		$spacer = (isset($attPairs['spacer']))?$attPairs['spacer']:$spacer;

		$type   = (isset($attPairs['type']))?$attPairs['type']:"hierarchical";

		$displayNum = (isset($engineVars['breadCrumbsDisplayNum']))?$engineVars['breadCrumbsDisplayNum']:0;
		$displayNum = (isset($attPairs['displayNum']))?$attPairs['displayNum']:$displayNum;

		$start  = 0;
		$prefix = 1;

		if ($type != "hierarchical" && $type != "actual") {
			return(webHelper_errorMsg("Breadcrumbs: type == '$type' not supported."));
		}

		$trailArray = array();

		if ($type == "hierarchical") {
			$url = explode("/",$_SERVER["SCRIPT_NAME"]);
			$urlCount = count($url);
			unset($url[--$urlCount]);

			if (isset($attPairs['displayNum'])) {
				if ($attPairs['displayNum'] < $urlCount) {
					$start = $urlCount - $attPairs['displayNum'];
				}
				if (isset($attPairs['prefixNum'])) {
					$prefix = $attPairs['prefixNum'];
				}
			}

			$path = $engineVars['documentRoot'];
			$href = $engineVars['WVULSERVER'];
			for ($I = 0;$I < $urlCount;$I++) {

				$path .= "/$url[$I]";
				$href .= "$url[$I]/";

				//Handles empty first case
				if (empty($url[$I])) {
					continue;
				}

				if ($start != 0) {
					if ($I == $prefix+1) {
						$trailArray[] = "$ellipse";
					}
					if ($I > $prefix && $I <= (($prefix > 1)?$start+($prefix-1):$start)) {
						continue;
					}
				}

				if (file_exists($path ."/.breadCrumbs")) {
					$lines = file($path ."/.breadCrumbs");
					$trailArray[] = '<a href="'.$href.'" class="breadCrumbLink">'.(($str2upper === TRUE)?str2TitleCase($lines[0]):$lines[0]).'</a>';
				}
				else {
					$trailArray[] = '<a href="'.$href.'" class="breadCrumbLink">'.(($str2upper === TRUE)?str2TitleCase($url[$I]):$url[$I]).'</a>';
				}
			}

		}
		if ($type == "actual") {
			return(webHelper_errorMsg("type == actual not coded yet"));	
		}

		$trail = implode(" <span class=\"breadCrumbSpacer\">$spacer</span> ",$trailArray);



		return($trail);

	}
}
?>