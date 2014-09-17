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

	/**
	 * @test
	 */
	public function splitS3Path_s3() {
		$this->assertSame(
			[
				'prefix' => 's3',
				'bucket' => 'mybucket',
				'key' => 'some/path',
			],
			splitS3Path('s3://mybucket/some/path'));
	}

	/**
	 * @test
	 */
	public function splitS3Path_s3n() {
		$this->assertSame(
			[
				'prefix' => 's3n',
				'bucket' => 'another-bucket_with^stuff',
				'key' => 'some/path',
			],
			splitS3Path('s3n://another-bucket_with^stuff/some/path'));
	}

	/**
	 * @test
	 * @expectedException UnexpectedValueException
	 */
	public function splitS3Path_invalidPrefix() {
		splitS3Path('s3x://x/y/z');
	}

	/**
	 * @test
	 * @expectedException UnexpectedValueException
	 */
	public function splitS3Path_malformed() {
		splitS3Path('s3n:/&/x/y/z');
	}

}

?>
