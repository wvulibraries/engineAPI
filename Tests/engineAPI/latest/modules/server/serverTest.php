<?php

class serverTest extends PHPUnit_Framework_TestCase {

	public function test_templateMatches() {

		$_SERVER['test'] = "foo";

		$this->assertEquals("foo",server::templateMatches(array(0,'var="test"')));

	}

}

?>