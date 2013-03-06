<?php
/**
 * EngineAPI searchSolr module
 * Search driver for the Solr Search Engine
 * WARNING: This object is not finished, and has been abandoned for the moment
 *
 * @todo Finish the object
 * @package EngineAPI\modules\search\searchSolr
 */
class searchSolr implements searchProvider
{
	/**
	 * The SolrClient object
	 * @var SolrClient
	 */
	private $solr;
	/**
	 * The config items which were sent to SolrClient
	 * @var array
	 */
	private $cfg;
	/**
	 * If set to TRUE, then an implicit commit() will be done after every solr request
	 * @var bool
	 */
	public $autoCommit = TRUE;

	/**
	 * Class constructor
	 * WARNING: This object is not finished, and has been abandoned for the moment
	 * @param array $cfg
	 */
	public function __construct($cfg){
		$this->solr = new SolrClient($cfg);
		$this->cfg = $cfg;
	}

	public function getRawClient(){
		return $this->solr;
	}

	/**
	 * @todo Needs alot of filling in
	 * @param array $params
	 *        Array of base query parameters:
	 *         + query - Specifies the underlying Solr Query Syntax (default: *:*)
	 *         + fields - An array or csv of fields to return in the result
	 *                    Note: If omitted, Solr will return ALL fields present
	 *         + rowStart - Specifies the number of rows to skip (default: 0)
	 *         + rowEnd - Specifies the maximum number of rows to return in the result
	 *
	 * @return SolrQuery
	 */
	public function getQuery($params){
		$q = new SolrQuery();
		$q->setQuery( isset($params['query']) ? $params['query'] : '*:*' );
		
		if(isset($params['fields'])){
			if(!is_array($params['fields'])) $params['fields'] = explode(',', $params['fields']);
			foreach($params['fields'] as $field){
				$q->addField($field);
			}
		}

		if(isset($params['sort'])){
			$params['sort'] = (array)$params['sort'];
			foreach($params['sort'] as $sortField){
				if(!is_array($sortField)) $sortField['field'] = $sortField;
				$sortField['direction'] = (!isset($sortField['direction']) || strtolower(trim($sortField['direction'])) == 'desc') ? SolrQuery::ORDER_DESC : SolrQuery::ORDER_ASC;
				$q->addSortField($sortField['field'], $sortField['direction']);
			}
		}

		if(isset($params['rowStart'])) $q->setStart($params['rowStart']);
		if(isset($params['rowEnd'])) $q->setRows($params['rowEnd']);

		$q->setHighlight( isset($params['highlight']) ? $params['highlight'] : TRUE );
		if ($q->getHighlight()) {
			$q->addHighlightField( isset($params['fields']) ? implode(',', $params['fields']) : '*' );
			$q->setHighlightSimplePre('<span class="kwic">');
			$q->setHighlightSimplePost('</span>');
			$q->setHighlightUsePhraseHighlighter(TRUE);
			$q->setHighlightHighlightMultiTerm(TRUE);
			$q->setHighlightMergeContiguous(TRUE);
		}
$q->setShowDebugInfo(TRUE);

		// Add more stuff???

		return $q;
	}

	/**
	 * Delete record(s) from solr by their uniqueKey field
	 * @param mixed $IDs An array of IDs to delete, or a single ID
	 * @return SolrObject|bool
	 */
	public function deleteById($IDs){
		try{
			$updateResponse = $this->solr->deleteByIds((array)$IDs);
			if($this->autoCommit) $this->solr->commit();
			return $updateResponse->getResponse();
		}catch(SolrClientException $e){
			errorHandle::newError(__METHOD__."() - Exception thrown! ({$e})", errorHandle::HIGH);
			return FALSE;
		}
	}

