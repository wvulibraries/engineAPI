<?php
/**
 * EngineAPI DLXS Module
 * @todo Add phpDoc blocks
 * @package EngineAPI\modules\dlxs
 */
class dlxs {
	
	public $viewType = NULL; // this needs to be set to private after display.php is cleaned up
	
	private $class    = NULL;	
	private $dlxsURL  = NULL;
	private $URL      = NULL;
	private $coll     = NULL;
	
	private $searchLinks     = array();
	private $findAidNavLinks = array();
	private $dlxsCGI         = array();
	private $findAidMenuRows = array(); // to build the left nav in finding aids
	private $findAidSubjects = array("corpname","persname","subject");
	
	private $cleanGet  = array();
	private $cleanPost = array();
	
	public $xml = NULL;
	
	// Settings 
	public $hideSortCaption      = FALSE;
	
	// Search Text
	public $textSearchFor        = "Search For ";
	public $textRecordsWithMedia = "only display records that have digital media (images, audio, etc.)";
	public $textSearchSubmit     = "search";
	public $textSearchClear      = "clear";
	
	// Results Text
	public $textCaptionSpacer    = ": ";
	public $textDetailRecordLink = "Display Full Record";
	
	// Pagebar Text              
	public $textPageBarNext      = "next >>";
	public $textPageBarPrev      = "<< previous";
	
	// finding aid text
	public $textFABookBagLink = "Add this to my bookbag";
	public $textFAFullTextLink = "full text";
	public $textFAStandardLink = "standard view";
	public $textFAKWIC         = "search terms in context";
	public $textFAElemNotFound = "Element Not Defined in XML";
	
	// Collection Information
	public $defaults = NULL;
	
	public $pageBar  = NULL;
	public $sliceBar = NULL;
	public $sortForm = NULL;
	
	public $backLink = NULL;
	
	public $noThumbURL = "/images/noThumbNail.gif";
	
	//Template Stuff
	private $pattern = "/\{dlxs\s+(.+?)\}/";
	private $function = "dlxs::templateMatches";
	
	function __construct($url,$class,$collection) {
		
		$engine = EngineAPI::singleton();
		
		$engine->defTempPattern($this->pattern,$this->function,$this);
		
		$this->cleanGet  = $engine->cleanGet;
		$this->cleanPost = $engine->cleanPost;
		
		if(isset($this->cleanGet['HTML']['type'])) {
			$this->viewType = $this->cleanGet['HTML']['type'];
		}
		
		$this->dlxsCGI['image']['main']      = "/cgi/i/image/image-idx";
		$this->dlxsCGI['image']['helper']    = "/cgi/i/image/getimage-idx";
		$this->dlxsCGI['findingaid']['main'] = "/cgi/f/findaid/findaid-idx";
		
		$this->dlxsURL = $url;
		$this->class   = $class;
		$this->coll    = $collection;
		$this->URL     = $url.$this->dlxsCGI[$this->class]['main'];
		
		$xml                           = $this->getDefaultXML();
		$this->defaults['sort']        = $this->getDefaultSort($xml);
		$this->defaults['title']       = $this->getDefaultTitle($xml);
		$this->defaults['contactLink'] = $this->getDefaultContactLink($xml);
		$this->defaults['contactText'] = $this->getDefaultContactText($xml);
		$this->defaults['group']       = $this->getDefaultGroup($xml);
		
	}
	
	public static function templateMatches($matches) {
		$engine   = EngineAPI::singleton();
		$dlxs      = $engine->retTempObj("dlxs");
		$attPairs = attPairs($matches[1]);
		
		$output = "Error: in dlxs.php";

		switch($attPairs['var']) {
			case "collTitle":
			    $output = $dlxs->defaults['title'];
				break;
			case "collGroup":
			    $output = $dlxs->defaults['group'];
				break;
			case "contactLink":
		    	$output = $dlxs->defaults['contactLink'];
			    break;
			case "contactText":
			    $output = $dlxs->defaults['contactText'];
			    break;
			case "pageBar":
			    $output = $dlxs->pageBar;
			    break;
			case "sliceBar":
			    $output = $dlxs->sliceBar;
			    break;
			case "findAidLeftNav":
			    $output = $dlxs->genFindAidLeftNav();
			    break;
			case "faElement":
			    $output = $dlxs->printFindAidElement($attPairs);
				break;
			case "faElementSubjects":
			    $output = $dlxs->printFindAidSubjects($attPairs['name']);
			    break;
			default:
			    $output = "Error: name function '".$attPairs['var']."' not found. Function: '$debug'";
		}

		return($output);
	}
	
	public function addSearchLink($abbr=null) {
		if(!isnull($abbr)) {
			
			$this->searchLinks[$abbr] = TRUE;
			
			return(TRUE);
		}
		return(FALSE);
	}
	
