<?php
class textManipulationTest extends PHPUnit_Framework_TestCase {
    function test_wordSubStr_simple(){
        $this->assertEquals(wordSubStr('a b c', 1), 'a');
        $this->assertEquals(wordSubStr('a b c', 2), 'a b');
        $this->assertEquals(wordSubStr('a b c', 3), 'a b c');
        $this->assertEquals(wordSubStr('a b c', 4), 'a b c');
        $this->assertEquals(wordSubStr('a b c'), 'a b c');
    }
    function test_wordSubStr_arrayparams(){
        $params = array('str'=>'a b c', 'wordCount'=>2);
        $this->assertEquals(wordSubStr($params), 'a b');
    }

    function test_kwic_basic(){
        $targetStr = 'dog <span class="kwic">cat</span> mouse';
        $this->assertEquals(kwic('cat', 'dog cat mouse'), $targetStr);
    }

    function test_kwic_noMatch(){
        $targetStr = 'dog mouse';
        $this->assertEquals(kwic('cat', $targetStr), $targetStr);
    }

    function test_str2TitleCase_Normal(){
        $this->assertEquals(str2TitleCase('tO KILL A MoCKinGBird'), 'To Kill a Mockingbird');
        $this->assertEquals(str2TitleCase('Gone WITH the Wind'), 'Gone With the Wind');
        $this->assertEquals(str2TitleCase('AnGeLs and Demons'), 'Angels and Demons');
        $this->assertEquals(str2TitleCase('a Tale of twO cities'), 'A Tale of Two Cities');
        $this->assertEquals(str2TitleCase('The Lord OF ThE Rings'), 'The Lord of the Rings');
        $this->assertEquals(str2TitleCase('WaterSHIP down'), 'Watership Down');
        $this->assertEquals(str2TitleCase("gOD&apos;s Little Acre"), "God&apos;s Little Acre");
        $this->assertEquals(str2TitleCase('Catch-22'), 'Catch-22');
        $this->assertEquals(str2TitleCase('Mr. men'), 'Mr. Men');
        $this->assertEquals(str2TitleCase("Where's WALLY?"), "Where's Wally?");
    }

    function test_str2TitleCase_Preserve(){
        $this->assertEquals(str2TitleCase('to KILL A MoCKinGBird',TRUE), 'To KILL A MoCKinGBird');
        $this->assertEquals(str2TitleCase("gOD&apos;s Little Acre",TRUE), "gOD&apos;s Little Acre");
        $this->assertEquals(str2TitleCase('Catch-22',TRUE), 'Catch-22');
        $this->assertEquals(str2TitleCase('Mr. men',TRUE), 'Mr. Men');
    }

    function test_nv_title_skip_dotted(){
        $this->markTestIncomplete('Unknown 3rd party code');
    }

    function test_obfuscateEmail_stringInput(){
        $this->assertEquals(obfuscateEmail('foo@bar.com'), '&#102;&#111;&#111;&#64;&#98;&#97;&#114;&#46;&#99;&#111;&#109;');
    }

    function test_obfuscateEmail_arrayInput(){
        $input = array('foo@bar.com');
        $this->assertEquals(obfuscateEmail($input), '&#102;&#111;&#111;&#64;&#98;&#97;&#114;&#46;&#99;&#111;&#109;');
    }

    function test_secureFilepath_documentRoot(){
        $_SERVER['DOCUMENT_ROOT'] = '/some/hidden/path';
        $this->assertEquals(secureFilepath($_SERVER['DOCUMENT_ROOT']."/some/public/part"), '[DOCUMENT_ROOT]/some/public/part');
        $this->assertEquals(secureFilepath("/some/basic/path"), '/some/basic/path');
    }
    function test_secureFilepath_home(){
        // Document root needs to be set
        $_SERVER['DOCUMENT_ROOT'] = '/some/hidden/path';
        $this->assertEquals(secureFilepath("/home/directory"), '[HOME]/directory');
        $this->assertEquals(secureFilepath("/some/basic/path"), '/some/basic/path');
    }

    function test_formatPhone_basicPhoneInputsWithDefaultParams(){
        $this->assertEquals(formatPhone('123.456.7890'), '(123) 456-7890');
        $this->assertEquals(formatPhone('123-456-7890'), '(123) 456-7890');
        $this->assertEquals(formatPhone('(123) 456-7890'), '(123) 456-7890');
        $this->assertEquals(formatPhone('123/456/7890'), '(123) 456-7890');
    }

    function test_formatPhone_basicPhoneExtensionInputsWithDefaultParams(){
        $this->assertEquals(formatPhone('123.456.7890 x12345'), '(123) 456-7890 x12345');
        $this->assertEquals(formatPhone('123.456.7890x12345'), '(123) 456-7890 x12345');
        $this->assertEquals(formatPhone('123.456.7890 ext.12345'), '(123) 456-7890 x12345');
        $this->assertEquals(formatPhone('123.456.7890 extension 12345'), '(123) 456-7890 x12345');
    }

