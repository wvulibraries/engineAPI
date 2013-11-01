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

}

?>