	public function buildSearchPage($submitURL=null,$get=FALSE) {
		
		$method = ($get === FALSE)?"post":"get";
		
		switch ($this->class) {
			case "image":
			    $url = $this->URL."?c=".$this->coll.";page=search;debug=xml";
				break;
			case "findingaid":
			    $url = $this->URL."?c=".$this->coll.";debug=xml";
				break;
			default: 
			    return(webHelper_errorMsg($this->class ."is not defined correctly."));
		}
		
		$xml = $this->getRemoteXML($url);
		
		if (isnull($submitURL)) {
			$submitURL = $_SERVER['PHP_SELF'];
		}
		

		//echo "<pre>";
		//print_r($xml->SearchForm);
		//echo "</pre>";
		
		
		if ($this->class == "image") {
		
			$output = '<form action="'.$submitURL.'" method="'.$method.'">';
		
			// This line is for EngineCMS's Built in security checks
			// If not using EngineCMS, remove this
			$output .= sessionInsertCSRF();
		
			$output .= '<input type="hidden" name="dlxsclass" value="image" />';
		
			$output .= '<input type="hidden" name="type" value="'.$xml->SearchForm->HiddenVars->Variable[0].'" />';
			$output .= '<input type="hidden" name="c" value="'.$xml->SearchForm->HiddenVars->Variable[1].'" />';
			$output .= '<input type="hidden" name="view" value="'.$xml->SearchForm->ResultsViewOptions->Default.'" />';
		

			foreach ($xml->SearchForm->Q as $q) {
		
				//Search Field
				$output .= $this->textSearchFor;
				$output .= '<input type="text" name="'.$q->attributes()->name.'" value="" />';
				$output .= " in ";
			
				// Select "In" Dropdown
				$output .= '<select name="'.$q->Rgn->attributes()->name.'" class="selectmenu">';
				foreach ($q->Rgn->Option as $rgnOption) {
					$output .= '<option value="'.$rgnOption->Value.'">'.$rgnOption->Label.'</option>';
				}
				$output .= '</select>';
			
				$output .= "<br />";
			
				// Boolean
				if (isset($q->Op)) {
					$output .= "<br />";
					$output .= '<select name="'.$q->Op->attributes()->name.'" class="selectmenu">';
					foreach ($q->Op->Option as $opOption) {
						$output .= '<option value="'.$opOption->Value.'"';
						if (isset($opOption->Focus)) {
							$output .= ' selected="selected"';
						}
						$output .= '>'.$opOption->Label.'</option>';
					}
					$output .= '</select>';
					$output .= "<br /><br />";
				}


			}
		
			if ($xml->SearchForm->MediaOnly->Visible == "true") {
				$output .= "<br />";
				$output .= '<input type="checkbox" name="med" value="1" />';
				$output .= $this->textRecordsWithMedia;
			}

			$output .= "<br /><br />";
			$output .= '<input class="grFormElement" type="submit" value="'.$this->textSearchSubmit.'" />';
			$output .= "&nbsp;";
			$output .= '<input class="grFormElement" type="button" value="'.$this->textSearchClear.'" onClick="javascript:clearForm(this.form)" />';
	
			$output .= "</form>";
		}
		else if ($this->class == "findingaid") {
			
			$output  = '<form action="'.$submitURL.'" method="'.$method.'">';
			$output .= sessionInsertCSRF();
			
			$output .= '<input type="hidden" name="dlxsclass" value="findingaid" />';
			
			$output .= '<input type="hidden" name="type" value="'.$xml->SearchType.'" />';
			
			$output .= '<input type="hidden" name="c" value="'.$xml->SearchForm->HiddenVars->Variable[0].'">';
			$output .= '<input type="hidden" name="cc" value="'.$xml->SearchForm->HiddenVars->Variable[1].'">';


			$output .= '<span class="formfont">Search in:</span>';

			$output .= '<select name="rgn" class="selectmenu">';
			foreach ($xml->SearchForm->SearchQuery->RegionSearchSelect->Option as $o) {
				
				$output .= '<option value="'.$o->Value.'"';
				if(isset($o->Focus)) {
					$output .= ' selected="selected"';
				}
				$output .= '>'.$o->Label.'</option>';
			}
			$output .= '</select>';

			$output .= '<span class="formfont">Find:</span>';

			$output .= '<input type="text" name="q1" size="25" value="" class="formfont">';

			$output .= '<input name="Submit" type="submit" value="Search">';
			
			$output .= "</form>";
			
		}
		else {
			$output = webHelper_errorMsg($this->class ."is not defined correctly.");
		}
		return($output);
		
	}
	
