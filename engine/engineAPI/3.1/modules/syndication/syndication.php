<?php

class syndication {

	// Template Variables
	private $templateDir = NULL; // The EngineAPI defined syndication template directory
	public  $template    = NULL; // May be modified directly or with the switchTemplate method

	// Items
	private $itemFields  = array(); 
	private $items       = array();

	// General
	private $syndicationMetadata = array();

	// Template - Optional, string, template to use (located in EngineAPI syndication template directory)
	function __construct($template=NULL) {

		$engine = EngineAPI::singleton();

		$this->templateDir = EngineAPI::$engineVars['syndicationTemplateDir'];
		
		if (!isnull($template) && is_readable($this->templateDir."/".$template)) {
			$this->template = $this->templateDir."/".$template;
		}

	}

	/*
	expects: 
	$template - string, the filename of the template to be used
	$dir - optional, string, the directory where the template is located. If it is located in the EngineAPI syndication directory, dir may be omitted.

	Returns:
	Bool - TRUE if the file exists and is readable, false otherwise.
	*/
	public function switchTemplate($template,$dir=NULL) {

		if (isnull($dir)) {

			if (is_readable($this->templateDir."/".$template)) {
				$this->template = $this->templateDir."/".$template;
				return(TRUE);				
			}

		}
		else {

			if (is_readable($dir."/".$template)) {
				$this->template = $dir ."/". $template;
				return(TRUE);
			}

		}

		return(FALSE);

	}


	public function addItemField($name,$optional=FALSE) {

		$this->itemFields[$name]             = array();
		$this->itemFields[$name]['name']     = $name;
		$this->itemFields[$name]['optional'] = $optional;

		return(TRUE);

	}

	/*
	Expects: array
	*/
	public function addItem($item) {

		$itemTemp = array();

		foreach ($this->itemFields as $I=>$field) {
			if ($field['optional'] === FALSE && !isset($item[$field['name']])) {
				errorHandle::newError("Missing Field:".$field['name'],errorHandle::DEBUG);			
				return(FALSE);
			}

			if (!isset($item[$field['name']])) {
				$item[$field['name']] = "";	
			}

			$itemTemp[$field['name']] = $item[$field['name']];

		}

		$this->items[] = $itemTemp;

		return(TRUE);

	}


	public function syndicationMetadata($name,$data) {
		$this->syndicationMetadata[$name] = $data;
		return(TRUE);
	}

	public function buildXML($html = FALSE) {

		$template = $this->buildTemplate();

		if ($template === FALSE) {
			errorHandle::newError("Error building template.",errorHandle::DEBUG);
			return(FALSE);
		}

		$xml = "";

		foreach ($template as $k => $v) {
			if (is_array($v)) {

				foreach ($this->items as $I=>$item) {

					$vtemp = $v;
					foreach ($vtemp as $k2=>$v2) {
						foreach ($this->itemFields as $var=>$value) {
							$v2 = preg_replace('/{xml name="'.$var.'"}/',$item[$var],$v2);
						}
						$xml .= $v2;
					}
					unset($vtemp);

				}

			}
			else {

				foreach ($this->syndicationMetadata as $var=>$value) {
					$v = preg_replace('/{xml name="'.$var.'"}/',$value,$v);
				}
				$xml .= $v;
			}
		}

		if ($html === TRUE) {
			$xml = htmlentities($xml);
		}

		return($xml);

	}

	private function buildTemplate() {

		if (isnull($this->template)) {
			errorHandle::newError("No template defined.",errorHandle::DEBUG);
			return(FALSE);
		}

		if (!is_readable($this->template)) {
			errorHandle::newError("Template file is not readable.",errorHandle::DEBUG);
			return(FALSE);
		}

		$rawTemp  = file($this->template);
		$template = array();


		$baseCount = 0;
		$repeat    = FALSE;
		for($I=0;$I<count($rawTemp);$I++) {
			
			if(preg_match('/{xml repeat="start"}/',$rawTemp[$I])) {
				$repeat = TRUE;
				continue;
			}
			else if (preg_match('/{xml repeat="end"}/',$rawTemp[$I])) {
				$repeat = FALSE;
				$baseCount++;
				continue;
			}
			
			if ($repeat == FALSE) {
				$template[$baseCount++] = $rawTemp[$I];
			}
			else {
				if (empty($template[$baseCount])) {
					$template[$baseCount] = array();
				}
				
				$template[$baseCount][$I] = $rawTemp[$I];
			}
				
		}
				
		return($template);


	}

}

?>