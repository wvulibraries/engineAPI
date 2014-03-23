<?php

class validateTest extends PHPUnit_Framework_TestCase {

	public function setup() {
		$this->validate = new validate();

		$this->internalEmails        = array();             
		$this->internalEmails['wvu'] = '/.*wvu.edu$/';
	}

	public function teardown() {
		unset($this->validate);
	}

	public function test_validationMethods_returnsArrayWithFunctionDescriptionPairs() {

		$array = $this->validate->validationMethods();

		$this->assertTrue(is_array($array));

		foreach ($array as $I=>$V) {
			$this->assertTrue(is_string($I));
			$this->assertTrue(is_string($V));
		}

	}

	public function test_isValidMethod_returnsTrueWhenValidMethodProvided() {

		$array = $this->validate->validationMethods();

		foreach ($array as $I=>$V) {
			$this->assertTrue($this->validate->isValidMethod($I));
		}

	}

	public function test_isValidMethod_returnsFalseWhenInvalidMethodProvided() {

		$this->assertFalse($this->validate->isValidMethod("foobario"));

	}

	public function test_csvValue_isValidCSValue() {
       $this->markTestIncomplete('This test has not been implemented yet.');
	}

	public function test_csvValue_isNotValidCSVValue() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	public function test_regexp() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	public function test_phoneNumber_isValidPhoneNumber() {
		$this->assertTrue($this->validate->phoneNumber("1-123-123-1234"));
		$this->assertTrue($this->validate->phoneNumber("1 - 123 - 123 - 1234"));
		$this->assertTrue($this->validate->phoneNumber("1 (123) - 123 - 1234"));
		$this->assertTrue($this->validate->phoneNumber("+1 (123) - 123 - 1234"));
		$this->assertTrue($this->validate->phoneNumber("1.123.123.1234"));
		$this->assertTrue($this->validate->phoneNumber("123.123.1234"));
		$this->assertTrue($this->validate->phoneNumber("123.123.1234 x 1234"));
		$this->assertTrue($this->validate->phoneNumber("1-123-123-1234 x 1234"));
		$this->assertTrue($this->validate->phoneNumber("1-123-123-1234 ext 1234"));
		$this->assertTrue($this->validate->phoneNumber("1-123-123-1234 extension 1234"));
		$this->assertTrue($this->validate->phoneNumber("1-123-123-1234 ext: 1234"));
	}

	public function test_phoneNumber_isInvalidPhoneNumber() {
		$this->assertFalse($this->validate->phoneNumber("1-123-1234"));

		// area code is required
		$this->assertFalse($this->validate->phoneNumber("123-1234"));
	}

	public function test_ipAddr_isValidIPAddress() {

		for ($oct1 = 0;$oct1 <= 255; $oct1++) {
			for ($oct2 = 0;$oct1 <= 255; $oct1++) {
				for ($oct3 = 0;$oct1 <= 255; $oct1++) {
					for ($oct4 = 0;$oct1 <= 255; $oct1++) {
						$this->assertTrue($this->validate->ipAddr(implode(".",array($oct1,$oct2,$oct3,$oct4))));
					}
				}
			}
		}

	}

	public function test_ipAddr_isinValidIPAddress() {

		$this->assertFalse($this->validate->ipAddr("1"));
		$this->assertFalse($this->validate->ipAddr("foo"));
		$this->assertFalse($this->validate->ipAddr(1));
		$this->assertFalse($this->validate->ipAddr("1111.1.1.1"));
		$this->assertFalse($this->validate->ipAddr("1.1111.1.1"));
		$this->assertFalse($this->validate->ipAddr("1.1.1111.1"));
		$this->assertFalse($this->validate->ipAddr("1.1.1.1111"));
		$this->assertFalse($this->validate->ipAddr("256.256.256.256"));
		$this->assertFalse($this->validate->ipAddr("-1.1.1.1"));

	}

	public function test_ipAddrRange_isValidIPRange() {
		$this->markTestSkipped('ipRange is not properly implemented');
	}

	public function test_ipAddrRange_isInvalidIPRange() {
		$this->markTestSkipped('ipRange is not properly implemented');
	}

	public function test_optionalURL_isValid() {

		$this->assertTrue($this->validate->optionalURL("foo"));
		$this->assertTrue($this->validate->optionalURL("http://foo.com"));

	}

	public function test_optionalURL_isInvalid() {
		$this->markTestIncomplete('This test has not been implemented yet.');
		// $this->assertFalse($this->validate->optionalURL("http://foo"));

	}

	public function test_url_isValidURL() {
		$this->assertTrue($this->validate->url("http://foo.com"));
	}

