<?php


class Apache_Solr_Query_Result
{
	/**
	 * Query Response
	 * @var SolrQueryResponse
	 */
	protected $queryResponse=null;
	
	protected $response;
	
	public function __construct(SolrQueryResponse $response)
	{
		$this->queryResponse = $response;
		$this->response = $this->queryResponse->getResponse();
	}
	
	public function getFound()
	{
		if($this->response){
			return $this->response->response->numFound;
		}else{
			return NULL;
		}
	}
	
	public function getFacetCounts()
	{
		if($this->response){
			return $this->response->facet_counts->facet_fields;
		}
	}
	
	public function getFacetCountsArray()
	{
		$counts = $this->getFacetCounts();
		return get_object_vars($counts);
	}
	
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
	 * 
	 * @return array|NULL
	 */
	public function getDocs(){
		if($this->response){
			return $this->response->response->docs;
		}else{
			return NULL;
		}
	}
	
	public function setDocs(Array $docs){
		$this->response->response->docs = $docs;
		return $this;
	}
}