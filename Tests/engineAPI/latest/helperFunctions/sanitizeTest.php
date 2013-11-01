<?php
/**
*
*/
class sanitizeTest extends PHPUnit_Framework_TestCase {

	public function test_dbSanitize_ConvertPHPNullToSqlNull() {
		$this->assertEquals('NULL', dbSanitize(NULL));
	}

	public function test_dbSanitize_ConvertTrueToNumeric1() {
		$this->assertEquals(1, dbSanitize(TRUE));
	}

	public function test_dbSanitize_ConvertFalseToNumeric0() {
		$this->assertEquals(0, dbSanitize(FALSE));
	}

	public function test_dbSanitize_SanitizeString() {
		$this->markTestIncomplete('Skipping until new database object is complete.');
	}

	public function test_dbSanitize_SanitizeStringWithQuotesEqualTrue() {
		$this->markTestIncomplete('Skipping until new database object is complete.');
	}

	public function test_dbSanitize_SanitizeStringWithQuotesEqualFalse() {
		$this->markTestIncomplete('Skipping until new database object is complete.');
	}

	public function test_dbSanitize_SanitizeArrays() {
		$this->markTestIncomplete('Skipping until new database object is complete.');
	}

	public function test_htmlSanitize_NonExistantVariableReturnsFalse() {
		// Using an undefined variable
		$this->setExpectedException('PHPUnit_Framework_Error_Notice');

		$this->assertFalse(htmlSanitize($foo));
	}

	public function test_htmlSanitize_ExistingVariableDoesNotReturnFalse() {
		$foo = 'foo';
		$this->assertNotEquals(FALSE,htmlSanitize($foo));
	}

	public function test_htmlSanitize_SimpleStrings() {
		$this->assertEquals('foo&amp;bar', htmlSanitize('foo&bar'));
		$this->assertEquals('foo &quot;bar&quot; baz', htmlSanitize('foo "bar" baz'));
	}

	public function test_htmlSanitize_ArrayToConvert() {
		$this->assertEquals(array('foo&amp;bar','foo &quot;bar&quot; baz'), htmlSanitize(array('foo&bar','foo "bar" baz')));
	}

	public function test_jsonSanitize_UnsupportedTypeReturnsType() {
		// Using an undefined variable
		$this->setExpectedException('PHPUnit_Framework_Error_Notice');

		$this->assertEquals('foo', jsonSanitize($foo, 'FOO'));
		$this->assertEquals('foo', jsonSanitize($foo, 'Foo'));
		$this->assertEquals('foo', jsonSanitize($foo, 'foo'));
	}

	public function test_jsonSanitize_SupportedTypeDoesNotReturnType() {
		// Using an undefined variable
		$this->setExpectedException('PHPUnit_Framework_Error_Notice');

		$this->assertNotEquals('MYSQL', jsonSanitize($foo, 'MYSQL'));
		$this->assertNotEquals('mysql', jsonSanitize($foo, 'MYSQL'));
		$this->assertNotEquals('mysql', jsonSanitize($foo, 'mysql'));
		$this->assertNotEquals('HTML', jsonSanitize($foo, 'HTML'));
		$this->assertNotEquals('html', jsonSanitize($foo, 'HTML'));
		$this->assertNotEquals('html', jsonSanitize($foo, 'html'));
	}

	public function test_jsonSanitize() {
		$this->markTestIncomplete('Cannot properly test due to lack of function level stubbing.');
	}

	public function test_stripCarriageReturns_StripWhenEnginevarIsTrue() {
		// Mock Enginevars to make sure it is true, should return input string w/o any carriage returns
		$this->markTestIncomplete('Awaiting Enginevars changes.');
	}

	public function test_stripCarriageReturns_ReturnStringWhenEnginevarIsNotTrue() {
		// Test with FALSE as well as random string, should return input string unchanged
		$this->markTestIncomplete('Awaiting Enginevars changes.');
	}

	public function test_stripNewLines_StripSlashR() {
		$this->assertEquals('foobar', stripNewLines("foo\rbar"));
	}

	public function test_stripNewLines_StripSlashN() {
		$this->assertEquals('foobar', stripNewLines("foo\nbar"));
	}

}
?>