	public function getSearchResults() {
		$qs = $this->buildQueryString();
		
		//print $this->URL."?".$qs.";debug=xml";
		
		$xml            = $this->getRemoteXML($this->URL."?".$qs.";debug=xml");
		$this->backLink = $this->getBackLink($xml);
		
		return($xml);
	}
		

	public function getPageXML($url = NULL) {

		if (isnull($url)) {
			$url = $this->cleanGet['HTML']['url'];
		}
		
		//print "$url";
		
		$xml            = $this->getRemoteXML($url);
		$this->backLink = $this->getBackLink($xml);
		
		return($xml);
	}

	public function getFullRecord($entryid=null) {
		
		if (isset($this->cleanGet['HTML']['backLink']) && $this->backLink === NULL) {
			$this->backLink = $this->cleanGet['HTML']['backLink'];
		}
	
		$url  = $this->URL;
		$url .= "?subview=detail;view=entry;debug=xml;";
		$url .= "cc=".$this->coll.";";
		$url .= "entryid=".$entryid;	
		if ($this->backLink !== NULL) {
			$url .= ";back=".$this->backLink;
		}
		//print $url;
		
		return($this->getRemoteXML($url));
	}
	
	public function getRecordList() {
		
		$url  = $this->URL;
		$url .= "?debug=xml;sort=".$this->defaults['sort'].";q1=".$this->coll.";type=boolean;rgn1=ic_all;view=reslist;c=".$this->coll;
		
		return($this->getRemoteXML($url));
	}
	
	private function genReference() {
		
		$ref = "";
		if (isset($this->cleanGet['HTML']['ref'])) {
			$ref = "ref=".$this->cleanGet['HTML']['ref'];
		}
		else if (isset($this->viewType)) {
			switch($this->viewType) {
				case "records":
				    $ref = "ref=records";
					break;
				case "fullrecord":
				    $ref = "ref=fullrecord";
					break;
				default:
				    $ref = "";
			}
		}
		
		return($ref);
	}
	
	public function genSortForm($xml) {
		if (empty($xml->SortOptionsMenu)) {
			return("");
		}


		$ref = $this->genReference();

		$output = '<form action="'.$_SERVER['PHP_SELF'].'?type=sort&amp;'.((empty($ref))?"":$ref."&amp;").'" method="post" name="srtform" id="srtform">';
		
		$output .= sessionInsertCSRF();;
		
		// Hidden Variables
		foreach ($xml->SortOptionsMenu->HiddenVars->Variable as $hvar) {
			if ($hvar->attributes()->name == "debug") {
				// be sure we skip the debug flag, we add it as "xml" after this. 
				continue;
			}
			$output .= '<input type="hidden" name="'.$hvar->attributes()->name.'" value="'.$hvar.'" />';
		}
		$output .= '<input type="hidden" name="debug" value="xml" />';
		
		// Select Dropdown
		$output .= '<select name="sort" class="selectmenu">';
		foreach ($xml->SortOptionsMenu->Option as $option) {
			$output .= '<option value="'.$option->Value.'"';
			if (isset($option->Focus)) {
				$output .= ' selected="selected"';
			}
			$output .= '>'.$option->Label.'</option>';
		}
		
		$output .= "</select>";
		
		$output .= '<input type="Submit" name="sortsubmit" value="sort" class="button" />';
		
		$output .= "</form>";
		
		return($output);
		
	}
	
	public function genSortResultsXML() {
		
		$qs  = $this->buildQueryString();
		$xml = $this->getRemoteXML($this->URL."?".$qs);
		
		return($xml);
		
	}
	
	public function genSliceBar($xml,$file) {
		
		if (empty($xml->SliceSummary->Start) || empty($xml->SliceSummary->End) || empty($xml->SliceSummary->Total)) {
			return("");
		}
		
		if(isnull($file)) {
			return(webHelper_errorMsg("No Template File Provided"));
		}
		
		$template = $this->buildTemplate($file);
		
		$output = "";
		
		foreach ($template as $k => $v) {
			$v = preg_replace('/{dlxs var="sliceStart"}/',$xml->SliceSummary->Start,$v);	
			$v = preg_replace('/{dlxs var="sliceEnd"}/',$xml->SliceSummary->End,$v);
			$v = preg_replace('/{dlxs var="sliceTotal"}/',$xml->SliceSummary->Total,$v);
			$output .= $v;	
		}
		
		$this->sliceBar = $output;
		
		return($output);

	}
	
	private function dlxsUrlEncode($url) {
		
		//some DLXS urls have spaces already encoded, un-encode
		$url = preg_replace('/%20/'," ",$url);
		$url = urlencode($url);
		
		return($url);
		
	}
	
