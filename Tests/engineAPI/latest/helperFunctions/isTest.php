<?php
/**
*
*/
class isTest extends PHPUnit_Framework_TestCase {

	public function test_is_function_IsAClosureObject() {
		$a = function() {};
		$this->assertInstanceOf('Closure', $a);
		$this->assertTrue(is_function($a));
	}

	public function test_is_function_IsAnExistingFunction() {
		$this->assertTrue(is_function('is_int'));
	}

	public function test_is_function_IsNotAClosureObjectOrAnExistingFunction() {
		$this->assertFalse(is_function('foo'));
		$this->assertFalse(is_function(1234));
		$this->assertFalse(is_function(array(1,2,3)));
	}

	public function test_is_odd_OddNumberReturnsTrue() {
		$this->assertTrue(is_odd(1));
	}

	public function test_is_odd_EvenNumberReturnsFalse() {
		$this->assertFalse(is_odd(2));
	}

	public function test_is_odd_NonNumbersReturnFalse() {
		$this->assertFalse(is_odd('foo'));
	}

	public function test_isint_IntegerReturnsTrue() {
		$this->markTestSkipped('Deprecated function.');
	}

	public function test_isint_FloatReturnsFalse() {
		$this->markTestSkipped('Deprecated function.');
	}

	public function test_isint_StringReturnsFalse() {
		$this->markTestSkipped('Deprecated function.');
	}

	public function test_isnull_NullReturnsTrue() {
		$this->assertTrue(isnull(NULL,FALSE));
		$this->assertTrue(isnull(NULL,TRUE));
		$this->assertTrue(isnull(NULL));
		$this->assertTrue(isnull('null'));
		$this->assertTrue(isnull('NULL'));
	}

	public function test_isnull_NotNullReturnsFalse() {
		$this->assertFalse(isnull('foo',FALSE));
		$this->assertFalse(isnull('foo',TRUE));
		$this->assertFalse(isnull('foo'));
		$this->assertFalse(isnull(array('foo')));
		$this->assertFalse(isnull(array(NULL)));
		$this->assertFalse(isnull(1234));
	}

	public function test_isempty_EmptyReturnsTrue() {
		$this->assertTrue(isempty($foo));
		$this->assertTrue(isempty(''));
		$this->assertTrue(isempty(NULL,FALSE));
		$this->assertTrue(isempty(NULL,TRUE));
		$this->assertTrue(isempty(NULL));
		$this->assertTrue(isempty(FALSE));
		$this->assertTrue(isempty(array()));
	}

	public function test_isempty_NotEmptyReturnsFalse() {
		$a = 'foo';
		$this->assertFalse(isempty($a));
		$this->assertFalse(isempty(array('')));
		$this->assertFalse(isempty(array('foo')));
		$this->assertFalse(isempty(array(NULL)));
		$this->assertFalse(isempty(0));
		$this->assertFalse(isempty('0'));
		$this->assertFalse(isempty('foo'));
	}

	public function test_isCLI_RunningFromCommandLine() {
		$this->markTestSkipped('Currently untestable: Needs code refactoring.');
	}

	public function test_isCLI_NotRunningFromCommandLine() {
		$this->markTestSkipped('Currently untestable: Needs code refactoring.');
	}

	public function test_isPHP_VersionGreaterThanOrEqualToSuppliedReturnsTrue() {
		$this->assertTrue(isPHP(-999999));
	}

	public function test_isPHP_VersionLessThanSuppliedReturnsFalse() {
		$this->assertFalse(isPHP(999999));
	}

	public function test_isSerialized_SerializedArrayReturnsTrue() {
		$a = serialize(array(1,2,3));
		$b = serialize(array());
		$this->assertTrue(isSerialized($a));
		$this->assertTrue(isSerialized($b));
	}

	public function test_isSerialized_SerializedStringReturnsTrue() {
		$a = serialize('foo');
		$b = serialize('');
		$this->assertTrue(isSerialized($a));
		$this->assertTrue(isSerialized($b));
	}

	public function test_isSerialized_SerializedBooleanReturnsTrue() {
		$a = serialize(TRUE);
		$b = serialize(FALSE);
		$this->assertTrue(isSerialized($a));
		$this->assertTrue(isSerialized($b));
	}

	public function test_isSerialized_NonSerializedArrayReturnsFalse() {
		$a = array(1,2,3);
		$b = array();
		$this->assertFalse(isSerialized($a));
		$this->assertFalse(isSerialized($b));
	}

	public function test_isSerialized_NonSerializedStringReturnsFalse() {
		$this->assertFalse(isSerialized('foo'));
	}

	public function test_isSerialized_NonSerializedBooleanReturnsFalse() {
		$this->assertFalse(isSerialized(TRUE));
		$this->assertFalse(isSerialized(FALSE));
	}
}
?>
