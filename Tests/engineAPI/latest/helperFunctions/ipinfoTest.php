<?php

class httpTest extends PHPUnit_Framework_TestCase {

	public function setup() {
		$this->onSiteIps = array();
		$this->onSiteIps[] = '157.182.247.242';
		$this->onSiteIps[] = '157.182.248-249.*';
		$this->onSiteIps[] = '157.182.150.*';
		$this->onSiteIps[] = '157.182.199.193-255';
		$this->onSiteIps[] = '157.182.*.*';
	}

	public function test_rangeCheckArray() {

		$this->assertTrue(ipAddr::rangeCheckArray($this->onSiteIps,"157.182.247.242"));
		$this->assertTrue(ipAddr::rangeCheckArray($this->onSiteIps,"157.182.248.242"));
		$this->assertTrue(ipAddr::rangeCheckArray($this->onSiteIps,"157.182.249.242"));
		$this->assertTrue(ipAddr::rangeCheckArray($this->onSiteIps,"157.182.150.1"));
		$this->assertTrue(ipAddr::rangeCheckArray($this->onSiteIps,"157.182.150.100"));
		$this->assertTrue(ipAddr::rangeCheckArray($this->onSiteIps,"157.182.199.200"));
		$this->assertTrue(ipAddr::rangeCheckArray($this->onSiteIps,"157.182.123.123"));
		$this->assertTrue(ipAddr::rangeCheckArray($this->onSiteIps,"157.182.321.321"));

		$this->assertFalse(ipAddr::rangeCheckArray($this->onSiteIps,"192.168.0.1"));

	}

	public function test_rangeCheck() {

		$this->assertTrue(ipAddr::rangeCheck($this->onSiteIps[0],"157.182.247.242"));
		$this->assertTrue(ipAddr::rangeCheck($this->onSiteIps[1],"157.182.248.242"));
		$this->assertTrue(ipAddr::rangeCheck($this->onSiteIps[1],"157.182.249.242"));
		$this->assertTrue(ipAddr::rangeCheck($this->onSiteIps[2],"157.182.150.1"));
		$this->assertTrue(ipAddr::rangeCheck($this->onSiteIps[2],"157.182.150.100"));
		$this->assertTrue(ipAddr::rangeCheck($this->onSiteIps[3],"157.182.199.200"));
		$this->assertTrue(ipAddr::rangeCheck($this->onSiteIps[4],"157.182.123.123"));
		$this->assertTrue(ipAddr::rangeCheck($this->onSiteIps[4],"157.182.321.321"));

		$this->assertFalse(ipAddr::rangeCheck($this->onSiteIps[0],"157.182.247.243"));
		$this->assertFalse(ipAddr::rangeCheck($this->onSiteIps[1],"157.182.247.242"));
		$this->assertFalse(ipAddr::rangeCheck($this->onSiteIps[2],"157.182.151.1"));
		$this->assertFalse(ipAddr::rangeCheck($this->onSiteIps[3],"157.182.199.192"));
		$this->assertFalse(ipAddr::rangeCheck($this->onSiteIps[4],"157.183.123.123"));

	}

	public function test_check() {

		$this->assertTrue(ipAddr::check($this->onSiteIps,"157.182.247.242"));
		$this->assertTrue(ipAddr::check($this->onSiteIps[0],"157.182.247.242"));
		$this->assertFalse(ipAddr::check($this->onSiteIps,"192.168.0.1"));
		$this->assertFalse(ipAddr::check($this->onSiteIps[0],"157.182.247.243"));

	}

	public function test_onsite() {

		// $enginevars = $this->getMock('enginevars', array('get'));

  //       $enginevars->expects($this->any())
  //            	   ->method('get')
  //          		   ->will($this->returnValue(array("onCampus"=>array("157.182.0-252.*"))));

        // $this->assertTrue(ipAddr::onsite("157.182.247.242"));
        // $this->assertFalse(ipAddr::onsite("15.182.247.242"));
        
        $this->markTestIncomplete('Need to fix the enginevars mock');

	}

}

?>