	public function genPaginationBar($xml,$file) {

		

		// Pagination won't work in the full description right now. 
		if ($this->cleanGet['HTML']['type'] == "fullrecord") {
				return("");	
		}

		if(isnull($file)) {
			return(webHelper_errorMsg("No Template File Provided"));
		}
		
		$template = $this->buildTemplate($file);

		$next = (empty($xml->Next->Url))?"":$xml->Next->Url;
		$prev = (empty($xml->Prev->Url))?"":$xml->Prev->Url;

		$ref = $this->genReference();

		$baseURL  = $_SERVER['PHP_SELF'].'?type=url&amp;';
		$baseURL .= (empty($ref))?"":$ref."&amp;";
		$baseURL .= 'url=';

/*
		print "Previous encoded: <pre>".$this->dlxsUrlEncode($prev)."</pre>";
		print "Previous raw: <pre>$prev</pre>";
		
		print "Next encoded: <pre>".$this->dlxsUrlEncode($next)."</pre>";
		print "Next raw: <pre>$next</pre>";
*/ 
		$output = "";

		foreach ($template as $k => $v) {
			if(is_array($v)) {
				
				if(!empty($prev)) {
					$name = $this->textPageBarPrev;
					$link = $baseURL.$this->dlxsUrlEncode($prev);
								
					$vTemp = $v;
					foreach ($vTemp as $k2 => $v2) {
						$v2 = preg_replace('/{dlxs var="url"}/',$link,$v2);
						$v2 = preg_replace('/{dlxs var="name"}/',$name,$v2);

						$output .= $v2;
					}
					$prev = "";
				}
				
				if (isset($xml->Fisheye->Url)) {
				
					foreach ($xml->Fisheye->Url as $url) {
				
						$name = $url->attributes()->name;
						$link = $baseURL.$this->dlxsUrlEncode($url);
					
						$vTemp = $v;
						foreach ($vTemp as $k2 => $v2) {
							$v2 = preg_replace('/{dlxs var="url"}/',$link,$v2);
							$v2 = preg_replace('/{dlxs var="name"}/',$name,$v2);

							$output .= $v2;
						}
					}
				}
			
				if(!empty($next)) {
					$name = $this->textPageBarNext;
					$link = $baseURL.$this->dlxsUrlEncode($next);
					
					$vTemp = $v;
					foreach ($vTemp as $k2 => $v2) {
						$v2 = preg_replace('/{dlxs var="url"}/',$link,$v2);
						$v2 = preg_replace('/{dlxs var="name"}/',$name,$v2);

						$output .= $v2;
					}
					$next = "";
				}
				
			}
			else {
				$output .= $v;	
			}
		}

		$this->pageBar = $output;

		return($output);
	}

	public function buildDetailResults($xml,$file=null) {
		if(isnull($file)) {
			return(webHelper_errorMsg("No Template File Provided"));
		}
		
		$template = $this->buildTemplate($file);

		$thumbUrl   = $xml->Url[1];
		$thumbnail  = "";
		if ($thumbUrl != "nothumb") {
			$thumbnail  = '<a href="'.$xml->RelatedViews->View->Row->Column->Url[3].'" rel="lightbox">';
			$thumbnail .= '<img src="'.$thumbUrl.'" />';
			$thumbnail .= "</a>";
		}
		else if ($this->displayNoThumbonDetail === TRUE) {
			$thumbnail = '<img src="'.$this->noThumbURL.'" />';
		}
		
		$output = "";

		foreach ($template as $k => $v) {
			
			if(is_array($v)) {
				foreach ($xml->Record[0]->Section->Field as $result) {
					
					$label = $result->Label;
					$field = (string)$result->attributes()->abbrev;
										
					//wow. just. wow. gettype shows it as an object, print_r shows it as an array
					if (isset($result->Values->Value[1])) {
						
						$value = '<ul class="valueList">';
						foreach ($result->Values->Value as $Value) {
							$value .= "<li>";
							if (isset($this->searchLinks[$field])) {
								$sanitizedValue = $this->sanitizeQuery(wordSubStr($result->Values->Value,10));
								$value .= '<a href="'.$_SERVER['PHP_SELF'].'?type=search&amp;q1='.$sanitizedValue.'&amp;rgn1='.$field.'">';
							}
							$value .= $Value;
							if (isset($this->searchLinks[$field])) {
								$value .= "</a>";
							}
							$value .= "</li>";
						}
						$value .= "</ul>";
						
					}
					else {
						
						$value = "";
						if (isset($this->searchLinks[$field])) {
							$sanitizedValue = $this->sanitizeQuery(wordSubStr($result->Values->Value,10));
							$value .= '<a href="'.$_SERVER['PHP_SELF'].'?type=search&amp;q1='.$sanitizedValue.'&amp;rgn1='.$field.'">';
						}
						$value .= $result->Values->Value;
						if (isset($this->searchLinks[$field])) {
							$value .= "</a>";
						}
					}

					$vTemp = $v;
					foreach ($vTemp as $k2 => $v2) {
						$v2 = preg_replace('/{dlxs var="thumbnail"}/',$thumbnail,$v2);
						$v2 = preg_replace('/{dlxs var="value"}/',$value,$v2);
						$v2 = preg_replace('/{dlxs var="label"}/',$label,$v2);	

						$output .= $v2;
					}

				}

			}
			else {
				$v = preg_replace('/{dlxs var="thumbnail"}/',$thumbnail,$v);	
				
				$output .= $v;	
			}
		}

		$output = $this->findAidTags2Markup($output);

		return($output);
	}
	
