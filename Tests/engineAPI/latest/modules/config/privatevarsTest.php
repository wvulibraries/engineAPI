<?php

class privatevarsTest extends PHPUnit_Framework_TestCase {

	public function setup() {
		$this->privatevars = new privatevars(__DIR__,"default");
	}

	public function teardown() {
		unset($this->privatevars);
	}

	public function test_set_properlySetsVariables() {

		$this->assertTrue($this->privatevars->set("foo","bar"));

		$lvExport = $this->privatevars->export();

		$this->assertArrayHasKey("foo",$lvExport);
		$this->assertEquals("bar",$lvExport['foo']);

	}

	public function test_set_properlySetsArrayVariables() {

		$this->assertTrue($this->privatevars->set(array("this","that"),"bar"));

		$lvExport = $this->privatevars->export();

		$this->assertArrayHasKey("this",$lvExport);
		$this->assertArrayHasKey("that",$lvExport['this']);
		$this->assertEquals("bar",$lvExport['this']['that']);

	}

	public function test_is_set_TestsIfSetVariableIsSet() {
		$this->privatevars->set("foo","bar");
		$this->assertTrue($this->privatevars->is_set("foo"));
	}

	public function test_is_set_TestsIfUNSetVariableIsSet() {
		$this->assertFalse($this->privatevars->is_set("foo"));
	}

	public function test_get_TestsIfGetRetrievesASetVariable() {
		$this->privatevars->set("foo","bar");
		$this->assertEquals("bar",$this->privatevars->get("foo"));
	}

	public function test_get_TestsIfGetRetrievesAVariableFromConfigFile() {
		$this->assertEquals("username",$this->privatevars->get(array("mysql","username")));
	}

	public function test_remove_testsIfVariableIsRemovedCorrectly(){
		$this->privatevars->set("foo","bar");
		$this->assertTrue($this->privatevars->is_set("foo"));
		$this->privatevars->remove("foo");
		$this->assertFalse($this->privatevars->is_set("foo"));
	}

	public function test_variable_TestsThatVariableSetWorks() {
		$this->assertTrue($this->privatevars->variable("foo","bar"));

		$lvExport = $this->privatevars->export();

		$this->assertArrayHasKey("foo",$lvExport);
		$this->assertEquals("bar",$lvExport['foo']);
	}

	public function test_variable_TestsThatVariableGetWorks() {
		$this->assertTrue($this->privatevars->variable("foo","bar"));
		$this->assertEquals("bar",$this->privatevars->variable("foo"));
	}

	public function test_dbImport_TestThatDBImportWorks() {
		$this->markTestIncomplete("Needs to setup a database to test");
		$mock_db = "foo";
		$this->privatevars->set_database($mock_db);
	}

}
?>