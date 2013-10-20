<?php


abstract class Apache_Solr_Query_Abstract extends SolrQuery
{
	/**
	 * Apache Solr Index object
	 *
	 * @var Apache_Solr_Index
	 */
	protected $_client;
	
	/**
	 * Query Result
	 * @var Apache_Solr_Query_Result
	 */
	protected $_result=null;
	
	/**
	 * Query Response
	 * @var SolrQueryResponse
	 */
	protected $_response=null;
	
	/**
	 * @var array
	 */
	protected $_facetFields = array();
	
	/**
	 * Flag to enable faceted search or not
	 * @var bool
	 */
	public $faceted = true;
	
	/**
	 * Array containing fields to boost
	 * 
	 * Example: array('name'=>'^2')
	 * 
	 * @var array
	 */
	protected $_fieldBoosts = array();
	
	/**
	 * Fields to be searched
	 * @var array
	 */
	protected $_fieldList = array();
	
	/**
	 * @var array
	 */
	protected $_queryFields = array();
	
	/**
	 * @var string
	 */
	protected $_defaultQueryField = 'text';
	
	/**
	 * Sorting fields as array(array('score'=>'desc'))
	 * @var array
	 */
	protected $_sortFields = array();
	
	public $debug = false;
	
	/**
	 * Dismax Query Parser
	 * @var string
	 */
	const DISMAX_QP = 'dismax';
	
	/**
	 * eDisMax Query Parser
	 * @var string
	 */
	const EDISMAX_QP = 'edismax';
	
	/**
	 * Lucene Query Parser
	 * @var string
	 */
	const LUCENE_QP  = 'lucene';
	
	public function __construct($q=null){
		$this->_client = Zend_Registry::get('solr');
		parent::__construct($q);
	}
	
	/**
	 * Retrieve currently set query parser
	 * @return mixed
	 */
	public function getQueryParser()
	{
		return $this->getParam('defType');
	}
	
