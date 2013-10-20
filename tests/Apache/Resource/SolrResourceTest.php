<?php

class SolrResourceTest extends PHPUnit_Framework_TestCase{
	
	public function testResourceExists()
	{
		global $application;
		$application->bootstrap();
		$this->assertInstanceOf('Apache_Solr_Index', Zend_Registry::get('solr'));
	}
	
}