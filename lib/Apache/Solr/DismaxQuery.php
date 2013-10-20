<?php

/**
 * Apache Solr DisMax/eDisMax Query Wrapper 
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author Omar A. Shaaban <omar@omaroid.com>
 *
 */
class Apache_Solr_DismaxQuery extends Apache_Solr_Query_Abstract
{
	/**
	 * 
	 * @param string $q Query String
	 */
	public function __construct($q=null){
		parent::__construct($q);
		$this->setQueryParser(self::EDISMAX_QP);
		$this->setParam('q.alt', '*:*');
	}
	
	/**
	 * Search Solr Index
	 *
	 * @param string $query query string (keywords)
	 * @param array $fields fields to select from the query return (fl param)
	 * @param array $filters array of filters (e.g array('cuisines'=>(array('chinese','egyptian')))
	 * @param array $sort array of sorting parameters (e.g. array('tagline'=>'asc')
	 * @param integer $offset
	 * @param integer $count
	 * @return Apache_Solr_Query_Result|FALSE
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
		
		$this->addFilters($filters);
		// search fields
		$this->setFields($fields);
		$this->sort();
		$this->boost();
		if($this->faceted || $this->getFacet() ){
			$this->applyFacets();
		}
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
	
	/**
	 * Boost preset field boosts (qf)
	 * 
	 * @see Apache_Solr_Query_Abstract::boost()
	 */
	public function boost(){
		$aggBoosts = array();
		if ($this->_fieldBoosts) {
			foreach ($this->_fieldBoosts as $field=>$boost)
			{
				if(in_array($field, $this->_queryFields)){
					$flipped = array_flip($this->_queryFields);
					unset($this->_queryFields[$flipped[$field]]);
				}
				$aggBoosts[] = $field.$boost;
			}
			if($aggBoosts){
				$this->setParam('qf', implode(' ',$aggBoosts));
			}
		}
	}
}