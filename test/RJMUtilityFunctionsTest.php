<?php
dirname(__DIR__);
require_once dirname(__DIR__) . '/src/RJMUtilityFunctions.php';

class RJMUtilityFunctionsTest extends PHPUnit_Framework_TestCase {

	public function test_array_get() {
		$this->assertSame('value', array_get(['key' => 'value'], 'key'));
		$this->assertNull(array_get(['key' => 'value'], 'nope'));
		$this->assertSame('default', array_get(['key' => 'value'], 'nope', 'default'));
		$this->assertSame(null, array_get(['key' => null], 'key', 0));
	}

	public function test_get_in() {
		$to_array = function($json) { return json_decode($json, true); };
		$to_object = function($json) { return json_decode($json, false); };

		$xforms = ['array' => $to_array,
				   'object' => $to_object];

		$this->assertEquals(get_in(null, []), null);
		$this->assertEquals(get_in(null, [0]), null);
		$this->assertEquals(get_in('string', []), 'string');
		$this->assertEquals(get_in('string', [0]), null);

		foreach($xforms as $type => $xform) {
			$this->assertEquals(get_in($xform('{}'), []), $xform('{}'), $type);
			$this->assertEquals(get_in($xform('{"a":"b"}'), []),
								$xform('{"a":"b"}'), $type);
			$this->assertEquals(get_in($xform('["a"]'), []), $xform('["a"]'));
			$this->assertEquals(get_in($xform('{"a":"b"}'), ["c"]), null, $type);
			$this->assertEquals(get_in($xform('{"a":"b"}'), ["b"]), null, $type);
			$this->assertEquals(get_in($xform('["a"]'), [0]), "a", $type);
			$this->assertEquals(get_in($xform('{"a": {"b": "c"}}'), ["a", "b"]), "c");
			$this->assertEquals(get_in($xform('{"a": {"b": "c"}}'), [0, 0]), null);
			$this->assertEquals(get_in($xform('{"a": {"a": {"a": "c"}}}'), ["a", "b"]), null);
			$this->assertEquals(get_in($xform('{"a": {"a": {"a": "c"}}}'), ["a", "a"]), $xform('{"a": "c"}'));
			$this->assertEquals(get_in($xform('{"a": {"a": {"a": "c"}}}'), ["a", "a", "a"]), "c");
			$this->assertEquals(get_in($xform('{"a": "b"}'), [0], "d"), "d");
			$this->assertInstanceOf('RuntimeException', get_in($xform('{"a": "b"}'), [0], new RuntimeException()));
		}
	}

	public function test_env() {
		$GLOBALS['environment'] = ["a" => "b", "c" => ["d" => "e"]];
		try {
			$out = env([0]);
		} catch (Exception $e) {
			$this->assertInstanceOf('OutOfBoundsException', $e);
		}

		$this->assertEquals(env([0], null), null);
		$this->assertEquals(env([0], "not found"), "not found");
		$this->assertEquals(env(['a']), 'b');
		$this->assertEquals(env(['c']), ['d' => 'e']);
		$this->assertEquals(env(['c', 'd']), 'e');
	}
}

?>
