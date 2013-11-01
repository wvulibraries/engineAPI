<?php
/**
*
*/
class miscTest extends PHPUnit_Framework_TestCase {

	public function test_functionExists_NonExistantMethodReturnsFalse() {
		$this->assertFalse(functionExists('fooClass', 'barMethod'));
	}

	public function test_functionExists_ExistingMethodReturnsTrue() {
		$stub = $this->getMock('stdClass', array('barMethod'));
		$this->assertTrue(functionExists($stub, 'barMethod'));
		unset($stub);
	}

	public function test_functionExists_IntegerReturnsFalse() {
		$this->assertFalse(functionExists(1));
	}

	public function test_functionExists_BooleanTrueReturnsFalse() {
		$this->assertFalse(functionExists(TRUE));
	}

	public function test_functionExists_BooleanFalseReturnsFalse() {
		$this->assertFalse(functionExists(FALSE));
	}

	public function test_functionExists_NullReturnsFalse() {
		$this->assertFalse(functionExists(NULL));
	}

	public function test_functionExists_NonExistantVariableReturnsFalse() {
		// Using an undefined variable
		$this->setExpectedException('PHPUnit_Framework_Error_Notice');

		$this->assertFalse(functionExists($foo));
	}

	public function test_functionExists_EmptyStringReturnsFalse() {
		$this->assertFalse(functionExists(''));
	}

	public function test_functionExists_ExistingFunctionReturnsTrue() {
		$this->assertTrue(functionExists('echo'));
	}

	public function test_functionExists_ExistingClassAsArrowNotationStringReturnsTrue() {
		$stub = $this->getMock('stdClass', array('barMethod'), array(), 'fooClass');
		$this->assertTrue(functionExists('fooClass->barMethod'));
		unset($stub);
	}

	public function test_functionExists_ExistingClassAsColonNotationStringReturnsTrue() {
		$stub = $this->getMock('stdClass', array('barMethod'), array(), 'fooClass2');
		$this->assertTrue(functionExists('fooClass::barMethod'));
		unset($stub);
	}

	public function test_functionExists_NonExistantClassAsArrowNotationStringReturnsFalse() {
		$this->assertFalse(functionExists('fooClass3->barMethod'));
	}

	public function test_functionExists_NonExistantClassAsColonNotationStringReturnsFalse() {
		$this->assertFalse(functionExists('fooClass4::barMethod'));
	}

	public function test_callingFunction() {
		$this->markTestIncomplete('Untestable or assume debug_backtrace will always return with the same string?');
	}

	public function test_callingLine() {
		$this->markTestIncomplete('Untestable or assume debug_backtrace will always return with the same string?');
	}

	public function test_callingFile() {
		$this->markTestIncomplete('Untestable or assume debug_backtrace will always return with the same string?');
	}

	public function test_attPairs_CreatesArrayFromProperString() {
		$a = array(
			'key1' => 'val1',
			'key2' => 'val2',
			);
		$this->assertEquals($a, attPairs('key1="val1" key2="val2"'));
		$this->assertEquals($a, attPairs('key1 = "val1" key2 = "val2"'));
		unset($a);
	}

	public function test_attPairs_CreatesArrayFromImproperString() {
		$a = array(
			'key1' => 'val1',
			'key2' => 'val2',
			);
		$this->assertNotEquals($a, attPairs('key1=val1 key2=val2'));
		$this->assertNotEquals($a, attPairs('key1 = val1 key2 = val2'));
		unset($a);
	}

	// public function test_recurseInsert_InvalidRegexAndConditionReturnsFalse() {
	// 	$this->assertFalse(recurseInsert('anything','anything','badRegex','badCondition',TRUE));
	// 	$this->assertFalse(recurseInsert('anything','anything','badRegex','badCondition',FALSE));
	// 	$this->assertFalse(recurseInsert('anything','anything','badRegex','badCondition'));
	// 	$this->markTestIncomplete();
	// }

	public function test_recurseInsert_() {
		$this->markTestIncomplete('Cannot currently stub EngineAPI.');
	}


}
?>