	public function getFindingAidXML($idno=null) {
		
		if (isnull($idno)) {
			return(FALSE);
		}
			
		$url = $this->URL."?c=".$this->coll.";cc=".$this->coll.";rgn=main;view=text;didno=".$idno.";debug=xml";

		$xml = $this->getRemoteXML($url);

		return($xml);
	}

	public function genFindAidPage($xml = null) {
		
		$output = "";
		
		foreach ($xml->OutlineFrame->MenuRow as $menurow) {
		
			if($menurow->attributes()->level == "top") {
				
				if(preg_match('/focusrgn=(.+?)$/',$menurow->Link,$matches)) {
					$this->findAidMenuRows[$matches[1]]["Title"] = $menurow->Text;
					$this->findAidMenuRows[$matches[1]]["XML"]   = $xml->FullTextResults->Results->DocContent->ead->$matches[1];
				}
			}
			
		}
		
		
		/*
		echo "<hr /><pre>";
		print_r($xml->OutlineFrame);
		echo "</pre><hr />";
		echo "<hr /><pre>";
		print_r($this->findAidMenuRows);
		echo "</pre><hr />";
		*/
		return($output);
	}
	
	/*
	$id is the Div ID that the info will be in
	$title is what the link should actually say. 
	*/
	public function addFindAidNavLink($id,$title) {
		if(!isnull($title) && !isnull($id)) {
			
			$this->findAidNavLinks[$id] = $title;
			
			return(TRUE);
		}
		return(FALSE);
	}

	public function genFindAidLeftNav() {
		$output = "";
		foreach ($this->findAidNavLinks as $id => $title) {
			$output .= '<li class="dlxsFindAid">';
			$output .= '<a href="#" onclick="return showOneDiv(\''.$id.'\');">'.$title.'</a>';
			$output .= "</li>";
		}
		$output .= '<li class="dlxsFindAid">';
		$output .= '<a href="#" onclick="return showAllDivs();">Full Text</a>';
		$output .= "</li>";
		
		return($output);
	}

	public function printFindAidElement($attPairs=null) {
		
		$output = "";
		
		if (isnull($attPairs) || !isset($attPairs['name'])) {
			return($this->textFAElemNotFound);
		}

		$element = $attPairs['name'];

		global $xml;
		if (empty($xml)) {
			$xml = $this->xml;
		}	
		
		$output =  $xml->xpath($element);
			
		if ($output === FALSE) {
			return($this->textFAElemNotFound);
		}
					
		$temp2 = "";
		foreach ($output as $I => $K) {
			$K = $this->findAidTags2Markup($K);
			if (isset($attPairs['tag'])) {
				$temp2 .= "<".$attPairs['tag'].">";
			}
			$temp2 .= $K;
			if (isset($attPairs['tag'])) {
				$temp2 .= "</".$attPairs['tag'].">";
			}
		}
		$output = $temp2;
		
		return($output);
	}

	public function printFindAidSubjects($element=null) {
		
		
		
		global $xml;
		
		$subjectArray = array();
		
		foreach ($this->findAidSubjects as $subjectElement) {
			$temp = $xml->xpath($element."/".$subjectElement);
			foreach ($temp as $I => $K) {
				$K = $this->findAidTags2Markup($K);
				$subjectArray[] = $K;
				
			}
		}
		
		sort($subjectArray);
		
		$output = '<ul id="subjectList">';
		foreach ($subjectArray as $I => $K) {
			$output .= "<li>".$K."</li>";	
		}
		$output .= "</ul>";
		
		return($output);
		
	}

	/*
	$temp   = null;
	$items  = explode("/",$element);
	$length = count($items);
	$last   = $items[$length-1];
	foreach ($items as $item) {
		$found = FALSE;
		switch($temp) {
			case null:
			    if (isset($xml->$item)) {
					$temp  = $xml->$item;
					$found = TRUE;
				}
				break;
			default:
			    if (isset($temp->$item)) {
				
					if ($item == $last && isset($attPairs['type'])) {
						
						foreach ($temp->$item as $I => $K) {
							if ($K->attributes()->type == $attPairs['type']) {
								$temp = $K;	
							}
						}
						
						$found = TRUE;
					}
				
					else {
						$temp  = $temp->$item;
						$found = TRUE;
					}
				}
		}
		if ($found === FALSE) {
			$temp = $this->textFAElemNotFound;
			
			$temp =  $xml->xpath('//FullTextResults/Results/DocContent/RegionContent/ead/archdesc/descgrp[@type="admin"]/prefercite/p');


			if ($temp === FALSE) {
				$temp = "FALSE!";
				break;
			}

			$temp2 = "";
			while(list( , $node) = each($temp)) {
				$temp2 .= $node;
			}
			
			$temp = $temp2;

			break;
		}
		
	}*/

