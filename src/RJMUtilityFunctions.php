<?php

//deep clone	
function cloneArray(array $array) {
	$toReturn = array();

	foreach($array as $key => $value) {
		if(is_array($value))
			$toReturn[$key] = cloneArray($value);
		elseif(is_object($value))
			$toReturn[$key] = clone $value;
		else
			$toReturn[$key] = $value;
	}

	return $toReturn;
}

function getRealIpAddr() {
	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		//check ip from share internet
		$ip=$_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		//to check ip is pass from proxy
		$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip=$_SERVER['REMOTE_ADDR'];
	}
	
	return $ip;
}

//compact stack trace output suitable for passing to logger
//I added the :.: prefix as a handle for a grep filter that I pipe tail through
function compactStackTrace($removeThisCall = true){
	$dbg = debug_backtrace();
	$r = '';
	
	if ($removeThisCall) array_shift($dbg);
	
	$fields = array('file', 'line', 'class', 'function');
		
	foreach ($dbg as $d){
		foreach ($fields as $f)
			$d[$f] =  nu($d[$f]) ?: 'N/A';
		$r .= "\n:.:{$d['file']}:{$d['line']}  {$d['class']} -> {$d['function']}";
	}
		
	$r .= "\n:.:\n";
	return $r;
}

//use equals method defined on object if available
//otherwise default to ===
function equals($a, $b){
	if (method_exists($a, 'equals'))
		return $a->equals($b);
	else
		return $a===$b;
}

function equivalent_in_array($needle, $haystack){
	foreach ($haystack as $h)
		if (equals($needle, $h))
			return true;
	return false;
}

/*** little experiment by Bill ***/

class Fluenter{
	
	private $o;
	private $methodsOnlyMode; // If TRUE, you can only call methods using this fluenter. Otherwise, you can assign to a property. TRUE by default.
	
	function __construct($o = null, $methodsOnlyMode = true) {
		$this->o = $o ?: new stdClass();
		$this->methodsOnlyMode = $methodsOnlyMode;
	}
	
	public function __call($name, $args){
		if (method_exists($this->o, $name))
			call_user_func_array(array($this->o, $name), $args);
		elseif ($this->methodsOnlyMode)
			throw new Exception("Method '{$name}' does not exist. If you are trying to assign to a property, set methodsOnlyMode to FALSE.");
		else 
			$this->o->$name = reset($args);
			
		return $this;
	}
	
	public function __get($member){
		if ($member == 'exit')
			return $this->o;		
		throw new Exception("Fluenter does not support getting properties.");
	}	
}

function fluent($o, $methodsOnly = true){
	return new Fluenter($o, $methodsOnly);
}

class ArrayMapper{
	private $array;
	
	public function __construct(array $array){
		$this->array = $array;
	}

	public function __call($name, $args){
		$this->array = array_map(
			function($o) use ($name, $args){
				if (method_exists($o, $name))
					return call_user_func_array(array($o, $name), $args);
				else
					throw new Exception("Method '{$name}' does not exist.");
			}, 
			$this->array);
	
		return $this;
	}

	
	public function __get($member){		
		if ($member == 'exit')
			return $this->array;
		throw new Exception("ArrayMapper does not support getting properties.");
	}	
	
}

//usage example: 
//assuming that $dashboardItems is an array of DashboardItems the line below will reurn an array of all their ids
//$dashboardItemIds = mapArray($dashboardItems)->getId()->exit;
function mapArray($array){
	return new ArrayMapper($array);
}

/************************/

function nu(&$v){
	return isset($v) ? $v : null;
}

function startsWith($haystack, $needle, $case=true) {
	if (is_array($needle)){
		foreach ($needle as $n)
			if (startsWith($haystack, $n))
				return $n;
		return false;
	}
				
	if ($case)
		return strncmp($haystack, $needle, mb_strlen($needle)) == 0;
	else
		return strncasecmp($haystack, $needle, mb_strlen($needle)) == 0;
}

function endsWith($haystack, $needle, $case=true) {
	if (is_array($needle)){
		foreach ($needle as $n)
			if (endsWith($haystack, $n))
				return $n;
		return false;
	}	
	return startsWith(strrev($haystack),strrev($needle),$case);
}

// psuedo example: array_parallel([1, 2, 3], [4, 5, 6]) = [[1,4], [2,5], [3,6]]
function array_parallel(){
	$r = array();
	$args = func_get_args();
	$keys = array();
	foreach ($args as $a)
		$keys = array_merge($keys, array_keys($a));
	$keys = array_unique($keys);

	foreach ($keys as $k){
		$i = array();
		foreach ($args as $a)
			$i[] = nu($a[$k]);
		$r[$k] = $i;
	}
	return $r;	
}

