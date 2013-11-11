<?php

class serverTest extends PHPUnit_Framework_TestCase {

	public function test_templateMatches() {

		$_SERVER['test'] = "foo";

		$this->assertEquals("foo",server::templateMatches(array(0,'var="test"')));

	}

	public function test_cleanHTTPReferer() {
		$_SERVER['HTTP_REFERER'] = "foo<bar>";
		server::cleanHTTPReferer();
		$this->assertEquals("foo&lt;bar&gt;",$_SERVER['HTTP_REFERER']);
	}

	public function test_cleanQueryStringReferer() {
		$_SERVER['QUERY_STRING'] = "foo<bar>";
		server::cleanQueryStringReferer();
		$this->assertEquals("foo&lt;bar&gt;",$_SERVER['QUERY_STRING']);
	}

	public function test_cleanHTTPReferer_varNotSet() {
		server::cleanHTTPReferer();
		$this->assertFalse(isset($_SERVER['HTTP_REFERER']));
	}

	public function test_cleanQueryStringReferer_varNotSet() {
		server::cleanQueryStringReferer();
		$this->assertFalse(isset($_SERVER['QUERY_STRING']));
	}

}

?>