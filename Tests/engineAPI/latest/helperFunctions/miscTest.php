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

	public function test_recurseInsert_InvalidRegexAndConditionReturnsFalse() {
	// 	$this->assertFalse(recurseInsert('anything','anything','badRegex','badCondition',TRUE));
	// 	$this->assertFalse(recurseInsert('anything','anything','badRegex','badCondition',FALSE));
	// 	$this->assertFalse(recurseInsert('anything','anything','badRegex','badCondition'));
		$this->markTestIncomplete('Cannot currently stub EngineAPI.');
	}

	public function test_recurseInsert_() {
		$this->markTestIncomplete('Cannot currently stub EngineAPI.');
	}

	public function test_linkPhone_NoPhoneNumber() {
		// $this->assertFalse(linkPhone(array()));
		// $this->assertFalse(linkPhone(array('phone'=>'')));
		$this->markTestIncomplete('Need to stub errorHandle::errorMsg()');
	}

	public function test_linkPhone_InvalidPhoneFormat() {
		// $this->assertFalse(linkPhone(array('phone'=>'(123) 456-7890')));
		$this->markTestIncomplete('Need to stub errorHandle::errorMsg()');
	}

	public function test_linkPhone_NoDisplayNotMobile() {
		// $phone = '1-234-567-8901';
		// $this->assertEquals('<span class="phoneNumber">'.$phone.'</span>', linkPhone(array('phone'=>$phone)));
		$this->markTestIncomplete('Need to stub mobileBrowsers::isMobileBrowser()');
	}

	public function test_linkPhone_NoDisplayIsMobile() {
		// $phone = '1-234-567-8901';
		// $this->assertEquals('<span class="phoneNumber">'.$phone.'</span>', linkPhone(array('phone'=>$phone)));
		$this->markTestIncomplete('Need to stub mobileBrowsers::isMobileBrowser()');
	}

	public function test_linkPhone_HasDisplayNotMobile() {
		// $phone = '1-234-567-8901';
		// $display = '(234) 567-8901';
		// $this->assertEquals('<span class="phoneNumber">'.$phone.'</span>', linkPhone(array('phone'=>$phone,'display'=>$display)));
		$this->markTestIncomplete('Need to stub mobileBrowsers::isMobileBrowser()');
	}

	public function test_linkPhone_HasDisplayIsMobile() {
		// $phone = '1-234-567-8901';
		// $display = '(234) 567-8901';
		// $this->assertEquals('<span class="phoneNumber">'.$phone.'</span>', linkPhone(array('phone'=>$phone,'display'=>$display)));
		$this->markTestIncomplete('Need to stub mobileBrowsers::isMobileBrowser()');
	}

	public function test_displayFileSize_NotNumericReturnsNaN() {
		$this->assertEquals('NaN', displayFileSize('foo'));
	}

	public function test_displayFileSize_CorrectlyIdentifyingSizes() {
		$this->assertEquals('1 Byte', displayFileSize(1));
		$this->assertEquals('1 KB', displayFileSize(1000));
		$this->assertEquals('1 MB', displayFileSize(1000000));
		$this->assertEquals('1 GB', displayFileSize(1000000000));
		$this->assertEquals('1 TB', displayFileSize(1000000000000));
		$this->assertEquals('1 PB', displayFileSize(1000000000000000));
	}

	public function test_castAs_NotValidCastType() {
		$this->setExpectedException('PHPUnit_Framework_Error_Warning');

		$this->assertNull(castAs('foo', 'bar'));
	}

	public function test_castAs_InputCastAsBoolean() {
		$this->assertTrue(is_bool(castAs(1,'boolean')));
	}

	public function test_castAs_InputCastAsBool() {
		$this->assertTrue(is_bool(castAs(1,'bool')));
	}

	public function test_castAs_InputCastAsInteger() {
		$this->assertTrue(is_int(castAs(1,'integer')));
	}

	public function test_castAs_InputCastAsInt() {
		$this->assertTrue(is_int(castAs(1,'int')));
	}

	public function test_castAs_InputCastAsFloat() {
		$this->assertTrue(is_float(castAs(1,'float')));
	}

	public function test_castAs_InputCastAsDouble() {
		$this->assertTrue(is_float(castAs(1,'double')));
	}

	public function test_castAs_InputCastAsString() {
		$this->assertTrue(is_string(castAs(1,'string')));
	}

	public function test_castAs_InputCastAsArray() {
		$this->assertTrue(is_array(castAs(1,'array')));
	}

	public function test_castAs_InputCastAsObject() {
		$this->assertTrue(is_object(castAs(1,'object')));
	}

	public function test_castAs_InputCastAsNull() {
		$this->assertTrue(is_null(castAs(1,'null')));
	}

}
?>
