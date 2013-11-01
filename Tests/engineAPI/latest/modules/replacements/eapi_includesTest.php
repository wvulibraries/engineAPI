<?php

class eapi_includesTest extends PHPUnit_Framework_TestCase {

	public function test_templateMatches() {

		$this->markTestSkipped("This can't be tested until recurseInsert is stubbable");

		$this->assertEquals("bar",eapi_includes::templateMatches(array(0,'')));

	}
}