<?php

class UtilTest extends PHPUnit_Framework_TestCase
{
	public function testNormalizeDate()
	{
		$now = new DateTime(null,new DateTimeZone('UTC'));
		
		$this->assertEquals($now->format('Y-m-d\TH:i:s\Z'), Apache_Solr_Util::normalizeDate($now));
	}
}