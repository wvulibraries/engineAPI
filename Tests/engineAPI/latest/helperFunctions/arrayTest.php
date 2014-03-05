<?php

class arrayTest extends PHPUnit_Framework_TestCase {

	public function test_array_unique_recursive_TestSingleDimensionalArray() {

		$arrayInput  = array("this", "that", "another", "this", "something", "else");
		$arrayOutput = array(0 => "this", 1 => "that", 2 => "another", 4 => "something", 5 => "else");
		$result      = array_unique_recursive($arrayInput);

		$this->assertEquals($arrayOutput,$result);

	}

	public function test_array_unique_recursive_TestMultiDimensionalArray() {

		$arrayInput  = array("this", array("a", "b", "c", "a", "d", "e"), "another", "this", "something", "else");
		$arrayOutput = array(0 => "this", 1 => array(0 => "a", 1 => "b", 2 => "c", 4=> "d", 5 => "e"), 2 => "another", 4 => "something", 5 => "else");
		$result      = array_unique_recursive($arrayInput);

		$this->assertEquals($arrayOutput,$result);

	}

	public function test_array_diff_assoc_recursive_TestSingleDimensionalArray() {

		$array1 = array("a" => "green", "b" => "brown", "c" => "blue", "red");
		$array2 = array("a" => "green", "yellow", "red");
		$result = array("b" => "brown", "c" => "blue", 0 => "red");

		$this->assertEquals($result,array_diff_assoc_recursive($array1,$array2));

	} 

	public function test_array_diff_assoc_recursive_TestSingleMultiArray() {

		$array1 = array("a" => "green", "b" => "brown", "c" => "blue", "red", "d" => array("a", "b", "c"));
		$array2 = array("a" => "green", "yellow", "red","d" => array("b"));
		$result = array("b" => "brown", "c" => "blue", 0 => "red", "d" => array(0 => "a", 2 => "c", 1 => "b"));

		$this->assertEquals($result,array_diff_assoc_recursive($array1,$array2));

	} 

	public function test_arrayNextIndex_RetrievesTheNextIndexNoLooping() {

		$array  = array("a" => "this", "b" => "that", "c" => "another", "d" => "this", "e" => "something", "f" => "else");

		$this->assertEquals("d",array_nextIndex($array,"c",FALSE));
		$this->assertEquals(NULL,array_nextIndex($array,"f",FALSE));
	}

	public function test_arrayNextIndex_RetrievesTheNextIndexLooping() {

		$array  = array("a" => "this", "b" => "that", "c" => "another", "d" => "this", "e" => "something", "f" => "else");

		$this->assertEquals("a",array_nextIndex($array,"f",TRUE));

	}

	public function test_arrayPrevIndex_RetrievesTheNextIndexNoLooping() {

		$array  = array("a" => "this", "b" => "that", "c" => "another", "d" => "this", "e" => "something", "f" => "else");

		$this->assertEquals("b",array_prevIndex($array,"c",FALSE));
		$this->assertEquals(NULL,array_prevIndex($array,"a",FALSE));
	}

	public function test_arrayPrevIndex_RetrievesTheNextIndexLooping() {

		$array  = array("a" => "this", "b" => "that", "c" => "another", "d" => "this", "e" => "something", "f" => "else");

		$this->assertEquals("f",array_prevIndex($array,"a",TRUE));

	}

	public function test_getFirstIndex_returnsFirstIndexOfTheArray() {
		$array  = array("a" => "this", "b" => "that", "c" => "another", "d" => "this", "e" => "something", "f" => "else");
		$this->assertEquals("a",array_getFirstIndex($array));
	}

	public function test_getFirstIndex_returnsFirstIndexOfTheArray_InvalidChecks() {
		$this->assertFalse(array_getFirstIndex(array()));
		$this->assertFalse(array_getFirstIndex("test"));
	}

	public function test_getLastIndex_returnsFirstIndexOfTheArray() {
		$array  = array("a" => "this", "b" => "that", "c" => "another", "d" => "this", "e" => "something", "f" => "else");
		$this->assertEquals("f",array_getLastIndex($array));
	}

	public function test_getLastIndex_returnsFirstIndexOfTheArray_InvalidChecks() {
		$this->assertFalse(array_getLastIndex(array()));
		$this->assertFalse(array_getLastIndex("test"));
	}

	public function test_simpleXMLToArray_defaults() {

		$xml    = '<root><node attribute="foo">Test Data</node><node attribute="bar">Data Test</node></root>';
		$simple = new SimpleXMLElement($xml);
		
		$match                         = array();
		$match["node"]                 = array();
		$match["node"][0][0]           = "Test Data";
		$match["node"][0]["attribute"] = "foo";
		$match["node"][1][0]           = "Data Test";
		$match["node"][1]["attribute"] = "bar";

		$this->assertEquals($match,simpleXMLToArray($simple));

	}

	public function test_simpleXMLToArray_Attributes() {

		$xml    = '<root><node attribute="foo">Test Data</node><node attribute="bar">Data Test</node></root>';
		$simple = new SimpleXMLElement($xml);
		
		$match                           = array();
		$match["node"]                   = array();
		$match["node"][0][0]             = "Test Data";
		$match["node"][0]["@attributes"] = array("attribute" => "foo");
		$match["node"][1][0]             = "Data Test";
		$match["node"][1]["@attributes"] = array("attribute" => "bar");

		$this->assertEquals($match,simpleXMLToArray($simple,TRUE));

	}