function rotate_matrix($matrix){
	return call_user_func_array('array_parallel', $matrix);
}

/* print_r replacement */
function _pr($o, $max=null, $print=true){
	$r = __pr($o, 0, $max);
	if ($print){
		echo "<pre>$r</pre>";
		return $o;
	}else
		return $r;
}

//recursive function used by _pr (above)
//modify this to provide custom outputs for specific classes
function __pr($o, $depth, $max){	
		
	if (!is_null($max) && ($depth > $max)) return '-=[limit reached]=-';
	
	if (is_a($o, 'Logger'))
		return "[Logger]";  
	if (is_bool($o))
		return $o ? '[TRUE]' : '[FALSE]';
	if (is_a($o, 'Closure'))
		return '[Closure]';
		
	
	$c = "array";
	if (is_object($o)){
		$c = get_class($o);
		$o = (array)($o);
	}
	
	if (is_array($o)){
		$depth++;
		$indent = '';
		for($i=0; $i<$depth; $i++)
			$indent .= "  ";		
		$r = array();
		$r[] = "{$c}\n";		
		foreach ($o as $k => $v){
			$k = ltrim($k, chr(0)); //casting from array prepends class name and NULL chars to property name. This looks a little better
			$k = str_replace(chr(0), '.', $k); 
			$r[] = "{$indent}[$k] => " . __pr($v, $depth, $max) . "\n";				
		}
		return implode('', $r);
	}else
		return print_r($o, true);		
}

// turns this: array(1,2,array(3,4, array(5,6,7), 8), 9);
// into this : array(1, 2, 3, 4, 5, 6, 7, 8, 9);
function array_flatten($array){
	if (is_array($array))
		return $array ?
			call_user_func_array('array_merge', array_map('array_flatten', $array))
			: array();
	return array($array);
}

/* simple profiling functions */

function _timerStart($key){
	global $_gTimers;
	
	if (!$_gTimers) $_gTimers = array();
	
	if (empty($_gTimers[$key]))
		$_gTimers[$key] = array(microtime(true),0, 1);
	else{
		$_gTimers[$key][0] = microtime(true);
		$_gTimers[$key][2] = 1;
	}
	
	return $_gTimers[$key][0];
}

function _timerDuration($key){
	global $_gTimers;
	
	if (!$_gTimers) return 0;
	
	if ($_gTimers[$key][2])	
		return (microtime(true) - $_gTimers[$key][0]) + $_gTimers[$key][1];
	else
		return $_gTimers[$key][1];
}

function _timerDurationAndReset($key) {
	$time = _timerDuration($key);
	_timerReset($key);
	
	return $time;
}

function _timerReset($key) {
	global $_gTimers;

	if(!$_gTimers) return 0;

	unset($_gTimers[$key]);
}

function _timerStop($key){
	global $_gTimers;
	
	if (!$_gTimers) return 0;

	$_gTimers[$key][2] = 0;
	$_gTimers[$key][1] += microtime(true) - $_gTimers[$key][0];
		
	return $_gTimers[$key][1];
}

function _timerPrint($key){
	echo " $key : " . _timerDuration($key) . "  ";
}

function _timerGetStopped($min = 0){
	global $_gTimers;
	
	$r = array();
	
	foreach ($_gTimers as $k => $v)
		if (!$v[2] && ($v[1] > $min))
			$r[$k] = $v[1];
	
	return $r;
}

function _timerPrintAllStopped($min = 0){
	foreach (_timerGetStopped($min) as $key => $timer){
		_timerPrint($key);
		echo "\n";
	}
}

/* END simple profiling functions */

function valuesAreHex(array $candidates) {
	if(!count($candidates))
		return false; //can't know
	foreach($candidates as $candidate) {
		if(mb_strlen(preg_replace("/[0-9a-f]/","",$candidate)) != 0) 
			return false;
	}
	return true;
}

function stdClassMerge(stdClass $obj1, stdClass $obj2) {
	return (object) array_merge((array) $obj1, (array) $obj2);
}

