<?php

class enginevarsTest extends PHPUnit_Framework_TestCase {

	public function setup() {
		$this->enginevars = new enginevars(__DIR__,"default");
	}

	public function teardown() {
		unset($this->enginevars);
	}

	public function test_set_properlySetsVariables() {

		$this->assertTrue($this->enginevars->set("foo","bar"));

		$lvExport = $this->enginevars->export();

		$this->assertArrayHasKey("foo",$lvExport);
		$this->assertEquals("bar",$lvExport['foo']);

	}

	public function test_set_properlySetsArrayVariables() {

		$this->assertTrue($this->enginevars->set(array("this","that"),"bar"));

		$lvExport = $this->enginevars->export();

		$this->assertArrayHasKey("this",$lvExport);
		$this->assertArrayHasKey("that",$lvExport['this']);
		$this->assertEquals("bar",$lvExport['this']['that']);

	}

	public function test_is_set_TestsIfSetVariableIsSet() {
		$this->enginevars->set("foo","bar");
		$this->assertTrue($this->enginevars->is_set("foo"));
	}

	public function test_is_set_TestsIfUNSetVariableIsSet() {
		$this->assertFalse($this->enginevars->is_set("foo"));
	}

	public function test_get_TestsIfGetRetrievesASetVariable() {
		$this->enginevars->set("foo","bar");
		$this->assertEquals("bar",$this->enginevars->get("foo"));
	}

	public function test_get_TestsIfGetRetrievesAVariableFromConfigFile() {
		$this->assertEquals("my.domain.com",$this->enginevars->get("server"));
	}

	public function test_remove_testsIfVariableIsRemovedCorrectly(){
		$this->enginevars->set("foo","bar");
		$this->assertTrue($this->enginevars->is_set("foo"));
		$this->enginevars->remove("foo");
		$this->assertFalse($this->enginevars->is_set("foo"));
	}

	public function test_variable_TestsThatVariableSetWorks() {
		$this->assertTrue($this->enginevars->variable("foo","bar"));

		$lvExport = $this->enginevars->export();

		$this->assertArrayHasKey("foo",$lvExport);
		$this->assertEquals("bar",$lvExport['foo']);
	}

	public function test_variable_TestsThatVariableGetWorks() {
		$this->assertTrue($this->enginevars->variable("foo","bar"));
		$this->assertEquals("bar",$this->enginevars->variable("foo"));
	}

	public function test_dbImport_TestThatDBImportWorks() {
		$this->markTestIncomplete("Needs to setup a database to test");
		$mock_db = "foo";
		$this->enginevars->set_database($mock_db);
	}

}
?>