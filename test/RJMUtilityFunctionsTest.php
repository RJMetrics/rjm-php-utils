<?php

print dirname(__DIR__);
require_once dirname(__DIR__) . '/src/RJMUtilityFunctions.php';

class RJMUtilityFunctionsTest extends PHPUnit_Framework_TestCase {

	/**
	 * @test
	 */
	public function array_get_tests() {
		$this->assertSame('value', array_get(['key' => 'value'], 'key'));
		$this->assertNull(array_get(['key' => 'value'], 'nope'));
		$this->assertSame('default', array_get(['key' => 'value'], 'nope', 'default'));
		$this->assertSame(null, array_get(['key' => null], 'key', 0));
	}

}

?>
