<?php


class Apache_Solr_LuceneQuery extends Apache_Solr_Query_Abstract
{
	
	public function __construct($q=null){
		parent::__construct($q);
		$this->setQueryParser(self::LUCENE_QP);
	}
	
	/**
	 * Search Solr Index
	 *
	 * @param string $query lucene query string
	 * @param array $fields fields to select from the query return (fl param)
	 * @param array $filters array of filters (e.g array('cuisines:(chinese OR egyptian)'))
	 * @param array $sort array of sorting parameters (e.g. array('tagline'=>'asc')
	 * @param integer $offset
	 * @param integer $count
	 * @return Apache_Solr_Query_Result
	 */
	public function search($query=null,$fields=array(), $filters = array(),$sort=array(), $offset=0, $count=10,array $params=array()){
		if($query){
			$this->setQuery($query);
		}
		
		if($sort)
		{
			$this->addSortFields($sort);
		}
		
		foreach ($params as $k=>$v){
			$this->setParam($k,$v);
		}
		
		if($this->faceted || $this->getFacet() ){
			$this->applyFacets();
		}
		$this->addFilters($filters);
		// search fields
		$this->setFields($fields);
		$this->sort();
		$this->setStart($offset);
		$this->setRows($count);
		$queryResponse = $this->_client->query($this);
		if ($queryResponse->success()) {
			$this->_response = $queryResponse;
			$this->_result = new Apache_Solr_Query_Result($this->_response);
			return $this->_result;
		}else{
			$this->_response = null;
			return FALSE;
		}
	}
	
	public function boost()
	{
		return false;
	}
	
}