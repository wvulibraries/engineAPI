<?php

class pagination {

	public $urlVar	= "page";
	public $prev	= "&#171";
	public $next	= "&#187;";
	public $spacer	= "&#8230;";

	public $totalItems		= NULL;
	public $currentPage		= 1;
	public $itemsPerPage	= 100;
	public $displayedPages	= 7;
	public $displayPrevNext	= "yes";

	/**
	 * @var array
	 */
	private $cleanGet;

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

		// Rebuild correct URL base (this correctly handles mod_rewrite URLs and random query params)
		$urlVar = urlencode($this->urlVar);
		$url = parse_url($_SERVER['REQUEST_URI']);
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
