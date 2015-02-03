<?php

class Validator {

	public $invalid = array();	//2D array: first index is the name of either a property of the object
								//being validated (used by validate()) or the name of a custom test
								//performed outside of this class (used by addFailure());
								//value is an array of messages to display due to those failures
	private $validatable;

	public function __construct(Validatable $validatable){
		$this->validatable = new Propertizer($validatable);
	}

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $property A property of the object being validated
	 * @param unknown_type $message The error message to eventually display on failure
	 * @param unknown_type $test the name of a static function defined in this class
	 */
	public function validate($property, $message, $test){
		$value = $this->validatable->$property;

		$args = array_slice(func_get_args(), 3);
		array_unshift($args,$value);

		$valid = call_user_func_array(array($this,$test), $args);

		if(!$valid)
			$this->addFailure($property, $message);

		return $valid;
	}

	public function addFailure($failureName, $message, $id = null){
		if (!isset($this->invalid[$failureName]))
			$this->invalid[$failureName] = array();
		if(is_null($id)) {
			$this->invalid[$failureName][] = $message;
		} else {
			$this->invalid[$failureName][$id] = $message;
		}
	}

	public function addFailures(array $failures) {
		foreach($failures as $failureKey => $failureValue) {
			if (is_array($failureValue)) {
				foreach($failureValue as $failureMessage) {
					$this->addFailure($failureKey, $failureMessage);
				}
			}
			else {
				$this->addFailure($failureKey, $failureValue);
			}

		}
	}

	public static function string_blacklist_insensitive($v, $invalid = ''){
		if (is_array($invalid))
			return !in_array(mb_strtolower($v), array_map(function($s){return mb_strtolower($s);}, $invalid));
		else
			return mb_strtolower($v) !== mb_strtolower($invalid);
	}

	public static function blacklist($v, $invalid = ''){
		if (is_array($invalid))
			return !in_array($v, $invalid);
		else
			return $v !== $invalid;
	}

	public static function whitelist($v, $valid){
		return in_array($v, $valid);
	}

	public static function range($v, $lo=null, $hi=null){
		$opts = array();
		if (!is_null($lo))
			$opts['min_range'] = $lo;

		if (!is_null($hi))
			$opts['max_range'] = $hi;

		return filter_var($v, FILTER_VALIDATE_INT, array('options'=>$opts))!==false;
	}

	public static function rangeFloat($v, $lo=null, $hi=null){
		if (!is_numeric($v))
			return false;

		if (!is_null($lo) && ($v<$lo))
			return false;

		if (!is_null($hi) && ($v>$hi))
			return false;

		return true;
	}

	public static function length($v, $lo=1, $hi=null){
		return self::range(mb_strlen($v), $lo, $hi);
	}

	public static function numeric($v){
		return is_numeric($v);
	}

	public static function typematch($v, $typeName){
		return ($v instanceof $typeName);
	}

	public static function email($v){
		return filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
	}

	public static function ip($v){
		return filter_var($v, FILTER_VALIDATE_IP) !== false;
	}

	public function setInvalidArray(array $invalid) {
		$this->invalid = $invalid;
	}

}

?>