	public function buildFindaidResults($xml,$file=null) {

		//echo "<pre>";
		//print_r($xml);
		//echo "</pre>";

		if(isnull($file)) {
			return(webHelper_errorMsg("No Template File Provided"));
		}
		
		if(count($xml->ResList->Results->Item) == 0) {
			
			print "COUNT: ".count($xml->ResList->Results->Item)." -- <br />";
			
			return(false);
		}
		
		$template = $this->buildTemplate($file);
		
		$origTemplate = array();
		$repeat       = FALSE;
		foreach ($template as $k => $v) {
			
			if(is_array($v)) {
				foreach ($v as $k2 => $v2) {
					if(preg_match('/{dlxs repeat="originationStart"}/',$v2)) {
						$template[$k][$k2] = '{dlxs var="faOriginationData"}';
						$repeat = TRUE;
						continue;
					}

					if(preg_match('/{dlxs repeat="originationEnd"}/',$v2)) {
						unset($template[$k][$k2]);
						$repeat = FALSE;
						continue;
					}
		
					if($repeat == TRUE) {
						$origTemplate[] = $v2;
						unset($template[$k][$k2]);
						continue;
					}
				}
			}
		}
		
		echo "Origination Template<pre>";
	//	print_r($origTemplate);
		echo "</pre>";
		
		$output = "";
		
		foreach ($template as $k => $v) {
			
			if(is_array($v)) {
				
				if (!isset($xml->ResList->Results->Item)) {
					continue;
				}

				foreach ($xml->ResList->Results->Item as $result) {

					$vTemp = $v;

					foreach ($vTemp as $k2 => $v2) {
										
						if(preg_match('/{dlxs var="faOriginationData"}/',$v2)) {
							foreach ($result->did->origination as $originationObject) {
								foreach ($originationObject as $oName => $oObject) {
									foreach ($origTemplate as $index => $value) {
										$value = preg_replace('/{dlxs var="originationTitle"}/',$oName,$value);
										$value = preg_replace('/{dlxs var="originationValue"}/',$oObject,$value);
										$v2 .= $value;
									}
								}
							}
						}
										
						$v2 = preg_replace('/{dlxs var="title"}/',$result->did->unittitle,$v2);
						$v2 = preg_replace('/{dlxs var="callnumber"}/',$result->did->unitid,$v2);
						$v2 = preg_replace('/{dlxs var="linkSearchTermsInContext"}/','<a href="'.$result->KwicLink.'">'.$this->textFAKWIC.'</a>',$v2);
						$v2 = preg_replace('/{dlxs var="linkStandardView"}/','<a href="'.$result->OutlineLink.'">'.$this->textFAStandardLink.'</a>',$v2);
						$v2 = preg_replace('/{dlxs var="linkBookbagAdd"}/','<a href="'.$result->Item->BookbagAddHref.'">'.$this->textFABookBagLink.'</a>',$v2);

						$output .= $v2;
					}

				}

			}
			else {
				$output .= $v;	
			}
		}
		
		return($output);
	}

