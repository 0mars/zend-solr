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
	 * array
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
	protected $_sortFields=array();
	
	public $debug=false;
	
	public $queryString = null;
	
	const DISMAX_QP = 'dismax';
	const EDISMAX_QP = 'edismax';
	const LUCENE_QP  = 'lucene';
	
	public function __construct($q=null){
		$this->_client = Zend_Registry::get('solr');
		parent::__construct($q);
	}
	
	public function getQueryParser()
	{
		return $this->getParam('defType');
	}
	
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
		// search fields
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
	
	public function isDismax()
	{
		return $this->getQueryParser() == self::DISMAX_QP ||$this->getQueryParser() == self::EDISMAX_QP; 
	}
	
	public function isLuceneQueryParser()
	{
		return !$this->getQueryParser() || $this->getQueryParser() == self::LUCENE_QP;
	}
	
	
	public function addBoost($field,$boost)
	{
		$this->_fieldBoosts[$field] = $boost;
		return $this;
	}
	
	public function removeBoost($field)
	{
		unset($this->_fieldBoosts[$field]);
		return $this;
	}
	
	public function setFieldBoosts(array $boosts)
	{
		$this->_fieldBoosts = $boosts;
		return $this;
	}
	
	abstract public function boost();
	
	public function setFields(array $fields)
	{
		/* foreach ($fields as $field)
		{
			$this->addField($field);
		}
		return; */
		
		$fieldName = 'fl';
// 		echo '<pre>';print_r($fields);echo '</pre>';die();
		foreach ($fields as $field){
			$this->addField($field);
		}
		if(!$fields){
			$this->addField('*');
		}
		return;
		$queryFields = $this->getParam('fl');
		if ($fields){
			if($queryFields){
				$queryFields .= ','.implode(',', $fields);
			}else{
				$queryFields .= implode(',', $fields);
			}
		}else{
			if($queryFields)
			{
				$queryFields .= ',*';
			}else{
				$queryFields .= '*';
			}
			
		}
		$this->setParam('fl', $queryFields);
	}
	
	public function sort()
	{
		foreach ($this->_sortFields as $field=>$direction)
		{
			$order = constant('SolrQuery::ORDER_'.strtoupper($direction));
			if($order == NULL) throw new Exception('Invalid sort order shall be ASC or DESC');
			$this->addSortField($field, $order);
		}
	}
	
	public function addSortFields(array $fields){
		$this->_sortFields  = array_merge($this->_sortFields,$fields);
	}
	
	public function filterByDistanceCircle($field,$point,$radius)
	{
		$fq = $this->getParam('fq');
		if($fq)
		{
			$this->setParam('fq',$fq.' AND '.$field.':"Intersects(Circle('.$point.' d='.$radius.'))"');
		}else{
			$this->setParam('fq',$field.':"Intersects(Circle('.$point.' d='.$radius.'))"');
		}	
// 		$this->addParam('fq', $field.':"Intersects(Circle('.$point.' d='.$radius.'))"');
	}
	
	protected function _addDistanceFunc($field,$point,$radius)
	{
		if(!$this->getParam('getDist')){
			$this->setParam('getDist',
					'{! score=distance}'.$field.':"Intersects(Circle('.$point.' d='.$radius.'))"'
			);
		}
	}
	
	public function boostDistance($field,$point,$radius=50)
	{
		if(!is_numeric($radius)) throw new Exception('Radius is not a numeric value');
		
		$this->_addDistanceFunc($field, $point, $radius);
		$queryField = 'fl';
		$queryFields = $this->getParam($queryField);
		$this->addField('score');
		$this->addField('distdeg:query($getDist)');
		/* if($queryFields){
			$queryFields .=',,distdeg:query($getDist)';
		}else{
			$queryFields .='score,distdeg:query($getDist)';
		}
		$this->setParam($queryField, $queryFields); */
		$boost = 'query({! score=recipDistance filter=false v=$getDist})';
		// query({! score=distdeg filter=false v=$getDist)
		$this->addParam('boost',$boost);
		return $this;
	}
	
	/* public function addSortField($field,$direction){
		if(strcasecmp($direction, 'asc') !==0 && strcasecmp($direction, 'desc') !==0)
		{
			throw new InvalidArgumentException('Invalid sorting direction '.$direction);
		}
		$this->_sortFields[$field]=$direction;
		return $this;
	} */
	
	public function setFacetQuery(SolrQuery $query,Array $facetFields = array()){
		if(!empty($facetFields)) $this->_facetFields = array_merge($this->_facetFields,$facetFields);
		 
		for ($i = 0, $fc = count($this->_facetFields); $i < $fc; $i ++) {
			$query->addFacetField($this->_facetFields[$i]);
		}
		 
		return $query;
	}
	
	public function setFacetFields(array $facets){
		$this->_facetFields = $facets;
		return $this;
	}
	
	public function addFacets(Array $facetFields = array()){
		if(!empty($facetFields)) $this->_facetFields = array_merge($this->_facetFields,$facetFields);
		 
		return $this;
	}
	
	protected function applyFacets(){
		$this->setFacet(true);
		for ($i = 0, $fc = count($this->_facetFields); $i < $fc; $i ++) {
			$this->addFacetField($this->_facetFields[$i]);
		}
	}
	
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
	
}