	/**
	 * Delete record(s) from solr by query
	 * @param $queries An array of queries to delete, or a single query
	 * @return SolrObject|bool
	 */
	public function deleteByQuery($queries){
		try{
			$updateResponse = $this->solr->deleteByQueries((array)$queries);
			if($this->autoCommit) $this->solr->commit();
			return $updateResponse->getResponse();
		}catch(SolrClientException $e){
			errorHandle::newError(__METHOD__."() - Exception thrown! ({$e})", errorHandle::HIGH);
			return FALSE;
		}
	}

	/**
	 * @param array $records
	 * @param array $fieldMapping
	 * @return SolrObject|bool
	 */
	public function addRecords($records, $fieldMapping=NULL){
		// make sure records is a multidimensional array
		if(count($records) == count($records, COUNT_RECURSIVE)) $records = array($records);

		$docs = array();
		foreach($records as $record){
			$doc = new SolrInputDocument();
			foreach($record as $field => $value){
				if(is_array($fieldMapping) and isset($fieldMapping[$field])){
					$solrFields = (is_array($fieldMapping[$field])) ? $fieldMapping[$field] : explode(',', $fieldMapping[$field]);
					foreach($solrFields as $solrFieldType){
						// Do special field pre=processing
						switch($solrFieldType){
							case 'boolean':
								$value = bool2str($value);
								break;
							case 'binary':
								$value = base64_encode($value);
								break;
							case 'int':
								$value = (int)$value;
								break;
							case 'float':
								$value = (float)$value;
								break;
							case 'date':
								if(isnull($value)) $value = 0;
								$value = (!is_numeric($value)) ? strtotime($value) : $value;
								$value = date("Y-m-d\TH:i:s\Z", $value + date('Z'));
								break;
						}

						// Save the field
						$doc->addField($field.$this->getSearchFieldSuffix($solrFieldType), $value);
					}
				}else{
					$doc->addField($field, $value);
				}
			}
			$docs[] = $doc;
		}

		try{
			$updateResponse = $this->solr->addDocuments($docs);
			if($this->autoCommit) $r = $this->solr->commit();
			return $updateResponse->getResponse();
		}catch(SolrClientException $e){
			errorHandle::newError(__METHOD__."() - Exception thrown! ({$e})", errorHandle::HIGH);
			return FALSE;
		}
	}

	/**
	 * This method will take in a raw SolrQueryResponse and reformat it into a more general form
	 * @param SolrQueryResponse $solrQueryResponse
	 * @return array
	 */
	public function getRecords(SolrQueryResponse $solrQueryResponse){
		$result = array();
		if($solrQueryResponse->success()){
			$solrResponse = $solrQueryResponse->getResponse();

			// Save the high-level request metaData
			$result['request'] = array(
				'status' => $solrResponse->responseHeader['status'],
				'QTime'  => $solrResponse->responseHeader['QTime'],
				'params' => (array)$solrResponse->responseHeader['params']);

			// Save the high-level response metaData
			$result['response'] = array(
				'numFound' 	   => $solrResponse->response['numFound'],
				'start' 	   => $solrResponse->response['start'],
				'highlighting' => (array)$solrResponse->response['highlighting']);

			// Save the high-level documents
			$result['records'] = (array)$solrResponse->response->docs;
		}

		// And we're done!
		return $result;
	}

	/**
	 * Returns a suffix for a document field to designate it's type in Solr
	 * @param string $fieldType
	 * @return string
	 */
	public function getSearchFieldSuffix($fieldType){
		switch(strtolower(trim($fieldType))){
			case 'boolean':
				return '_bool';
			case 'binary':
				return '_bin';
			case 'int':
				return '_int';
			case 'float':
				return '_float';
			case 'date':
				return '_date';
			case 'string':
				return '_str';
			case 'path':
				return '_path';
			case 'text':
				return '_txt';
			case 'text_ws':
				return '_txt_ws';
			case 'text_en':
				return '_txt_en';
			default:
				return '';
		}
	}

