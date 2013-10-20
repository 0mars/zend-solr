<?php

class Apache_Solr_Index extends SolrClient
{
	
	/**
	 * Field boosts
	 * 
	 * array(
	 *		'fieldName'	=> boostValue
	 * )
	 * 
	 * @var array
	 */
	protected $_boosts = array();
	
	/**
	 * Adds an array of documents to the index
	 * 
	 * @param array $documents
	 */
	public function indexDocuments(Array $documents)
	{
		for ($i=0,$count=count($documents); $i<$count; $i++){
			$this->indexDocument($documents[$i]);
		}
		$this->commit();
		return TRUE;
	}
	
	/**
	 * Adds a document to Solr Index
	 * 
	 * Applies index-time boosting (optional) 
	 * 
	 * @param array $data associative array of key value
	 * @param bool $overwrite
	 * @param int $commitWithin commit within (in milliseconds)
	 * @return SolrUpdateResponse
	 */
	public function indexDocument(Array $data,$overwrite=true,$commitWithin=false)
	{
		$doc = new SolrInputDocument();
		foreach ($data as $field=>$value){
			if(isset($this->_boosts[$field])){
				$boost = $this->_boosts[$field];
			}else{
				$boost = NULL;
			}
			if (is_array($value)) {
				
				for ($i=0,$count=count($value);$i<$count;$i++){
					$enc = mb_detect_encoding($value[$i]);
					$value[$i] = @iconv($enc, 'UTF-8//IGNORE', $value[$i]);
					$doc->addField($field,$value[$i],$boost);
				}
			}else{
				$enc = mb_detect_encoding($value);
				$value = @iconv($enc, 'UTF-8//IGNORE', $value);
				$doc->addField($field, $value, $boost);
			}
		}
		try
		{
			return $this->addDocument($doc,$overwrite,$commitWithin);
		}catch(Exception $e)
		{
			error_log($this->getDebug());
			return FALSE;
		}
	}
	
	/**
	 * 
	 * @param array $boosts
	 * @return Apache_Solr_Index
	 */
	public function addBoosts(array $boosts){
		$this->_boosts = $this->_boosts + $boosts;
		return $this;
	}
	
	/**
	 * 
	 * @param array $boosts
	 * @return Apache_Solr_Index
	 */
	public function setBoosts(array $boosts){
		$this->_boosts = $boosts;
		return $this;
	}
	
	/**
	 * Get Defined boosts
	 * @return array
	 */
	public function getBoosts(){
		return $this->boosts;
	}
	
	/**
	 * Boost a field (used in index time)
	 * @param string $field
	 * @param string $boost
	 */
	public function addBoost($field, $boost)
	{
		$this->_boosts[$field] = $boost;
	}
	
}