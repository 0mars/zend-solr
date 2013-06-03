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
	private $boosts = array(
// 			'b_name'	=> 2,
// 			'tagline'	=> 2
	);
	
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
	 * @param array $data associative array of key value
	 * @param bool $allowDups
	 * @param int $commitWithin commit within (in milliseconds)
	 * @return SolrUpdateResponse
	 */
	public function indexDocument(Array $data,$allowDups=false,$commitWithin=false)
	{
		$doc = new SolrInputDocument();
		foreach ($data as $field=>$value){
			if(isset($this->boosts[$field])){
				$boost = $this->boosts[$field];
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
				$doc->addField($field,$value,$boost);
			}
		}
		try
		{
			return $this->addDocument($doc,$allowDups,$commitWithin);
		}catch(Exception $e)
		{
			error_log($this->getDebug());
			return FALSE;
		}
	}
	
	public function AddBoosts(array $boosts){
		$this->boosts = $this->boosts + $boosts;
	}
	
	public function setBoosts(array $boosts){
		$this->boosts = $boosts;
	}
	
}