function xmlEncode($string) {
	return str_replace ( array ( '&', '"', "'", '<', '>' ), array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;' ), $string );
}

function isExpired($month, $year) {
	$twoDigitMonth = str_pad($month, 2, "0", STR_PAD_LEFT);
	$expdate = strtotime("+1 month", strtotime($year."-".$twoDigitMonth."-01"));
	return $expdate<time();
}



function niceDurationOfSeconds($seconds){
	if($seconds === 0) //special case prevents div by zero below
		return "0 seconds";
	
	$per = array(
		'decade' => 60*60*24*356*10,
		'year' => 60*60*24*356,
		'month' => 60*60*24*(365/12),
		'week' => 60*60*24*7, 
		'day' => 60*60*24, 
		'hour' => 60*60, 
		'minute' => 60, 
		'second' => 1		
		);
	
	foreach ($per as $k => $v){
		if ($seconds >= $v){
			$n = round($seconds/$v);
			$err = abs($seconds-($n*$v)) / $seconds;
			if ($err < 0.5)
				return "$n $k" . (($n==1) ? '':'s');
		}
	}
}

function niceDuration($d1, $d2=null){
	$d = strtotime($d1);
	if (!$d) return $d1; 
	
	if ($d2==null)
		$d2 = date('Y-M-d H:i:s');

	$d2 = strtotime($d2);		
	$dd = abs($d - $d2);
	
	return niceDurationOfSeconds($dd);		
}

//given two objects, this function will iterate the objects' properties recursively and output any differences
function diffProperties($a, $b, $depth=0){
	$output = array();
	
	$hasProperties = function($o){ return is_object($o) || is_array($o); };
	
	if (!$hasProperties($a))
		$a = new stdClass();
	if (!$hasProperties($b))
		$b = new stdClass();
	
	
	$union = array_merge(get_object_vars(is_array($a) ? (object) $a : $a), get_object_vars(is_array($b) ? (object) $b : $b));
	ksort($union);
	
	$indentString = "\t";
	$indent = '';
	for($i=0; $i<$depth; $i++)
		$indent .= $indentString;

	foreach ($union as $k => $v){
		$v1 = isset($a->$k) ? $a->$k : '**NOT SET**';
		$v2 = isset($b->$k) ? $b->$k : '**NOT SET**';
		
		$keyString = "{$indent}[{$k}] =>\n";
		
		if ($hasProperties($v1) || $hasProperties($v2)){
			if ($sub = diffProperties ($v1, $v2, $depth+1))
				$output[] = $keyString . $sub;
		}else{
			$v1Type = gettype($v1);
			$v2Type = gettype($v2);
			if ($v1 !== $v2)
				$output[] = "{$keyString}{$indent}{$indentString}{$v1Type}::{$v1}\n{$indent}{$indentString}{$v2Type}::{$v2}";
		}
	}

	return implode("\n", $output);
}

// JSON encode is choking on UTF8 characters so I grabbed an alternate implementation off the web and use it here
// We may want to use it everywhere. I don't think we've been able to reproduce this on the VM, which might be a clue to the root cause.
function alternate_json_encode($a = false) {

	if (is_null($a))
		return 'null';
	if ($a === false)
		return 'false';
	if ($a === true)
		return 'true';
	if (is_scalar($a)) {
		if (is_float($a)) {
			// Always use "." for floats.
			return floatval(str_replace(",", ".", strval($a)));
		}

		// Replacing special chartacers:  It's dangerous to go alone! Take this - http://php.net/manual/en/regexp.reference.unicode.php
		// And this: http://www.codeproject.com/Articles/37735/Searching-Modifying-and-Encoding-Text
		if (is_string($a)) {
			static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"', "\0", "\v", "\e", "\p{Cc}"), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"', '\u0000', '\u000b','\u001b', '\u009b'));
			return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
		}
		else
			return $a;
	}
	if ($a instanceof JsonSerializable) {
		return alternate_json_encode($a->jsonSerialize());
	}
	$isList = true;
	for ($i = 0, reset($a); $i < count($a); $i++, next($a)) {
		if (key($a) !== $i) {
			$isList = false;
			break;
		}
	}
	$result = array();
	if ($isList) {
		foreach ($a as $v)
			$result[] = alternate_json_encode($v);
		return '[' . join(',', $result) . ']';
	} else {
		foreach ($a as $k => $v)
			$result[] = alternate_json_encode(''.$k) . ':' . alternate_json_encode($v);
		return '{' . join(',', $result) . '}';
	}
}

//this function takes a mutable object and returns a new immuable copy of that object
function freeze($model) {
	if(is_null($model))
		throw new Exception("Can't freeze a null object");

	$immutableClassName = $model->getImmutableClassName();
	return $immutableClassName::createFromPropertyObject($model->getPropertyObject());
}

//this function takes an immutable object and returns a new mutable copy of that object
//mutable objects should be used in the tightest scope possible -- try not to pass them unless performance requirements demand it
function mutable($model) {
	if(is_null($model))
		throw new Exception("Can't unfreeze a null object");

	$mutableClassName = $model->getMutableClassName();
	return $mutableClassName::createFromPropertyObject($model->getPropertyObject());
}

class Mutator {

	private $model;

	function __construct(Model $model) {
		$this->model = mutable($model);
	}

	public function __call($functionName, $args){
		if(!method_exists($this->model, $functionName)) {
			$class = get_class($this->model);
			throw new Exception("Method '$functionName' does not exist for object of class $class.");
		}
		call_user_func_array(array($this->model, $functionName), $args);
		return $this;
	}

	public function __get($member){
		if($member == 'freeze')
			return freeze($this->model);
		throw new Exception("Mutator does not support getting properties.");
	}
}

function mut(Model $model) {
	return new Mutator($model);
}

function format_bytes($a_bytes)
{
    if ($a_bytes < 1024) {
        return $a_bytes .' Bytes';
    } elseif ($a_bytes < 1048576) {
        return round($a_bytes / 1024, 2) .' KB';
    } elseif ($a_bytes < 1073741824) {
        return round($a_bytes / 1048576, 2) . ' MB';
    } elseif ($a_bytes < 1099511627776) {
        return round($a_bytes / 1073741824, 2) . ' GB';
    } elseif ($a_bytes < 1125899906842624) {
        return round($a_bytes / 1099511627776, 2) .' TB';
    } elseif ($a_bytes < 1152921504606846976) {
        return round($a_bytes / 1125899906842624, 2) .' PB';
    } elseif ($a_bytes < 1180591620717411303424) {
        return round($a_bytes / 1152921504606846976, 2) .' EB';
    } elseif ($a_bytes < 1208925819614629174706176) {
        return round($a_bytes / 1180591620717411303424, 2) .' ZB';
    } else {
        return round($a_bytes / 1208925819614629174706176, 2) .' YB';
    }
}


function is_assoc($arr) { //returns true if $arr is an associative array
    return (is_array($arr) && count(array_filter(array_keys($arr),'is_string')) == count($arr));
}

function json_response(ControllerResponse $cr, $json, $status = 200) {
	return $cr->data(array('json' => $json))->status($status)->view('json');
}

function convertStringToBoolean($string) {
	if($string == 'true' || $string == '1')
		return true;
	elseif($string == 'false' || $string == '0')
		return false;
	return null;
}

function makeClosureWrapper($returnValue) {
	return function () use ($returnValue) {
		return $returnValue;
	};
}

// usage:  list($id, $name, $email) = decomposeAssocArray($array, 'id', 'name', 'email')
function decomposeAssocArray(){
	$args = func_get_args();
	$array = array_shift($args);

	$r = array();
	foreach ($args as $key)
		$r[] = $array[$key];
	
	return $r;
}

function slow_death($count, $message=''){
	global $slow_death_count;

	if (empty($slow_death_count))
		$slow_death_count = 1000;

	if ($count < $slow_death_count)
		$slow_death_count = $count;

	$slow_death_count--;

	if ($slow_death_count<1)
		die($message);
}


/*
This function should be used, instead of the require_once statement, whenever requiring a file
from a composer package. It temporarily replaces the include path with one limited to the package
directory and the vendor directory. This allows references between packages to always be resolved
correctly.

Additionally, attempting to require a non-existant file will return a catchable exception w/
stack trace, instead of a fatal error.

Example: composer_require_once('rjmetrics/phinch','ImmutableModel.php');

*/
function composer_require_once($lib, $filename){
	$originalPath = get_include_path();
	$libPath = "vendor/$lib";
	set_include_path("vendor:$libPath");
	$requiredFile = "src/$filename";

	if (stream_resolve_include_path($requiredFile))
		require_once $requiredFile;
	else
		throw new Exception("composer_require_once -> require_once({$requiredFile}): failed to open stream. Include path = \"" . get_include_path() . "\"");

	set_include_path($originalPath);
}

/**
 * Checks a json object for missing fields and that fields are the correct type.
 *
 * $fields is an array mapping field names to an array of constraints. The
 * restraints can be:
 *
 *     - 'required' (boolean, default: false)
 *     - 'numeric' (boolean, default: false)
 *
 * For example:
 *
 *     $fields = [
 *         'fieldname' => ['required' => true],
 *         'otherfield' => ['required' => false, 'numeric' => true],
 *     ];
 *
 * Will return a map of fields to a human-readable error message about the field.
 */
function findJsonErrors(array $fields, stdClass $json) {
	$errors = [];

	foreach($fields as $field => $constraints) {
		$required = isset($constraints['required']) && $constraints['required'];
		$numeric = isset($constraints['numeric']) && $constraints['numeric'];

		if(!isset($json->$field)) {
			if($required) {
				$errors[$field] = 'This field is missing';
			}
		}
		elseif($numeric && !is_numeric($json->$field)) {
			$errors[$field] = "$field must be numeric, got: " . $json->$field;
		}
	}

	return $errors;
}


?>
