<?php

class localvarsTest extends PHPUnit_Framework_TestCase {

	public function setup() {
		$this->localvars = new localvars;
	}

	public function teardown() {
		unset($this->localvars);
	}

	public function test_set_properlySetsVariables() {

		$this->assertTrue($this->localvars->set("foo","bar"));

		$lvExport = $this->localvars->export();

		$this->assertArrayHasKey("foo",$lvExport);
		$this->assertEquals("bar",$lvExport['foo']);

	}

	public function test_set_properlySetsArrayVariables() {

		$this->assertTrue($this->localvars->set(array("this","that"),"bar"));

		$lvExport = $this->localvars->export();

		$this->assertArrayHasKey("this",$lvExport);
		$this->assertArrayHasKey("that",$lvExport['this']);
		$this->assertEquals("bar",$lvExport['this']['that']);

	}

	public function test_export_static_properlyExportsALocalvarsArray() {

		// the static methods use getInstance to create an instance. 
		// That doesn't exist in the scope of testing
		// 
		// Internally they use export() and get() which are tested elsewhere
		$this->markTestSkipped("untestable?");

	}

	public function test_get_static_properlyExportsALocalvarsArray() {

		// the static methods use getInstance to create an instance. 
		// That doesn't exist in the scope of testing
		// 
		// Internally they use export() and get() which are tested elsewhere
		$this->markTestSkipped("untestable?");

	}

	public function test_is_set_TestsIfSetVariableIsSet() {
		$this->localvars->set("foo","bar");
		$this->assertTrue($this->localvars->is_set("foo"));
	}

	public function test_is_set_TestsIfUNSetVariableIsSet() {
		$this->assertFalse($this->localvars->is_set("foo"));
	}

	public function test_get_TestsIfGetRetrievesASetVariable() {
		$this->localvars->set("foo","bar");
		$this->assertEquals("bar",$this->localvars->get("foo"));
	}

	public function test_remove_testsIfVariableIsRemovedCorrectly(){
		$this->localvars->set("foo","bar");
		$this->assertTrue($this->localvars->is_set("foo"));
		$this->localvars->remove("foo");
		$this->assertFalse($this->localvars->is_set("foo"));
	}

	public function test_variable_TestsThatVariableSetWorks() {
		$this->assertTrue($this->localvars->variable("foo","bar"));

		$lvExport = $this->localvars->export();

		$this->assertArrayHasKey("foo",$lvExport);
		$this->assertEquals("bar",$lvExport['foo']);
	}

	public function test_variable_TestsThatVariableGetWorks() {
		$this->assertTrue($this->localvars->variable("foo","bar"));
		$this->assertEquals("bar",$this->localvars->variable("foo"));
	}

	public function test_dbImport_TestThatDBImportWorks() {
		$this->markTestIncomplete("Needs to setup a database to test");
		$mock_db = "foo";
		$this->localvars->set_database($mock_db);
	}

}
?>