	public function buildThumbResults($xml,$file=null,$thumb=TRUE) {

/*
		$foo = debug_backtrace();
		$foo = print_r($foo, true);

		$fh = fopen("/tmp/dlxslog.txt",'a');
		fwrite($fh,$foo);
		fwrite($fh,"\n\n");
		fclose($fh);
*/

		if(isnull($file)) {
			return(webHelper_errorMsg("No Template File Provided"));
		}

		if (preg_match('/no hits/',$xml->body)) {
			return FALSE;
		}

		$template = $this->buildTemplate($file);

		$output = "";

		foreach ($template as $k => $v) {
			if(is_array($v)) {

				if (!isset($xml->Results->Result)) {
					continue;
				}

				foreach ($xml->Results->Result as $result) {

					$thumbUrl = $result->Url[1];
					$url      = $result->Url[2];

					/* img stuff */
					$viewid   = $result->EntryIdSplit->viewid;
					$cc       = $result->EntryIdSplit->cc;
					$entryid  = $result->EntryIdSplit->entryid;

					$thumbNailTitle = "thumbnail";

					$caption = '<div class="captionDiv">';
					$caption .= '<ul class="captionList">';
					foreach ($result->Record->Section->Field as $item) {

						if ($thumbNailTitle == "thumbnail") {
							$thumbNailTitle = $item->Values->Value;
							$thumbNailTitle = str_replace("[/markup]","",$thumbNailTitle);
							$thumbNailTitle = str_replace('[markup style="kwic"]',"",$thumbNailTitle);
						}

						if ($this->hideSortCaption == TRUE && $item->attributes()->sortfield == "true") {
							continue;
						}

						$caption .= '<li><span class="captionLabel">'.$item->Label.'</span>';
						$caption .= '<span class="captionSpacer">'. $this->textCaptionSpacer .'</span>';
						$caption .= '<span class="caption">'.$item->Values->Value ."</span></li>";
					}
					$caption .= "</ul>";
					$caption .= "</div>";

					$detailLink = '<a href="'.$_SERVER['PHP_SELF'].'?type=fullrecord&amp;entryid='.$entryid.'';
					if($this->backLink !== NULL) {
						$detailLink .= '&amp;backLink='.$this->backLink.'';
					}
					$detailLink .= '">';
					$detailLink .= $this->textDetailRecordLink;
					$detailLink .= '</a>';

					$thumbnail = "";
					if ($thumb === TRUE) {
						if ($thumbUrl != "nothumb") {
							$thumbnail = '<a href="'.$this->dlxsURL.$this->dlxsCGI[$this->class]['helper'].'?viewid='.$viewid.';entryid='.$entryid.';cc='.$cc.';view=image" rel="lightbox">';
							$thumbnail .= '<img src="'.$thumbUrl.'" alt="'.(strip_tags($thumbNailTitle)).'" />';
							$thumbnail .= "</a>"; 
						}
						else {
							$thumbnail = '<img src="'.$this->noThumbURL.'" />';
						}
					}


					$vTemp = $v;
					foreach ($vTemp as $k2 => $v2) {
						
						$v2 = preg_replace('/{dlxs var="thumbnail"}/',$thumbnail,$v2);
						$v2 = preg_replace('/{dlxs var="caption"}/',$caption,$v2);
						$v2 = preg_replace('/{dlxs var="detailLink"}/',$detailLink,$v2);

						$output .= $v2;
					}

				}

			}
			else {
				$output .= $v;	
			}
		}
		
		$output = $this->findAidTags2Markup($output);
		
		return($output);
	}

	private function getDefaultXML() {

		switch ($this->class) {
			case "image":
			    $url = $this->URL."?debug=xml;c=".$this->coll;
				break;
			case "findingaid":
			    $url = $this->URL."?debug=xml;c=".$this->coll;
				break;
			default: 
			    $url = null;
		}
			
		$xml = $this->getRemoteXML($url);
		return($xml);
		
	}

	private function getDefaultSort($xml) {
		
		$sort = NULL;
		
		if ($this->class == "image") {
			$t = trim((string)$xml->BrowseRecords->Url);
			preg_match('/(.+?)\?(.*?)sort=(.+?)(;.+|$)/',$t,$matches);		
			$sort = (isset($matches[3]))?$matches[3]:"";	
		}
		else {
			// This needs to be smarter
			$sort = $this->coll."_id";
		}
		
		return($sort);
			
	}
	
	private function getDefaultGroup($xml) {
		
		$group = NULL;
		
		$t = trim((string)$xml->GroupsLink);
		preg_match('/(.+?)\?(.*?)g=(.+?)(;.+|$)/',$t,$matches);		
		$group = (isset($matches[3]))?$matches[3]:"";	

		return($group);
			
	}

	private function getDefaultTitle($xml) {
		
		$title = trim((string)$xml->CollName);
		return($title);
		
	}

	private function getDefaultContactLink($xml) {
		
		$link = trim((string)$xml->ContactLink);
		
		$patterns[0] = '/\'/';
		$patterns[1] = '/\+/';
		$replacements[1] = '';
		$replacements[0] = '';
		
		$link = preg_replace($patterns, $replacements, $link);
		return($link);
		
	}
	
	private function getDefaultContactText($xml) {
		
		$text = trim((string)$xml->ContactText);
		return($text);
		
	}

	private function getBackLink($xml) {
		if (isset($xml->DlxsGlobals->CurrentCgi->Param[9])) {
			$text = trim((string)$xml->DlxsGlobals->CurrentCgi->Param[9]);
		}
		else {
			$text = NULL;
		}
		return($text);
	}

