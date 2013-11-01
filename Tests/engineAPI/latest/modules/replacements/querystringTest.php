<?php

class queryStringTest extends PHPUnit_Framework_TestCase {

	public function test_templateMatches() {

		$_GET['HTML']['test'] = "foo";

		$this->assertEquals("foo",queryString::templateMatches(array(0,'var="test"')));

	}

	public function test_templateMatches_WithDecode() {

		$_GET['HTML']['test'] = urlencode('foo-This?and"That"');

		$this->assertEquals(urldecode($_GET['HTML']['test']),queryString::templateMatches(array(0,'var="test" decode="true"')));

	}

	public function test_removeQueryStringVar_RemoveExistingVariable() {
		$this->assertEquals('?b=2&c=3&d=4', queryString::getInstance()->remove('a', '?a=1&b=2&c=3&d=4'));
		$this->assertEquals('?a=1&c=3&d=4', queryString::getInstance()->remove('b', '?a=1&b=2&c=3&d=4'));
		$this->assertEquals('?a=1&b=2&d=4', queryString::getInstance()->remove('c', '?a=1&b=2&c=3&d=4'));
		$this->assertEquals('?a=1&b=2&c=3', queryString::getInstance()->remove('d', '?a=1&b=2&c=3&d=4'));

		$this->assertEquals('a=1&c=3&d=4', queryString::getInstance()->remove('b', 'a=1&b=2&c=3&d=4'));
		$this->assertEquals('a=1&c=3&d=4&', queryString::getInstance()->remove('b', 'a=1&b=2&c=3&d=4&'));

		$this->assertEquals('?a=1&c=3&d=4', queryString::getInstance()->remove('b', '?a=1&b=This%20%22is%22%20a%20test%20amd%20%22stuff%22%3F%3F%23%24%25%5E%26*&c=3&d=4'));
	}

	public function test_removeQueryStringVar_NonExistantVariableReturnsSameString() {
		$this->assertEquals('?a=1&b=2&c=3&d=4', queryString::getInstance()->remove('e', '?a=1&b=2&c=3&d=4'));
	}

}

?>