	/**
	 * Set query parser to be dismax, edismax or lucene
	 * @param string $queryParser can be one of the constants 
	 * 
	 * @throws Exception
	 */
	public function setQueryParser($queryParser)
	{
		if($queryParser !== self::DISMAX_QP 
				&& $queryParser !== self::EDISMAX_QP 
				&& $queryParser !== self::LUCENE_QP
		){
			throw new Exception('Invalid Query Parser');
		}
		$this->setParam('defType', $queryParser);
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
		
		$this->addFilters($filters);
		
		$this->setFields($fields);
		$this->sort();
		$this->boost();
		$this->applyFacets();
		
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
	 * Execute query
	 * It appplies sorting fields and executes
	 * 
	 * @return Apache_Solr_Query_Result|boolean
	 */
	public function exec()
	{
		$this->sort();
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
	 * Check query parser if DisMax or eDisMax or not
	 * 
	 * @return boolean
	 */
	public function isDismax()
	{
		return $this->getQueryParser() == self::DISMAX_QP ||$this->getQueryParser() == self::EDISMAX_QP; 
	}
	
	/**
	 * Check query parser if Lucene or not
	 * 
	 * @return boolean
	 */
	public function isLuceneQueryParser()
	{
		return !$this->getQueryParser() || $this->getQueryParser() == self::LUCENE_QP;
	}
	
	/**
	 * Boost a certain field
	 * 
	 * @param string $field 
	 * @param string $boost example: ^2
	 * @return Apache_Solr_Query_Abstract
	 */
	public function addBoost($field, $boost)
	{
		$this->_fieldBoosts[$field] = $boost;
		return $this;
	}
	
	/**
	 * Unboost a previously boosted fields
	 * 
	 * @param string $field
	 * @return Apache_Solr_Query_Abstract
	 */
	public function removeBoost($field)
	{
		unset($this->_fieldBoosts[$field]);
		return $this;
	}
	
	/**
	 * Example: array('name'=>'^2')
	 * 
	 * @param array $boosts
	 * @return Apache_Solr_Query_Abstract
	 */
	public function setFieldBoosts(array $boosts)
	{
		$this->_fieldBoosts = $boosts;
		return $this;
	}
	
	/**
	 * Set SolrQuery Fields (fl)
	 * Fields to return results
	 * 
	 * @param array $fields example: array('name','features')
	 * @return Apache_Solr_Query_Abstract
	 */
	public function setFields(array $fields)
	{
		foreach ($fields as $field){
			$this->addField($field);
		}
		if(!$fields){
			$this->addField('*');
		}
		return $this;
	}
	
	/**
	 * Apply Sort from previously set sortFields
	 * 
	 * @throws Exception
	 */
	public function sort()
	{
		foreach ($this->_sortFields as $field=>$direction)
		{
			$order = constant('SolrQuery::ORDER_'.strtoupper($direction));
			if($order == NULL) throw new Exception('Invalid sort order shall be ASC or DESC');
			$this->addSortField($field, $order);
		}
	}
	
	/**
	 * Add a sorting field to the query
	 * Format: array('fieldName'=>'direction')
	 * Example: array('name'=>'ASC')
	 * 
	 * @param array $fields
	 */
	public function addSortFields(array $fields){
		$this->_sortFields  = array_merge($this->_sortFields,$fields);
	}
	
	/**
	 * Supports only Solr 4
	 * a spatial field have to be of type solr.SpatialRecursivePrefixTreeFieldType
	 * 
	 * @see http://wiki.apache.org/solr/SolrAdaptersForLuceneSpatial4
	 * @param string $field location field 
	 * @param string $point format (lat,lng)
	 * @param float $radius in Degrees
	 * @return Apache_Solr_Query_Abstract
	 */
	public function filterByDistanceCircle($field,$point,$radius)
	{
		$fq = $this->getParam('fq');
		if($fq)
		{
			$this->setParam('fq', $fq . ' AND ' . $field . ':"Intersects(Circle(' . $point.' d=' . $radius . '))"');
		}else{
			$this->setParam('fq', $field.':"Intersects(Circle(' . $point . ' d=' . $radius . '))"');
		}
		return $this;
	}
	
	/**
	 * Adds the distance function to the query 'getDist'
	 * Used in boosting and filtering
	 *  
	 * @param string $field
	 * @param string $point
	 * @param float $radius in degrees
	 */
	protected function _addDistanceFunc($field,$point,$radius)
	{
		if(!$this->getParam('getDist')){
			$this->setParam('getDist',
					'{! score=distance}'.$field.':"Intersects(Circle('.$point.' d='.$radius.'))"'
			);
		}
	}
	
	/**
	 * 
	 * @param string $field
	 * @param string $point
	 * @param float $radius
	 * @throws Exception
	 * @return Apache_Solr_Query_Abstract
	 */
	public function boostDistance($field,$point,$radius=50)
	{
		if(!is_numeric($radius)) throw new Exception('Radius is not a numeric value');
		
		$this->_addDistanceFunc($field, $point, $radius);
		$queryField = 'fl';
		$queryFields = $this->getParam($queryField);
		$this->addField('score');
		$this->addField('distdeg:query($getDist)');

		$boost = 'query({! score=recipDistance filter=false v=$getDist})';
		
		$this->addParam('boost',$boost);
		return $this;
	}
	
	/**
	 * 
	 * @param SolrQuery $query
	 * @param array $facetFields
	 * @return SolrQuery
	 */
	public function setFacetQuery(SolrQuery $query,Array $facetFields = array()){
		if(!empty($facetFields)) $this->_facetFields = array_merge($this->_facetFields,$facetFields);
		 
		for ($i = 0, $fc = count($this->_facetFields); $i < $fc; $i ++) {
			$query->addFacetField($this->_facetFields[$i]);
		}
		 
		return $query;
	}
	
	/**
	 * Set Facet fields [overwrite existing ones]
	 * 
	 * @param array $facets
	 * @return Apache_Solr_Query_Abstract
	 */
	public function setFacetFields(array $facets){
		$this->_facetFields = $facets;
		return $this;
	}
	
	/**
	 * Add facet fields [to the existing ones]
	 * 
	 * @param array $facetFields
	 * @return Apache_Solr_Query_Abstract
	 */
	public function addFacets(Array $facetFields = array()){
		if(!empty($facetFields)) $this->_facetFields = array_merge($this->_facetFields,$facetFields);
		 
		return $this;
	}
	
	/**
	 * Applies previously set facet fields to the query
	 * 
	 * @return Apache_Solr_Query_Abstract
	 */
	protected function applyFacets(){
		$this->setFacet(true);
		for ($i = 0, $fc = count($this->_facetFields); $i < $fc; $i ++) {
			$this->addFacetField($this->_facetFields[$i]);
		}
		return $this;
	}
	
	/**
	 * Adds filters to the query (fq field)
	 * The filter query is like a WHERE clause in the sql world
	 * 
	 * @param array $filters
	 */
	protected function addFilters(array $filters){
		$filtersStr = '';
		$fq = $this->getParam('fq');
		$aggFilters = array();
		foreach ($filters as $field=>$vals){
			if (!$vals) {
				continue;
			}
			
			if(is_array($vals)){
				foreach ($vals as $k=>$val){
				    $vals[$k] = '"'.$val.'"';
				}
				$filter =  $field.':('.implode(' OR ', $vals).')';
			}else{
				$filter =  $field.':"'.$vals.'"';
			}
			$aggFilters[] = $filter;
		}
		if($aggFilters){
			if($fq){
				$fq .=' AND '. implode(' AND ',$aggFilters);
			}else{
				$fq = implode(' AND ',$aggFilters);
			}
			
			$this->setParam('fq', $fq);
		}
	}
	
	/**
	 * Boost preset fields in $_fieldBoosts
	 */
	abstract public function boost();
	
}