	public function test_url_isInvalidURL() {
		$this->markTestIncomplete('This test has not been implemented yet.');
		// $this->assertFalse($this->validate->url("http://foo"));
	}

	public function test_emailAddr_isValidEmailAddress() {
		$this->assertTrue($this->validate->emailAddr("foo@bar.com"));
		$this->assertTrue($this->validate->emailAddr("foo@foo-bar.com"));
		$this->assertTrue($this->validate->emailAddr("Foo.Bar@foobar.com"));
		$this->assertTrue($this->validate->emailAddr("foo@foo.bar.com"));
		$this->assertTrue($this->validate->emailAddr("foo.bar@foo.bar.com"));
		$this->assertTrue($this->validate->emailAddr("foo.bar1@foo.bar1.com"));

		// This is technically a valid email address, but it will fail.		
		// $this->assertTrue($this->validate->emailAddr('()<>[]:,;@\\\"!#$%&\'*+-/=?^_`{}| ~.a"@example.org'));
	}

	public function test_emailAddr_isValidInternalEmailAddress() {

		// create an EngineAPI Stub for EngineAPI::enginevars
		$stub_enginevars  = $this->getMockBuilder('enginevars')
								 ->disableOriginalConstructor()
                     			 ->getMock();

        // $stub::staticExpects($this->any())
        $stub_enginevars->expects($this->any())
             			->method('get')
             			->will($this->returnValue($this->internalEmails));
        
        
        $this->validate->set_enginevars($stub_enginevars);

		$this->assertTrue($this->validate->emailAddr("Michael.Bond@mail.wvu.edu",TRUE));
	}

	public function test_emailAddr_isInvalidEmailAddress() {
		$this->assertFalse($this->validate->emailAddr("foo"));
		$this->assertFalse($this->validate->emailAddr("foo@bar"));
		$this->assertFalse($this->validate->emailAddr("@bar.com"));
	}

	public function test_emailAddr_isInvalidInternalEmailAddress() {
		// create an EngineAPI Stub for EngineAPI::enginevars
		$stub_enginevars  = $this->getMockBuilder('enginevars')
								 ->disableOriginalConstructor()
                     			 ->getMock();

        // $stub::staticExpects($this->any())
        $stub_enginevars->expects($this->any())
             			->method('get')
             			->will($this->returnValue($this->internalEmails));

        $this->validate->set_enginevars($stub_enginevars);


		$this->assertFalse($this->validate->emailAddr("foo@bar.com",TRUE));
	}

	public function test_internalEmailAddr_isValidEmailAddress() {

		// create an EngineAPI Stub for EngineAPI::enginevars
		$stub_enginevars  = $this->getMockBuilder('enginevars')
								 ->disableOriginalConstructor()
                     			 ->getMock();

        // $stub::staticExpects($this->any())
        $stub_enginevars->expects($this->any())
             			->method('get')
             			->will($this->returnValue($this->internalEmails));
        
        
        $this->validate->set_enginevars($stub_enginevars);

		$this->assertTrue($this->validate->internalEmailAddr("Michael.Bond@mail.wvu.edu"));
	}

	public function test_internalEmailAddr_isValidInternalEmailAddress() {
		// create an EngineAPI Stub for EngineAPI::enginevars
		$stub_enginevars  = $this->getMockBuilder('enginevars')
								 ->disableOriginalConstructor()
                     			 ->getMock();

        // $stub::staticExpects($this->any())
        $stub_enginevars->expects($this->any())
             			->method('get')
             			->will($this->returnValue($this->internalEmails));

        $this->validate->set_enginevars($stub_enginevars);


		$this->assertFalse($this->validate->emailAddr("foo@bar.com",TRUE));
	}

	public function test_intenger_isValidInteger() {
		$this->assertTrue($this->validate->integer(1));
		$this->assertTrue($this->validate->integer("1"));
		$this->assertTrue($this->validate->integer(-1));
		$this->assertTrue($this->validate->integer("-1"));
	}

	public function test_intenger_isInvalidInteger() {
		$this->assertFalse($this->validate->integer(1.1));
		$this->assertFalse($this->validate->integer("1.1"));
		$this->assertFalse($this->validate->integer(-1.1));
		$this->assertFalse($this->validate->integer("-1.1"));
		$this->assertFalse($this->validate->integer(1.0));
		$this->assertFalse($this->validate->integer("1.0"));
		$this->assertFalse($this->validate->integer(-1.0));
		$this->assertFalse($this->validate->integer("-1.0"));
	}

