<?php

print dirname(__DIR__);
require_once dirname(__DIR__) . '/../src/validation/Validator.php';

class ValidatorTest extends PHPUnit_Framework_TestCase {

	/**
	 * @test
	 */
	public function validate_length() {
		$this->assertEquals(false, Validator::length(''));
		$this->assertEquals(false, Validator::length('abc', 1, 2));
		$this->assertEquals(true, Validator::length('a', 1, 3));
	}

	/**
	 * @test
	 */
	public function validate_numeric() {
		$this->assertEquals(false, Validator::numeric('abc'));
		$this->assertEquals(true, Validator::numeric('0xFF'));
		$this->assertEquals(true, Validator::numeric('03'));
	}

	/**
	 * @test
	 */
	public function validate_typematch() {
		$this->assertEquals(false, Validator::typematch(array(), 'Validator'));
		$this->assertEquals(true, Validator::typematch(new StdClass(), 'StdClass'));
	}

	/**
	 * @test
	 */
	public function validate_email() {
		$this->assertEquals(false, Validator::email('  <@example.foo'));
		$this->assertEquals(true, Validator::email('foo@example.com'));
	}

	/**
	 * @test
	 */
	public function validate_ip() {
		$this->assertEquals(false, Validator::ip('1.2.3.4.5'));
		$this->assertEquals(true, Validator::ip('127.0.0.1'));
	}

}

?>