	public function displaySearchForm($method='post',$action=NULL,$placeholder="Search Keywords") {
		$engine = EngineAPI::singleton();
		$method = strtolower($method);
		if ($method == 'get') {
			$value = isset($engine->cleanGet['HTML']['solrSearchInput']) ? $engine->cleanGet['HTML']['solrSearchInput'] : NULL;
		}
		else {
			$method = 'post';
			$value = isset($engine->cleanPost['HTML']['solrSearchInput']) ? $engine->cleanPost['HTML']['solrSearchInput'] : NULL;
		}

		$action = isnull($action) ? $_SERVER['REQUEST_URI'] : htmlSanitize($action);

		$output  = '<form name="solrSearchForm" method="'.$method.'" action="'.$action.'">';
		$output .= '<input type="text" name="solrSearchInput" placeholder="'.htmlSanitize($placeholder).'" value="'.$value.'" />';
		$output .= '<input type="submit" name="solrSearchSubmit" value="Search" />';
		$output .= sessionInsertCSRF();
		$output .= '</form>';
		return $output;
	}

	public function submitSearch($method='post') {
		$engine = EngineAPI::singleton();
		$method = strtolower($method);
		if ($method == 'get') {
			$value = !is_empty($engine->cleanGet['MYSQL']['solrSearchInput']) ? $engine->cleanGet['MYSQL']['solrSearchInput'] : '*';
		}
		else {
			$method = 'post';
			$value = !is_empty($engine->cleanPost['MYSQL']['solrSearchInput']) ? $engine->cleanPost['MYSQL']['solrSearchInput'] : '*';
		}

		$options = array();
		$options['query'] = $value;
		// $options['query'] = '*:'.$value;

		$query = $this->getQuery($options);
		try {
			$query_response = $this->query($query);
			return $response = $query_response->getResponse();
		}
		catch(SolrClientException $e) {
			print errorHandle::errorMsg(__METHOD__."() - Exception thrown! ({$e})");
			errorHandle::newError(__METHOD__."() - Exception thrown! ({$e})", errorHandle::HIGH);
			return FALSE;
		}
	}

	public function displayResults($queryResult) {
		if (!isset($queryResult->response->docs) || is_empty($queryResult->response->docs)) {
			return errorHandle::errorMsg("0 results found.");
		}

		$output  = '<table class="searchResults">'."\n";
		$output .= '<tr>';
		$output .= '<th>Field</th>';
		$output .= '<th>Value</th>';
		$output .= '</tr>'."\n";

		if (is_array($queryResult->response->docs)) {
			foreach ($queryResult->response->docs as $result) {

				// insert highlights
				foreach ($queryResult->highlighting as $ID => $field) {
					if ($result['ID'] != $ID) {
						continue;
					}
					foreach ($field as $key => $value) {
						$result[$key] = $value[0];
					}
					break;
				}

				foreach ($result as $fieldName => $fieldValue) {
					$output .= '<tr>';
					$output .= '<td class="fieldName">'.$fieldName.'</td>';
					$output .= '<td class="fieldValue">'.$fieldValue.'</td>';
					$output .= '</tr>'."\n";
				}

				$output .= '<tr class="separator"><td></td><td></td></tr>'."\n";

			}
		}

		$output .= '</table>'."\n";
		return $output;
	}


	/**
	 * SolrClient method proxy. (This will proxy method calls to the SolrClient object)
	 * Note: This method MUST come last in the class definition (bad PHP)
	 * @param $name
	 * @param $arguments
	 * @return mixed
	 */
	public function __call($name,$arguments){
		if(method_exists($this->solr, $name)){
			return call_user_func_array(array($this->solr, $name), $arguments);
		}else{
			$callingFile = callingFile();
			$callingLine = callingLine();
			errorHandle::newError(__METHOD__."() - [Proxy] Call to undefined method '$name' from $callingLine:$callingFile!", errorHandle::HIGH);
		}
		return NULL;
	}
}
