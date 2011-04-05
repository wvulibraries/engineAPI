<?php

// Initial Creation: 11/01/2010
// Very basic functionality.
// To do: metadata, slide shows, 
class photoAlbum {
	
	private $engine     = NULL;
	private $dir        = NULL;
	
	public $thumbWidth  = "100";
	public $thumbHeight = "100";
	public $start       = 1;
	public $perPage     = 10;
	public $next        = NULL;
	
	public $lightbox    = TRUE;
	public $groupImages = TRUE; 
	
	private $photos = array();
	
	function __construct($directory=NULL) {
		
		if (!($engine instanceof EngineCMS)) {
			return(FALSE);
		}
		
		if (isnull($directory) || !file_exists($directory)) {
			return(FALSE);
		}
		
		global $engineVars;
		
		include_once($engineVars['phpthumb']);
		
		$this->engine      = EngineAPI::singleton();
		$this->dir         = $directory;
	}
	
	function __destruct() {
	}
	
	public function displayThumbnails() {
		$output = "";
		foreach ($this->photos as $I=>$V) {
			$output .= '<div class="photo">';
			$output .= '<a href="'.$V['url'].'"';
			if ($this->lightbox === TRUE) {
				if ($this->groupImages === TRUE) {
					$output .= ' rel="lightbox['.$this->dir.']"';
				}
				else {
					$output .= ' rel="lightbox"';
				}
			}
			$output .= '><img src="'.$V['thumbURL'].'" /></a>';
			$output .= "</div>";
		}
		return($output);
	}
	
	public function scanDir($directory=NULL) {
		global $engineVars;
		
		if (isnull($directory)) {
			$directory = $this->dir;
		}
		
		$directory .= (substr($directory,-1) !== "/")?"/":"";
		
		$files     = scandir($directory);
		$fileCount = count($files)-3; // subtract 2 for the "." and ".." and "thumbs"
		
		// the first 2 entries in the scandir are "." and ".." 
		// shifting the first one off and leaving the second as a place holder
		array_shift($files);
		
		$last = (($this->start + $this->perPage)>$fileCount)?$fileCount:($this->start + $this->perPage);
		
		$count = $this->start-1;
		for ($I = $this->start;$I < $last;$I++) {
			$V = $files[$I];
		#foreach ($files as $I=>$V) {
			if ($V == "." || $V == ".." || is_dir($directory.$V) === TRUE) {
				continue;
			}
			
			$this->photos[$count]['filename']  = $directory.$V;
			$this->photos[$count]['thumbFile'] = $directory."thumbs/".$V;
			
			
			$this->photos[$count]['url']       = str_replace($engineVars['documentRoot'],"",$this->photos[$count]['filename']);
			$this->photos[$count]['thumbURL']  = str_replace($engineVars['documentRoot'],"",$this->photos[$count]['thumbFile']);
			
			if (!file_exists($this->photos[$count]['thumbFile'])) {
			
				$thumb = PhpThumbFactory::create($this->photos[$count]['filename']);
				$thumb->resize($this->thumbWidth,$this->thumbHeight);
				$thumb->save($this->photos[$count]['thumbFile']);
			
			}
			
			$count++;
		}
		
		if (++$count < $fileCount) {
			$this->next = $count;
		}
		
	}	
}

?>