	private function buildQueryString() {

        /*
		print "cleanGet in BQS:<pre>";
		var_dump($this->cleanGet);
		print "</pre>";
		*/
		
		$qs = "";
		
		if (!empty($this->cleanGet['HTML']['q1']) && !empty($this->cleanGet['HTML']['rgn1'])) {
		
			$q1   = urlencode($this->cleanGet['HTML']['q1']);
			$rgn1 = urlencode($this->cleanGet['HTML']['rgn1']);
		
			//I have NO idea where this string is coming from ... but, it needs converted back to an apostrophe
			$q1 = preg_replace("/%26%23039%3B/","'",$q1);
		
			$qs  = 'q1='.$q1.'&amp;rgn1='.$rgn1.'&amp;type=boolean&amp;c='.$this->coll.'&amp;view=thumbnail';
			$qs .= (empty($this->cleanGet['HTML']['sort']))?"":"&amp;sort=".$this->cleanGet['HTML']['sort'];
			$qs .= (empty($this->cleanGet['HTML']['mediaOnly']))?"":"&amp;med=1";
		
			return($qs);	
		}
		
		if (empty($this->cleanPost['HTML'])) {
			return($qs);
		}
	
		foreach ($this->cleanPost['HTML'] as $item=>$value) {
			
			$item  = urlencode($item);
			$value = urlencode($value);
			
			$qs   .= (empty($qs)?"":"&").$item."=".$value;
		}
		
		return($qs);
	}
	
	private function replaceHighlightTags($xml) {
		
		$xml = preg_replace('/<Highlight\s(.+?)\>/','[markup style="kwic"]',$xml);
		$xml = preg_replace('/<\/Highlight>/','[/markup]',$xml);
		
		return($xml);
	}
	
	private function replaceImageTags($xml) {
		$xml = preg_replace('/%Obold%/','[markup style="bold"]',$xml);
		$xml = preg_replace('/%Oitalic%/','[markup style="italic"]',$xml);
		$xml = preg_replace('/%Ounderline%/','[markup style="underline"]',$xml);
		
		$xml = preg_replace('/%Cbold%/','[/markup]',$xml);
		$xml = preg_replace('/%Citalic%/','[/markup]',$xml);
		$xml = preg_replace('/%Cunderline%/','[/markup]',$xml);
		
		return($xml);
	}
	
	private function replaceFindAidTags($xml) {
		
		$xml = preg_replace('/<markup style="(.+?)">/','[markup style="$1"]',$xml);
		$xml = preg_replace('/<link url="(.+?)">/','[link url="$1"]',$xml);
		$xml = preg_replace('/<image url="(.+?)">/','[image url="$1"]',$xml);

		$xml = preg_replace('/<\/markup>/','[/markup]',$xml);
		$xml = preg_replace('/<\/link>/','[/link]',$xml);
		$xml = preg_replace('/<\/image>/','[/image]',$xml);
		
		return($xml);
	}
	
	private function findAidTags2Markup($str) {
		
		$str = preg_replace('/\[markup style="(.+?)"\]/','<span class="$1">',$str);
		$str = preg_replace('/\[link url="(.+?)"\]/','<a href="$1">',$str);
		$str = preg_replace('/\[image url="(.+?)"\]/','<img src="$1">',$str);
		
		$str = preg_replace('/\[\/markup\]/','</span>',$str);
		$str = preg_replace('/\[\/link\]/','</a>',$str);
		$str = preg_replace('/\[\/image\]/','</img>',$str);
		
		return($str);
	}
	
	private function getRemoteXML($url) {
		
		// print "URL: $url -- <br />";
		
		// $url = str_ireplace("%3b",";",$url);
		// $url = str_ireplace("%3d","=",$url);
		// $url = str_ireplace("%2f","/",$url);
		// $url = str_ireplace("%3a",":",$url);
		$url = str_ireplace(" ","++",$url);
		
		// print "URL: $url -- <br />";
		
		$content = file_get_contents($url);

		if ($content !== false) {
			
			if($this->class == "findingaid") {
				$content = $this->replaceFindAidTags($content);
			}
			else if ($this->class == "image") {
				$content = $this->replaceImageTags($content);
			}
			
			$content = $this->replaceHighlightTags($content);

			$content = utf8_encode($content);
			
			$xml = simplexml_load_string($content);
		}
		else {
			$xml = false;
		}

		return($xml);
	}
	
	private function buildTemplate($file) {
		$temp = file($file);
		
		$template = array();
		
		$baseCount = 0;
		$repeat = FALSE;
		for($I=0;$I<count($temp);$I++) {
			
			if(preg_match('/{dlxs repeat="start"}/',$temp[$I])) {
				$repeat = TRUE;
				continue;
			}
			else if (preg_match('/{dlxs repeat="end"}/',$temp[$I])) {
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
	
	private function sanitizeQuery($str) {

		$str = preg_replace('/[\"\-\;]/'," ",$str);
		$str = preg_replace('/\s+/'," ",$str);
	
		$str = trim($str);
		
		return($str);
		
	}
	
}

?>