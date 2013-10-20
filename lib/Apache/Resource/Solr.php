<?php

/**
 * Zend Application Resource for Apache Solr Client
 * 
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author Omar A. Shaaban <omar@omaroid.com>
 *
 */
class Apache_Resource_Solr 
	extends Zend_Application_Resource_ResourceAbstract
{
	/**
	 * Initialize solr resource, with configuration
	 * Options
	 * 
	 * @see Zend_Application_Resource_Resource::init()
	 */
	public function init()
	{
		$config = $this->getOptions();
		
		$solrClient = new Apache_Solr_Index($config);
		
		Zend_Registry::set('solr',$solrClient);
	}
}