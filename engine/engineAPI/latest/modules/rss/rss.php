<?php
/**
 * EngineAPI RSS module
 * @package EngineAPI\modules\rss
 */
class rss {
	/**
	 * Template file to use
	 * @var string
	 */
	private $templateFile;
	/**
	 * Final, built, template
	 * @var array
	 */
	private $template = array();
	/**
	 * @var array
	 */
	private $rssItems = array();
	/**
	 * Output
	 * @todo This dosen't look used
	 * @var string
	 */
	private $output;
	/**
	 * RSS feed title
	 * @var string
	 */
	public $title;
	/**
	 * RSS feed link
	 * @var string
	 */
	public $link;
	/**
	 * RSS feed description
	 * @var string
	 */
	public $description;
	/**
	 * Last build date
	 * @var string
	 */
	public $lastBuildDate;
	/**
	 * RSS feed language
	 * @var string
	 */
	public $language = "en-us";

	/**
	 * Class constructor
	 *
	 * @param string $type
	 *        Type of RSS (Right now only rss2.0 is supported)
	 */
	function __construct($type = "rss2.0") {
		global $engineVars;
		
		switch($type) {
			case "rss2.0":
			    $this->templateFile = $engineVars['rss2.0'];
			    break;
			default:
			    $this->templateFile = $engineVars['rss2.0'];
		}
		
		$this->template = $this->builtTemplate($this->templateFile);
		
	}

	/**
	 * Add an item to the RSS feed
	 *
	 * @param $title
	 * @param $link
	 * @param $guid
	 * @param $pubDate
	 * @param $description
	 */
	public function addItem($title,$link,$guid,$pubDate,$description) {
		
		$tArray = array();
		$tArray['title']       = $title;
		$tArray['link']        = $link;
		$tArray['guid']        = $guid;
		$tArray['pubDate']     = $pubDate;
		$tArray['description'] = $description;
		
		$this->rssItems[] = $tArray;
		
	}

	/**
	 * Build the RSS feed
	 *
	 * @param bool $html
	 *        If true, passes the results through htmlentities()
	 * @return string
	 */
	public function buildRSS($html = FALSE) {
		
		$rss = "";
		
		foreach ($this->template as $k => $v) {
			if(is_array($v)) {
				foreach ($this->rssItems as $index => $item) {
		
					$vTemp = $v;
					foreach ($vTemp as $k2 => $v2) {
						$v2 = preg_replace('/{rss var="itemTitle"}/',$item['title'],$v2);	
						$v2 = preg_replace('/{rss var="itemLink"}/',$item['link'],$v2);
						$v2 = preg_replace('/{rss var="itemGuid"}/',$item['guid'],$v2);
						$v2 = preg_replace('/{rss var="itemPubdate"}/',$item['pubDate'],$v2);
						$v2 = preg_replace('/{rss var="itemDescription"}/',$item['description'],$v2);
						
						$rss .= $v2;
					}
				}
				
			}
			else {
				$v = preg_replace('/{rss var="title"}/',$this->title,$v);	
				$v = preg_replace('/{rss var="link"}/',$this->link,$v);	
				$v = preg_replace('/{rss var="description"}/',$this->description,$v);	
				$v = preg_replace('/{rss var="lastBuildDate"}/',$this->lastBuildDate,$v);	
				$v = preg_replace('/{rss var="language"}/',$this->language,$v);		
				
				$rss .= $v;	
			}
		}
		
		if ($html == TRUE) {
			$rss = htmlentities($rss);
		}
		
		return($rss);
		
	}

	/**
	 * Build RSS template
	 *
	 * @param string $file
	 * @return array
	 */
	private function builtTemplate($file) {
		$temp = file($file);
		
		$template = array();
		
		$baseCount = 0;
		$repeat = FALSE;
		for($I=0;$I<count($temp);$I++) {
			
			if(preg_match('/{rss repeat="start"}/',$temp[$I])) {
				$repeat = TRUE;
				continue;
			}
			else if (preg_match('/{rss repeat="end"}/',$temp[$I])) {
				$repeat = FALSE;
				$baseCount++;
				continue;
			}
			
			if ($repeat == FALSE) {
				$template[$baseCount++] = $temp[$I];
			}
			else {
				if (empty($template[$baseCount])) {
					$template[$baseCount] = array();
				}
				
				$template[$baseCount][$I] = $temp[$I];
			}
				
		}
				
		return($template);
		
	}
}

?>