	public function test_simpleXMLToArray_Children() {

		$xml    = '<root><node attribute="foo">Test Data</node><node attribute="bar">Data Test</node></root>';
		$simple = new SimpleXMLElement($xml);

		$match                                      = array();
		$match[0]                                   = array();
		$match["@children"]                         = array();
		$match["@children"]['node']                 = array();
		$match["@children"]['node'][0][0]           = "Test Data";
		$match["@children"]['node'][0]["attribute"] = "foo";
		$match["@children"]['node'][1][0]           = "Data Test";
		$match["@children"]['node'][1]["attribute"] = "bar";


		$this->assertEquals($match,simpleXMLToArray($simple,NULL,TRUE));

	}

	public function test_simpleXMLToArray_Value() {

		$xml    = '<root><node attribute="foo">Test Data</node><node attribute="bar">Data Test</node></root>';
		$simple = new SimpleXMLElement($xml);
		
		$match                         = array();
		$match["node"]                 = array();
		$match["node"][0]["@values"]   = "Test Data";
		$match["node"][0]["attribute"] = "foo";
		$match["node"][1]["@values"]   = "Data Test";
		$match["node"][1]["attribute"] = "bar";

		$this->assertEquals($match,simpleXMLToArray($simple,NULL,NULL,TRUE));

	}

	public function test_simpleXMLToArray_AttributesChildren() {

		$xml    = '<root><node attribute="foo">Test Data</node><node attribute="bar">Data Test</node></root>';
		$simple = new SimpleXMLElement($xml);
		
		$match                                        = array();
		$match[0]                                     = array();
		$match["@children"]                           = array();
		$match["@children"]['node']                   = array();
		$match["@children"]['node'][0][0]             = "Test Data";
		$match["@children"]['node'][0]["@attributes"] = array("attribute" => "foo");
		$match["@children"]['node'][1][0]             = "Data Test";
		$match["@children"]['node'][1]["@attributes"] = array("attribute" => "bar");


		$this->assertEquals($match,simpleXMLToArray($simple,TRUE,TRUE));

	}

	public function test_simpleXMLToArray_AttributesChildrenValues() {

		$xml    = '<root><node attribute="foo">Test Data</node><node attribute="bar">Data Test</node></root>';
		$simple = new SimpleXMLElement($xml);
		
		$match                                        = array();
		$match[0]                                     = array();
		$match["@children"]                           = array();
		$match["@children"]['node']                   = array();
		$match["@children"]['node'][0]["@values"]     = "Test Data";
		$match["@children"]['node'][0]["@attributes"] = array("attribute" => "foo");
		$match["@children"]['node'][1]["@values"]     = "Data Test";
		$match["@children"]['node'][1]["@attributes"] = array("attribute" => "bar");


		$this->assertEquals($match,simpleXMLToArray($simple,TRUE,TRUE,TRUE));

	}

	public function test_array_merge_recursive_overwrite_SingleDimension() {
		$array1 = array("a" => "green", "b" => "brown", "c" => "blue", "red");
		$array2 = array("a" => "green", "yellow", "red");
		$match  = array("a" => "green", "b" => "brown", "c" => "blue", 0 => "red", 1 => "yellow", 2=> "red");

		$this->assertEquals($match,array_merge_recursive_overwrite($array1, $array2));

	}

	public function test_array_merge_recursive_overwrite_MultiDimension() {
		$array1 = array("multi" => array("a", "b", "c"), "a" => "green", "b" => "brown", "c" => "blue", "red");
		$array2 = array("multi" => array("d", "b", "e"), "a" => "green", "yellow", "red");
		$match  = array("multi" => array("a", "b", "c", "d", "b", "e"), "a" => "green", "b" => "brown", "c" => "blue", 0 => "red", 1 => "yellow", 2=> "red");

		$this->assertEquals($match,array_merge_recursive_overwrite($array1, $array2));

	}

	public function test_array_peak_validArrayPop() {

		$array1     = array("a" => "green", "b" => "brown", "c" => "blue", "red");
		$array1Copy = $array1;

		$this->assertEquals("red",array_peak($array1,"end"));
		$this->assertEquals("red",array_peak($array1,"right"));
		$this->assertEquals("red",array_peak($array1,"top"));
		$this->assertEquals($array1Copy,$array1);

	}

	public function test_array_peak_validArrayShift() {

		$array1     = array("a" => "green", "b" => "brown", "c" => "blue", "red");
		$array1Copy = $array1;

		$this->assertEquals("green",array_peak($array1,"start"));
		$this->assertEquals("green",array_peak($array1,"left"));
		$this->assertEquals("green",array_peak($array1,"bottom"));
		$this->assertEquals($array1Copy,$array1);

	}

	public function test_array_peak_invalidArray() {

		$this->assertEquals(NULL,array_peak("array","start"));

	}


	public function testArrayGet()
	{
		$array = array('names' => array('developer' => 'taylor'));
		$this->assertEquals('taylor', array_get($array, 'names.developer'));
		$this->assertEquals('dayle', array_get($array, 'names.otherDeveloper', 'dayle'));
	}


	public function testArraySet()
	{
		$array = array();
		array_set($array, 'names.developer', 'taylor');
		$this->assertEquals('taylor', $array['names']['developer']);
	}


	public function testArrayUnset()
	{
		$array = array('names' => array('developer' => 'taylor', 'otherDeveloper' => 'dayle'));
		array_unset($array, 'names.developer');
		$this->assertFalse(isset($array['names']['developer']));
		$this->assertTrue(isset($array['names']['otherDeveloper']));
	}
}

?>