    function test_formatPhone_formats(){
        $this->assertEquals(formatPhone('123.456.7890', 0), '1234567890');
        $this->assertEquals(formatPhone('123.456.7890', 1), '(123) 456-7890');
        $this->assertEquals(formatPhone('123.456.7890', 2), '123.456.7890');
        $this->assertEquals(formatPhone('123.456.7890', 3), '123-456-7890');

    }

    function test_formatPhone_extensionFormats(){
        $this->assertEquals(formatPhone('123.456.7890x12345', 0, 0), '1234567890');
        $this->assertEquals(formatPhone('123.456.7890x12345', 0, 1), '1234567890 x12345');
        $this->assertEquals(formatPhone('123.456.7890x12345', 0, 2), '1234567890 ext12345');
    }

    function test_bool2Str_trueDefault(){
        $this->assertEquals(bool2str(TRUE), 'true');
    }
    function test_bool2Str_falseDefault(){
        $this->assertEquals(bool2str(FALSE), 'false');
    }
    function test_bool2Str_trueBit(){
        $this->assertEquals(bool2str(TRUE, TRUE),  '1');
    }
    function test_bool2Str_falseBit(){
        $this->assertEquals(bool2str(FALSE, TRUE), '0');
    }
    function test_bool2Str_trueString(){
        $this->assertEquals(bool2str(TRUE, FALSE), 'true');
    }
    function test_bool2Str_falseString(){
        $this->assertEquals(bool2str(FALSE, FALSE), 'false');
    }

    function test_str2Bool_true(){
        // True tests
        $this->assertTrue(str2bool(true),   '(bool)true === TRUE');
        $this->assertTrue(str2bool('true'), '(string)true === TRUE');
        $this->assertTrue(str2bool('yes'),  '(string)yes === TRUE');
        $this->assertTrue(str2bool('1'),    '(string)1 === TRUE');
        $this->assertTrue(str2bool('2'),    '(string)2 === TRUE');
        $this->assertTrue(str2bool(1),      '(int)1 === TRUE');
        $this->assertTrue(str2bool(2),      '(int)2 === TRUE');
    }
    function test_str2Bool_false(){
        // False tests
        $this->assertFalse(str2bool(false),   '(bool)false === FALSE');
        $this->assertFalse(str2bool('false'), '(string)true === FALSE');
        $this->assertFalse(str2bool('no'),    '(string)no === FALSE');
        $this->assertFalse(str2bool('0'),     '(string)0 === FALSE');
        $this->assertFalse(str2bool('-1'),    '(string)-1 === FALSE');
        $this->assertFalse(str2bool(0),       '(int)0 === FALSE');
        $this->assertFalse(str2bool(-1),      '(int)-1 === FALSE');
    }
    function test_str2Bool_null(){
        // Null tests
        $this->assertNull(str2bool('someText'));
        $this->assertNull(str2bool(array()));
        $this->assertNull(str2bool(new StdClass));
    }

    function test_normalizeArray_arrayInput(){
        $array = array(
            'a',
            ' b ',
            ' C  ',
            '123 abc     !@#',
            'a' => '123',
            'b' => ' abc        ');
        $this->assertEquals($array, normalizeArray($array));
    }
    function test_normalizeArray_jsonInput(){
        $array = array(
            'a',
            ' b ',
            ' C  ',
            '123 abc     !@#',
            'a' => '123',
            'b' => ' abc        ');
        $json  = json_encode($array);
        $this->assertEquals($array, normalizeArray($json));
    }
    function test_normalizeArray_csvInput(){
        $arrayOUT = array('a','b','c');
        $this->assertEquals($arrayOUT, normalizeArray('a, b    ,  c   '));
    }

    function test_dateToUnix(){
        $this->markTestIncomplete('Unknown valid cases');
    }

    function test_UnixToDate_WithCustomFormat(){
        $now = time();
        $this->assertEquals(unixToDate($now, 'D, d M Y H:i:s'), date('D, d M Y H:i:s', $now));
        $this->assertEquals(unixToDate($now, 'c'), date('c', $now));
        $this->assertEquals(unixToDate($now, 'r'), date('r', $now));
        $this->assertEquals(unixToDate($now, 'u'), date('u', $now));
    }
    function test_UnixToDate_WithDefaultFormat(){
        $time = mktime(0,0,0,1,20,2000);
        $this->assertEquals(unixToDate($time), '01/20/2000');

        $time = mktime(1,0,0,1,20,2000);
        $this->assertEquals(unixToDate($time), '01/20/2000 01:00 am');

        $time = mktime(1,2,0,1,20,2000);
        $this->assertEquals(unixToDate($time), '01/20/2000 01:02 am');

        $time = mktime(1,2,3,1,20,2000);
        $this->assertEquals(unixToDate($time), '01/20/2000 01:02:03 am');
    }

    function test_lc(){
        $this->assertEquals(lc('AbC123'), 'abc123');
    }

    function test_uc(){
        $this->assertEquals(uc('AbC123'), 'ABC123');
    }
}
