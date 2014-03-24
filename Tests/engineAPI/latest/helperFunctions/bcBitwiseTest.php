<?php

class bcBitwiseTest extends PHPUnit_Framework_TestCase {

	function test_bcAND() {
		$this->assertEquals(0, bcBitwise::bcAND(0, 5));
		$this->assertEquals(1, bcBitwise::bcAND(1, 5));
		$this->assertEquals(0, bcBitwise::bcAND(2, 5));
		$this->assertEquals(4, bcBitwise::bcAND(4, 5));
		$this->assertEquals(0, bcBitwise::bcAND(8, 5));
	}

	function test_bcOR() {
		$this->assertEquals(5,  bcBitwise::bcOR(0, 5));
		$this->assertEquals(5,  bcBitwise::bcOR(1, 5));
		$this->assertEquals(7,  bcBitwise::bcOR(2, 5));
		$this->assertEquals(5,  bcBitwise::bcOR(4, 5));
		$this->assertEquals(13, bcBitwise::bcOR(8, 5));
	}

	function test_bcXOR() {
		$this->assertEquals(5,  bcBitwise::bcXOR(0, 5));
		$this->assertEquals(4,  bcBitwise::bcXOR(1, 5));
		$this->assertEquals(7,  bcBitwise::bcXOR(2, 5));
		$this->assertEquals(1,  bcBitwise::bcXOR(4, 5));
		$this->assertEquals(13, bcBitwise::bcXOR(8, 5));
	}

	function test_bcLeftShift() {
		$this->assertEquals(2,  bcBitwise::bcLeftShift(1, 1));
		$this->assertEquals(4,  bcBitwise::bcLeftShift(1, 2));
		$this->assertEquals(8,  bcBitwise::bcLeftShift(1, 3));
		$this->assertEquals(16, bcBitwise::bcLeftShift(1, 4));

		$this->assertEquals(-2,  bcBitwise::bcLeftShift(-1, 1));
		$this->assertEquals(-4,  bcBitwise::bcLeftShift(-1, 2));
		$this->assertEquals(-8,  bcBitwise::bcLeftShift(-1, 3));
		$this->assertEquals(-16, bcBitwise::bcLeftShift(-1, 4));
	}

	function test_bcRightShift() {
		$this->assertEquals(4, bcBitwise::bcRightShift(8, 1));
		$this->assertEquals(2, bcBitwise::bcRightShift(8, 2));
		$this->assertEquals(1, bcBitwise::bcRightShift(8, 3));
		$this->assertEquals(0, bcBitwise::bcRightShift(8, 4));

		$this->assertEquals(-4, bcBitwise::bcRightShift(-8, 1));
		$this->assertEquals(-2, bcBitwise::bcRightShift(-8, 2));
		$this->assertEquals(-1, bcBitwise::bcRightShift(-8, 3));
		$this->assertEquals(0, bcBitwise::bcRightShift(-8, 4));
	}

	function test_dec2base() {
		$this->assertEquals('0100', bcBitwise::dec2base(4, 2));
		$this->assertEquals('0100', bcBitwise::dec2base(4, 2, '01'));
	}

	function test_base2dec() {
		$this->assertEquals('4', bcBitwise::base2dec('0100', 2));
		$this->assertEquals('4', bcBitwise::base2dec('0100', 2, '0123456789'));
	}

	function test_digits() {
		$this->assertEquals('01', bcBitwise::digits(2));
		$this->assertEquals('0123456789', bcBitwise::digits(10));
		$this->assertEquals('0123456789abcdefghijklmnopqrstuv', bcBitwise::digits(32));
		$this->assertEquals('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_', bcBitwise::digits(64));
	}
}

?>