	public function test_integerSpaces_isValidInteger() {
		$this->assertTrue($this->validate->integerSpaces(1));
		$this->assertTrue($this->validate->integerSpaces("1"));
		$this->assertTrue($this->validate->integerSpaces(-1));
		$this->assertTrue($this->validate->integerSpaces("-1"));
		$this->assertTrue($this->validate->integerSpaces(" 1"));
		$this->assertTrue($this->validate->integerSpaces("1 "));
		$this->assertTrue($this->validate->integerSpaces("1 1"));
	}

	public function test_integerSpaces_isInvalidInteger() {
		$this->assertFalse($this->validate->integer("1.1 "));
	}

	public function test_alphaNumeric_valid() {
		$this->assertTrue($this->validate->alphaNumeric("abcd"));
		$this->assertTrue($this->validate->alphaNumeric("abcd1234"));
		$this->assertTrue($this->validate->alphaNumeric("abcd 1234"));
		$this->assertTrue($this->validate->alphaNumeric("1234 abcd"));
		$this->assertTrue($this->validate->alphaNumeric("1234"));
		$this->assertTrue($this->validate->alphaNumeric("1-2_34"));
	}

	public function test_alphaNumeric_invalid() {
		$this->assertFalse($this->validate->alphaNumeric("abcd."));
	}

	public function test_alphaNumericNoSpaces_valid() {
		$this->assertTrue($this->validate->alphaNumericNoSpaces("abcd"));
		$this->assertTrue($this->validate->alphaNumericNoSpaces("abcd1234"));
		$this->assertTrue($this->validate->alphaNumericNoSpaces("1234"));
		$this->assertTrue($this->validate->alphaNumericNoSpaces("1-2_34"));
	}

	public function test_alphaNumericNoSpaces_invalid() {
		$this->assertFalse($this->validate->alphaNumericNoSpaces("abcd 1234"));
		$this->assertFalse($this->validate->alphaNumericNoSpaces("1234 abcd"));
	}

	public function test_alpha_valid() {
		$this->assertTrue($this->validate->alpha("abcd"));
		$this->assertTrue($this->validate->alpha("ab cd"));
	}

	public function test_alpha_invalid() {
		$this->assertFalse($this->validate->alpha("abcd1234"));
		$this->assertFalse($this->validate->alpha("1234"));
		$this->assertFalse($this->validate->alpha("1-2_34"));
	}

	public function test_alphaNoSpaces_valid() {
		$this->assertTrue($this->validate->alphaNoSpaces("abcd"));
	}

	public function test_alphaNoSpaces_invalid() {
		$this->assertFalse($this->validate->alphaNoSpaces("ab cd"));
	}

	public function test_noSpaces_valid() {
		$this->assertTrue($this->validate->noSpaces("abcd"));
		$this->assertTrue($this->validate->noSpaces("1234"));
		$this->assertTrue($this->validate->noSpaces("!@#$"));
	}

	public function test_noSpaces_invalid() {
		$this->assertFalse($this->validate->noSpaces("ab cd"));
		$this->assertFalse($this->validate->noSpaces("12 34"));
		$this->assertFalse($this->validate->noSpaces("!@ #$"));
	}

	public function test_noSpecialChars_valid() {
		$this->assertTrue($this->validate->noSpecialChars("abcd"));
		$this->assertTrue($this->validate->noSpecialChars("1234"));
	}

	public function test_noSpecialChars_invalid() {
		$this->assertFalse($this->validate->noSpecialChars("!@ #$"));
		$this->assertFalse($this->validate->noSpecialChars("1234 abcd"));
	}

	public function test_date_valid() {

		$dates   = array("1904-01-02","1904-01","1904","1904-02-29");
		$badDates = array("1904/01/02","1904-1-2","1904-23-32","1904-12-32","1903-02-29");

		foreach ($dates as $test) {
			$this->assertTrue($this->validate->date($test));
		}
		foreach ($badDates as $test) {
			$this->assertFalse($this->validate->date($test));
		}

	}

	public function test_date_invalid() {
		$this->assertFalse($this->validate->date("2013/02/29"));
		$this->assertFalse($this->validate->date("02/29/2013"));
		$this->assertFalse($this->validate->date("29/02/2013"));
	}

	public function test_serialized_valid() {

		$string = serialize("test");
		$this->assertTrue($this->validate->serialized($string));

	}

	public function test_serialized_invalid() {

		$string = "test";
		$this->assertFalse($this->validate->serialized($string));

	}

	// public function test_() {

	// }

	// public function test_() {

	// }

	// public function test_() {

	// }

	// public function test_() {

	// }

}

?>