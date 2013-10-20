<?php

class SolrTestCase extends \PHPUnit_Framework_TestCase
{
	
	public function setUp(){
		global $application;
		$application->bootstrap();
		$this->solr = Zend_Registry::get('solr');
		parent::setUp();
	}
	
	public function tearDown()
	{
		parent::tearDown();
	}
}