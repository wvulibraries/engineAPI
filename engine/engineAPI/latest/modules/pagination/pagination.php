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
	 * Generate HTML navBar for pagination
	 * @return string
	 */
	public function nav_bar() {

		$output = "";
		$totalPages = ceil($this->totalItems/$this->itemsPerPage);

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

		$urlVarStr = "";
		if (!empty($this->cleanGet)) {
			foreach ($this->cleanGet['MYSQL'] as $key => $val) {
				if ($key != $this->urlVar) {
					$urlVarStr .= (empty($urlVarStr)?"?":"&amp;").urlencode($key)."=".urlencode($val);
				}
			}
		}
		$linkURL = $_SERVER['PHP_SELF'].(empty($urlVarStr)?"?":$urlVarStr."&amp;").urlencode($this->urlVar)."=";


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