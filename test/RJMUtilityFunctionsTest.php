<?php

print dirname(__DIR__);
require_once dirname(__DIR__) . '/src/RJMUtilityFunctions.php';

class RJMUtilityFunctionsTest extends PHPUnit_Framework_TestCase {

	/**
	 * @test
	 */
	public function array_get_tests() {
		$this->assertEquals('value', array_get(['key' => 'value'], 'key'));
		$this->assertNull(array_get(['key' => 'value'], 'nope'));
		$this->assertEquals('default', array_get(['key' => 'value'], 'nope', 'default'));
	}

}

?>
