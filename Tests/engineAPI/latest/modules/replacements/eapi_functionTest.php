<?php

class eapi_functionTest extends PHPUnit_Framework_TestCase {

	public function test_templateMatches() {

		require_once "_files/testFunction.php";

		$this->assertEquals("bar",eapi_function::templateMatches(array(0,'function="testFunction" foo="bar"')));

	}
}