<?php

class tabList {

	private $engine       = NULL;
	private $table        = NULL;

	function __construct($engine,$table) {
		
		if (!($engine instanceof EngineCMS)) {
			return(FALSE);
		}
		
		$this->table  = $table;
		$this->engine = $engine;
		
	}
	
	function __destruct() {
	}	
	
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