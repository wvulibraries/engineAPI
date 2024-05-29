<?php
/**
 * EngineAPI pagination module
 * @package EngineAPI\modules\pagination
 */
class pagination {
	/**
	 * URL GET var to use
	 * @var string
	 */
	public $urlVar= "page";
	/**
	 * Text to use for 'previous' link
	 * @var string
	 */
	public $prev = "&#171";
	/**
	 * Text to use for 'next' link
	 * @var string
	 */
	public $next = "&#187;";
	/**
	 * Text to use for spacer
	 * @var string
	 */
	public $spacer = "&#8230;";
	/**
	 * Total number of things to show
	 * @var int
	 */
	public $totalItems;
	/**
	 * The current page
	 * @var int
	 */
	public $currentPage	= 1;
	/**
	 * Number of items to show per page
	 * @var int
	 */
	public $itemsPerPage = 100;
	/**
	 * Maximum number of page to show
	 * @var int
	 */
	public $displayedPages = 7;
	/**
	 * Show 'previous' and 'next' links
	 * @var string 'yes' to show, anything else to not show
	 */
	public $displayPrevNext	= "yes";

	/**
	 * @var array
	 */
	private $cleanGet;

	/**
	 * Class constructor
	 * @param int $total Total number of things to show
	 */
	function __construct($total) {
		$engine = EngineAPI::singleton();
		$this->cleanGet = $engine->cleanGet;
		$this->totalItems = $total;
	}

	/**
	 * Generate HTML dropdown with number of elements = number of pages
	 * intended for use for switching pages faster
	 * @param string name name of select element
	 * @param string id ID for select element
	 * @param string class class for select element
	 * @return string
	 */
	public function dropdown($name="paginationPageDropdown",$id="paginationPageDropdownID",$class="paginationPageDropdownClass") {

		$output = sprintf('<select name="%s" id="%s" class="%s">',
			htmlSanitize($name),
			htmlSanitize($id),
			htmlSanitize($class)
			);


		for($I=1;$I<=$this->totalPages();$I++) {
			$output .= sprintf('<option value="%s">%s</option>',
				$I,
				$I);
		}

		$output .= "</select>";

		return $output;

	}

	/**
	 * Generate HTML dropdown for number of records per paginated page
	 * 
	 * @param  int maxPerPage the max number of records to display per page
	 * @param int leastPerPage Least number of records to display per page
	 * @param  int divisor Increment from max per page to least per page
	 * @param string name name of select element
	 * @param string id ID for select element
	 * @param string class class for select element
	 * @return string
	 */
	public function recordsPerPageDropdown($maxPerPage=500,$leastPerPage=25,$divisor=100, $name="paginationRecordsPerPageDropdown",$id="paginationRecordsPerPageDropdownID",$class="paginationRecordsPerPageDropdownClass") {
		
		if ($maxPerPage <= $leastPerPage) return "maxPerPage must be greater than leastPerPage";

		$output = sprintf('<select name="%s" id="%s" class="%s">',
			htmlSanitize($name),
			htmlSanitize($id),
			htmlSanitize($class)
			);


		for($I=$maxPerPage;$I>=$leastPerPage;$I-=$divisor) {
			$output .= sprintf('<option value="%s"%s>%s</option>',
				$I,
				($I == $this->itemsPerPage)?' selected="selected"':"",
				$I);
		} 

		$output .= sprintf('<option value="%s"%s>%s</option>',
			$leastPerPage,
			($this->itemsPerPage == $leastPerPage)?' selected="selected"':"",
			$leastPerPage
			);

		$output .= "</select>";

		return $output;

	}

