<?php

interface Validatable
{
	// validate returns an array of validation failures.
	// The array's key is the property of the object that failed, and the value is another array containing the failure messages
	public function validate();
}

?>
