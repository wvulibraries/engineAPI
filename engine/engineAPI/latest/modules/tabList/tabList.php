<?php
/**
 * EngineAPI tabList module
 * @package EngineAPI\modules\tabList
 */
class tabList {
	/**
	 * @var EngineAPI
	 */
	private $engine = NULL;

	/**
	 * @todo This doesn't look used
	 * @var null
	 */
	private $table = NULL;

	/**
	 * Class constructor
	 *
	 * @see $this->table
	 * @param $table
	 */
	function __construct($table) {

		$this->table  = $table;
		$this->engine = EngineAPI::singleton();
		
	}

	/**
	 * Builds an HTML tab list (think breadcrumbs)
	 * @param array $range
	 * @param string $current
	 * @return string
	 */
	public function buildList($range,$current) {
		
		if (isset($this->engine->cleanGet['HTML']['currentTabItem'])) {
			unset($this->engine->cleanGet['HTML']['currentTabItem']);
		}		
		$queryString = array();
		if (isset($this->engine->cleanGet['HTML'])) {
			foreach ($this->engine->cleanGet['HTML'] as $I=>$V) {
				$queryString[] = "$I=$V";
			}
			$queryString = implode("&amp;",$queryString);
		}
		else {
			$queryString = "";
		}
		
		$output = "<ul class=\"tabList\">";
		$count = 0;
		foreach ($range as $item) {
			
			$classStrArr = array();
			if (strtoupper($current) == strtoupper($item)) {
				$classStrArr[] = "currentTabItem";
			}
			if ($count === 0) {
				$classStrArr[] = "firstTabItem";
			}
			if (++$count == count($range)) {
				$classStrArr[] = "lastTabItem";
			}
			
			$classStr = ' class="'.(implode(" ",$classStrArr)).'"';
			
			
			$output .= "<li";
			$output .= ($classStr != 'class=""')?$classStr:"";
			$output .= '><a href="'.$_SERVER['PHP_SELF'].'?'.$queryString.'&amp;currentTabItem='.$item.'">'.(($item == "@")?"#":$item).'</a></li>';
		}
		$output .= "</ul>";
		
		return($output);
	}

}