	/**
	 * Generate HTML navBar for pagination
	 * @return string
	 */
	public function nav_bar() {

		$output = "";
		$totalPages = $this->totalPages();

		if ($this->currentPage < 1) {
			$this->currentPage = 1;
		}
		else if ($this->currentPage > $totalPages) {
			$this->currentPage = $totalPages;
		}

		$prev2 = $this->currentPage - 2;
		$prev1 = $this->currentPage - 1;
		$next1 = $this->currentPage + 1;
		$next2 = $this->currentPage + 2;

		if ($this->displayPrevNext == "yes") {
			$this->displayedPages += 2;
		}

		$urlVar = urlencode($this->urlVar);
		$url    = parse_url($_SERVER['REQUEST_URI']);
		if(isset($url['query'])){
			parse_str($url['query'], $query);
			if(isset($query[$urlVar])) unset($query[$urlVar]);
		}
		$linkURL = (isset($query) and sizeof($query))
			? $url['path'].'?'.http_build_query($query)."&$urlVar="
			: $url['path']."?$urlVar=";

		$output .= '<div class="pagination_bar">';
		$output .= '<ul>';


		// Display previous link
		if($this->currentPage == 1) {
			$output .= '<li><a class="disabled" href="#">' . $this->prev . '</a></li>';
		}
		else {
			$output .= '<li><a href="'.$linkURL.$prev1.'">' . $this->prev . '</a></li>';
		}
		// Display previous link


		if ($totalPages <= $this->displayedPages) {
			for($i = 1; $i <= $totalPages; $i++) {
				if($i == $this->currentPage) {
					$output .= '<li><a class="currentpage" href="'.$linkURL.$i.'">' . $i . '</a></li>';
				}
				else {
					$output .= '<li><a href="'.$linkURL.$i.'">' . $i . '</a></li>';
				}
			}
		}
		else {
			if ($this->currentPage <= floor($this->displayedPages / 2)) {
				for($i = 1; $i <= ceil($this->displayedPages / 2); $i++) {
					if($i == $this->currentPage) {
						$output .= '<li><a class="currentpage" href="'.$linkURL.$i.'">' . $i . '</a></li>';
					}
					else {
						$output .= '<li><a href="'.$linkURL.$i.'">' . $i . '</a></li>';
					}
				}
				$output .= '<li>&nbsp;'.$this->spacer.'&nbsp;</li>';
				$output .= '<li><a href="'.$linkURL.$totalPages.'">' . $totalPages . '</a></li>';
			}

			else if ($this->currentPage > $totalPages - floor($this->displayedPages / 2)) {
				$output .= '<li><a href="'.$linkURL.'1">1</a></li>';
				$output .= '<li>&nbsp;'.$this->spacer.'&nbsp;</li>';
				for($i = $totalPages - floor($this->displayedPages / 2); $i <= $totalPages; $i++) {
					if($i == $this->currentPage) {
						$output .= '<li><a class="currentpage" href="'.$linkURL.$i.'">' . $i . '</a></li>';
					}
					else {
						$output .= '<li><a href="'.$linkURL.$i.'">' . $i . '</a></li>';
					}
				}
			}

			else {
				$output .= '<li><a href="'.$linkURL.'1">1</a></li>';
				$output .= '<li>&nbsp;'.$this->spacer.'&nbsp;</li>';
				for($i = $prev1; $i <= $next1; $i++) {
					if($i == $this->currentPage) {
						$output .= '<li><a class="currentpage" href="'.$linkURL.$i.'">' . $i . '</a></li>';
					}
					else {
						$output .= '<li><a href="'.$linkURL.$i.'">' . $i . '</a></li>';
					}
				}
				$output .= '<li>&nbsp;'.$this->spacer.'&nbsp;</li>';
				$output .= '<li><a href="'.$linkURL.$totalPages.'">' . $totalPages . '</a></li>';
			}			
		}

		// Display next link
		if($this->currentPage == $totalPages) {
			$output .= '<li><a class="disabled" href="#">' . $this->next . '</a></li>';
		}
		else {
			$output .= '<li><a href="'.$linkURL.$next1.'">' . $this->next . '</a></li>';
		}
		// Display next link

		$output .= '</ul></div>';

		return $output;
	}

	/**
	 * Returns an integer of the total number of pages
	 *
	 * @return int
	 */
	private function totalPages() {
		return ceil($this->totalItems/$this->itemsPerPage);
	}

	/**
	 * Returns an array with the page limits
	 *
	 * @return array
	 *   - lowLimit:
	 *   - highLimit:
	 */
	public function getLimits() {

		$output = array();

		$output['lowLimit'] = $this->currentPage * $this->itemsPerPage - ($this->itemsPerPage - 1);

		if($this->currentPage * $this->itemsPerPage < $this->totalItems) {
			$output['highLimit'] = $this->currentPage * $this->itemsPerPage;
		}
		else {
			$output['highLimit'] = $this->totalItems;
		}

		return $output;
	}
}
?>
