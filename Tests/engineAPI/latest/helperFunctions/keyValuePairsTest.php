<?php
class keyValuePairsTest extends PHPUnit_Framework_TestCase {
    function testItCanTakeNoData(){
        $keyValuePairs = new keyValuePairs();
        $this->assertInstanceOf('keyValuePairs', $keyValuePairs);
    }
    function testItCanTakeAnArray(){
        $keyValuePairs = new keyValuePairs(array());
        $this->assertInstanceOf('keyValuePairs', $keyValuePairs);
    }
    function testItCanTakeAnObject(){
        $keyValuePairs = new keyValuePairs(new stdClass);
        $this->assertInstanceOf('keyValuePairs', $keyValuePairs);
    }
    function testItSupportsSizeof(){
        $keyValuePairs = new keyValuePairs(array(1));
        $this->assertEquals(1, sizeof($keyValuePairs));

        $keyValuePairs = new keyValuePairs(array(1,2));
        $this->assertEquals(2, sizeof($keyValuePairs));

        $keyValuePairs = new keyValuePairs(array(1,2,3));
        $this->assertEquals(3, sizeof($keyValuePairs));
    }
    function testItStartsWithZeroElements(){
        $keyValuePairs = new keyValuePairs();
        $this->assertEquals(0, sizeof($keyValuePairs));
    }
    function testItCanBeIteratedOver(){
        $keyValuePairs = new keyValuePairs(array('a'=>1));
        foreach($keyValuePairs as $k => $v){
            $this->assertEquals('a',$k);
            $this->assertEquals(1,$v);
        }
    }
    function testItCanBeSerialized(){
        $keyValuePairs = new keyValuePairs(array('a'=>1));
        $ser = serialize($keyValuePairs);
        $newObject = unserialize($ser);
        $this->assertEquals(1, $newObject['a']);
    }
    function testItCanBeTreatedAsArray_read(){
        $keyValuePairs = new keyValuePairs(array('a'=>1));
        $this->assertEquals(1, $keyValuePairs['a']);
    }
    function testItCanBeTreatedAsArray_write(){
        $keyValuePairs = new keyValuePairs();
        $keyValuePairs['a'] = 1;
        $this->assertEquals(1, $keyValuePairs['a']);
    }
    function testItCanBeTreatedAsArray_delete(){
        $keyValuePairs = new keyValuePairs(array('a'=>1));
        $this->assertEquals(1, sizeof($keyValuePairs));
        unset($keyValuePairs['a']);
        $this->assertEquals(0, sizeof($keyValuePairs));
    }
    function testItCanBeTreatedAsArray_append(){
        $keyValuePairs = new keyValuePairs();
        $keyValuePairs[] = 'a';
        $this->assertEquals(1, sizeof($keyValuePairs));
    }
}
