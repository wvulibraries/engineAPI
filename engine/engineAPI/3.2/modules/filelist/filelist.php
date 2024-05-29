<?php
class fileList {

	public $directory = NULL;
	public $sortType  = NULL;
	public $files     = array();

	private $xmlFile  = FALSE;
	private $xmlArray = NULL;

	private $currentFile = NULL;
	private $urlBase     = NULL;

	private $engine      = NULL;

	/**
	 * @todo Remove deprecated use of global $engineVars
	 * @param $directory
	 */
	function __construct($directory) {
		global $engineVars;
		$this->engine = EngineAPI::singleton();
		$xml = $engineVars['fileListings']."/".(preg_replace('/\//','.',$directory)).".xml";
		if(file_exists($xml)) {
			$this->xmlFile  = simplexml_load_file($xml);
			$this->xmlArray = $this->parseXML();
		}
		$this->urlBase = "/".$directory;
	}

	/**
	 * Print a debug for the file array
	 *
	 * @deprecated
	 * @return string
	 */
	public function printObject() {
		deprecated();
		$output  = "<pre>";
		$output .= obsafe_print_r($this->files);
		$output .= "</pre>";
		
		return($output);
	}

	/**
	 * Apply a template
	 *
	 * @todo Why is $template default to NULL if that's an error?
	 * @param string $template
	 * @return bool|string
	 */
	public function applyTemplate($template=NULL) {
		if(is_null($template) || !file_exists($template)) {
			return(FALSE);
		}
		
		$tempContent = file_get_contents($template);
		$output = "";
		
		foreach ($this->files as $key => $value) {
			
			// check if its hidden
			if(!empty($value['hidden'])) {
				continue;
			}
			
			$this->currentFile = $key;
			$output .= $this->engine->displayTemplate($tempContent);
		}
		$this->currentFile = NULL;
		
		return($output);
		
	}

	/**
	 * Get some attributes
	 *
	 * @param array $attPairs
	 *   - type:      rowcolor,rowclass,count,zeroCount,url,directory
	 *   - oddcolor:  asd
	 *   - evencolor: asd
	 *   - oddclass:  asd
	 *   - evenclass: asd
	 * @return string
	 */
	public function getAttribute($attPairs) {
		global $engineVars;
		
		$odd    = is_odd($this->currentFile);
		$output = "";
		
		switch($fileAttribute = $attPairs['type']) {
			case "rowcolor":
			$color = ($odd)?$engineVars['oddColor']:$engineVars['evenColor'];
			    if (isset($attPairs['oddcolor']) && $odd) {
					$color = $attPairs['oddcolor'];
				}
				elseif (isset($attPairs['evencolor']) && !$odd) {
					$color = $attPairs['evencolor'];
				}
		
				$output = $color;
				break;
			case "rowclass":
			    $class = ($odd)?$engineVars['oddClass']:$engineVars['evenClass'];
			    if (isset($attPairs['oddclass']) && $odd) {
					$color = $attPairs['oddclass'];
				}
				elseif (isset($attPairs['evenclass']) && !$odd) {
					$color = $attPairs['evenclass'];
				}
		
				$output = $class;
				break;
			case "count":
			    $output = $this->currentFile+1;
				break;
			case "zeroCount":
			    $output = $this->currentFile;
				break;
			case "url":
			    $output = $this->urlBase."/".$this->files[$this->currentFile]['filename'];
			    break;
			case "directory":
			    $output = $this->urlBase;
			    break;
			default:
			    $output = (isset($this->files[$this->currentFile][$fileAttribute]))?$this->files[$this->currentFile][$fileAttribute]:"";
		}
		return($output);
	}

	/**
	 * Parse some XML
	 * Uses $this->xmlFile and Modifies $this->files
	 */
	private function parseXML() {
		
		for($I=0;$I<count($this->xmlFile->file);$I++) {
			$this->files[$I]['id']          = (isset($this->xmlFile->file[$I]->id))?(int)$this->xmlFile->file[$I]->id:NULL;
			$this->files[$I]['sort']        = (isset($this->xmlFile->file[$I]->sort))?(int)$this->xmlFile->file[$I]->sort:NULL;
			$this->files[$I]['binary']      = (isset($this->xmlFile->file[$I]->binary))?(int)$this->xmlFile->file[$I]->binary:NULL;
			$this->files[$I]['hidden']      = (isset($this->xmlFile->file[$I]->hidden))?(int)$this->xmlFile->file[$I]->hidden:NULL;
			$this->files[$I]['filename']    = (isset($this->xmlFile->file[$I]->filename))?(string)$this->xmlFile->file[$I]->filename:NULL;
			$this->files[$I]['title']       = (isset($this->xmlFile->file[$I]->title))?(string)$this->xmlFile->file[$I]->title:NULL;
			$this->files[$I]['description'] = (isset($this->xmlFile->file[$I]->description))?(string)$this->xmlFile->file[$I]->description:NULL;
			$this->files[$I]['definition']  = (isset($this->xmlFile->file[$I]->definition))?(string)$this->xmlFile->file[$I]->definition:NULL;
			$this->files[$I]['lastupdate']  = (isset($this->xmlFile->file[$I]->lastupdate))?(int)$this->xmlFile->file[$I]->lastupdate:NULL;
			$this->files[$I]['filesize']    = (isset($this->xmlFile->file[$I]->filesize))?(int)$this->xmlFile->file[$I]->filesize:NULL;
			$this->files[$I]['location']    = (isset($this->xmlFile->file[$I]->location))?(string)$this->xmlFile->file[$I]->location:NULL;
			$this->files[$I]['type']        = (isset($this->xmlFile->file[$I]->type))?(string)$this->xmlFile->file[$I]->type:NULL;	
		}
		
		$this->sortType = (string)$this->xmlFile->sort->type;
		usort($this->files, array($this, "compareValues"));
		
	}
}

?>