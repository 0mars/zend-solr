<?php

/**
 * 
 * @author Omar A. Shaban
 *
 */
class Apache_Solr_Query_Result
{
	/**
	 * Query Response
	 * 
	 * @var SolrQueryResponse
	 */
	protected $queryResponse=null;
	
	/**
	 * Response Object
	 * 
	 * @var SolrObject
	 */
	protected $response;
	
	/**
	 * 
	 * @param SolrQueryResponse $response
	 */
	public function __construct(SolrQueryResponse $response)
	{
		$this->queryResponse = $response;
		$this->response = $this->queryResponse->getResponse();
	}
	
	/**
	 * Total number of results found
	 * 
	 * @return integer
	 */
	public function getFound()
	{
		if($this->response){
			return $this->response->response->numFound;
		}else{
			return NULL;
		}
	}
	
	/**
	 * Get Facet counts
	 * 
	 * @return SolrObject
	 */
	public function getFacetCounts()
	{
		if($this->response){
			return $this->response->facet_counts->facet_fields;
		}
	}
	
	/**
	 * Get facet counts from results
	 * 
	 * @return multitype:
	 */
	public function getFacetCountsArray()
	{
		$counts = $this->getFacetCounts();
		return get_object_vars($counts);
	}
	
	/**
	 * Get Response Object
	 * 
	 * @return SolrObject
	 */
	public function getResponse(){
		return $this->response;
	}
	
	/**
	 * Get the query object
	 *
	 * @return SolrQuery
	 */
	public function getQuery(){
		return $this->query;
	}
	
	/**
	 * Get documents from result
	 * @return array|NULL
	 */
	public function getDocs(){
		if($this->response){
			return $this->response->response->docs;
		}else{
			return NULL;
		}
	}
	
	/**
	 * Set documents
	 * 
	 * (Used for hooks)
	 * 
	 * @param array $docs
	 * @return Apache_Solr_Query_Result
	 */
	public function setDocs(Array $docs){
		$this->response->response->docs = $docs;
		return $this;
	}
}