<?php

class validateTest extends PHPUnit_Framework_TestCase {

	public function setup() {
		$this->time = new time();
	}

	public function teardown() {
		unset($this->validate);
	}

	public function test_toSeconds() {
		$this->assertEquals("48600",$this->time->toSeconds("1:30pm"));
		$this->assertEquals("48630",$this->time->toSeconds("1:30:30pm"));
		$this->assertEquals("48600",$this->time->toSeconds("13:30"));
		$this->assertEquals("48600",$this->time->toSeconds("1330"));
		$this->assertEquals("48630",$this->time->toSeconds("13:30:30"));
		$this->assertEquals("46800",$this->time->toSeconds("1pm"));
		$this->assertEquals("48630",$this->time->toSeconds("1:30:30.5pm"));
		$this->assertEquals("48630.5",$this->time->toSeconds("1:30:30.5pm",TRUE));
		$this->assertEquals("46800",$this->time->toSeconds("1pm",TRUE));
		$this->assertEquals("0",$this->time->toSeconds("24:00",TRUE));
		$this->assertEquals("0",$this->time->toSeconds("12:00am",TRUE));

		$this->assertFalse($this->time->toSeconds("25:00"));
		$this->assertFalse($this->time->toSeconds("Foo"));
		$this->assertFalse($this->time->toSeconds("1"));
	}

	public function test_convert_toSeconds() {

		$this->assertEquals("48600",$this->time->convert("1:30pm"));
		$this->assertEquals("48630",$this->time->convert("1:30:30pm"));
		$this->assertEquals("48600",$this->time->convert("13:30"));
		$this->assertEquals("48600",$this->time->convert("1330"));
		$this->assertEquals("48630",$this->time->convert("13:30:30"));
		$this->assertEquals("46800",$this->time->convert("1pm"));
		$this->assertEquals("48630",$this->time->convert("1:30:30.5pm"));
		$this->assertEquals("48630.5",$this->time->convert("1:30:30.5pm",TRUE));
		$this->assertEquals("46800",$this->time->convert("1pm",TRUE));
		$this->assertEquals("0",$this->time->convert("24:00",TRUE));
		$this->assertEquals("0",$this->time->convert("12:00am",TRUE));

		$this->assertFalse($this->time->convert("25:00"));
		$this->assertFalse($this->time->convert("Foo"));
		$this->assertFalse($this->time->convert("1"));

	}

	public function test_toTime() {

		$this->assertEquals("1:30:00pm",$this->time->toTime(48600,TRUE));
		$this->assertEquals("13:30:00",$this->time->toTime(48600,FALSE));
		$this->assertEquals("13:30:30",$this->time->toTime(48630,FALSE));
		$this->assertEquals("01:30",$this->time->toTime(48630,"h:i"));
		$this->assertEquals("01:30PM",$this->time->toTime(48630,"h:iA"));

	}

	public function test_convert_toTime() {

		$this->assertEquals("1:30:00pm",$this->time->convert(48600,TRUE));
		$this->assertEquals("13:30:00",$this->time->convert(48600,FALSE));
		$this->assertEquals("13:30:30",$this->time->convert(48630,FALSE));
		$this->assertEquals("01:30",$this->time->convert(48630,"h:i"));
		$this->assertEquals("01:30PM",$this->time->convert(48630,"h:iA"));
		
	}

}
?>