<?php

/*      ____________________________________________________
       |                                                    |
       |             		MathCheck                       |
       |                                                    |
       |            - a PHP class for web forms -           |
       |                                                    |
       |                © Michael Woehrer                   |
       |____________________________________________________|

    Author: Michael Woehrer <michael dot woehrer at gmail dot com>
	Author URI: http://sw-guide.de/
    Version: 1.1
    Copyright © 2006-2007, all rights reserved

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

*/

class MathCheck {


	/**
	 * @access public
	 */
	var $opt; 		// array containing the options
	var $info;		// containing information


	/**
	 * MathCheck
	 *
	 * Constructor for the MathCheck class. Provides $info. 
	 */ 
	function MathCheck() {

		// Default options		
		$this->opt = array(
			// Random, unique chars to be used in hash calculation. Please change this value.
			'unique' => 'LnfvpVZmsSCfLf0WxXN0',

			// Enter the numbers to be used. Use the number on the left side, tilde (~) as 
			// separator and then the term to display. Separate values with comma (,).
			// Examples:
			//  - 1~one, 2~two, 3~three, 4~four, 5~five, 6~six, 7~seven, 8~eight, 9~nine
			//  - 1~1, 2~2, 3~3, 4~4, 5~5, 6~6, 7~7, 8~8, 9~9, 10~10
			'input_numbers' => '1~1, 2~2, 3~3, 4~4, 5~5, 6~6, 7~7, 8~8, 9~9, 10~10',
		);				
		
	} // MathCheck()


	/**
	 * GenerateValues
	 *
	 */
	function GenerateValues() {

		// Get numbers in array
		$num_array = $this->auxNoToArray($this->opt['input_numbers']);
		// Get random keys
		$rand_keys = array_rand($num_array, 2);

		// Operands for displaying...
		$this->info['operand1'] = $num_array[$rand_keys[0]];
		$this->info['operand2'] = $num_array[$rand_keys[1]];
		// Generate result
		$this->info['result'] = $this->auxGenerateHash($rand_keys[0] + $rand_keys[1], date(j));

	} // GenerateValues()

	/**
	 * InputValidation
	 *
	 * Input validation. Returns an empty string if validation passed or an
	 * error string if not passed.	 
	 */
	function InputValidation($actualResult, $userEntered) {

		$error = '';

		// Case 1: User has not entered an answer at all:
		if ($error == '' && $userEntered == '') {
			$error = 'No answer';
		}

		$userEntered = preg_replace('/[^0-9]/', '', $userEntered);	// Remove everything except numbers

		if ($error == '' && $actualResult != $this->auxGenerateHash($userEntered, date(j)) ) {
			if ( ( date('G') <= 1 ) AND ( $actualResult == $this->auxGenerateHash($$userEntered, (intval(date(j))-1) ) )  ) {
				// User has just passed midnight while writing the comment. We consider
				// the time between 0:00 and 1:59 still as the day before to avoid
				// error messages if user visited page on 23:50 but pressed the "Submit Comment"
				// button on 0:15.
			} else {
				$error = 'Wrong answer';
			}
		}
		
		return $error;

	} // InputValidation()


	/***
	 * auxNoToArray
	 * 
	 * Converts the input string, e.g. "1~one, 2~two, 3~three, 4~four, ..."
	 * into an array, e.g.: Array([1] => one, [2] => two, [3] => three, ...)
	 */	 	 
	function auxNoToArray($input) {
	
		$input = str_replace(' ', '', $input);	// Strip whitespace
		$sourcearray = explode(',', $input);	// Create array
	
		foreach ($sourcearray as $loopval) {
			$temparr = explode('~', $loopval);
			$targetarray[$temparr[0]] = $temparr[1];
		}
		return $targetarray;

	} // auxNoToArray()


	/***
	 * auxGenerateHash
	 * 
	 * Generate hash
	 */	 	 

	function auxGenerateHash($inputstring, $day) {
	
		// If using Wordpress: many people have defined a WP_SECRET sting in 
		// wp-config.php, so we add it if it exists 
		if ( defined('WP_SECRET') ) 
			$inputstring .= WP_SECRET;
	
		// Adds the file modification time of this file
		$inputstring .= filemtime(__FILE__);
	
		// If using Wordpress: add the file modification time of wp-config.php
		if ( defined('ABSPATH') ) {
			if ( file_exists(ABSPATH . 'wp-config.php' ) )
				$inputstring .= filemtime(ABSPATH . 'wp-config.php');
		}

		// Adds a unique value defined in the options
		$inputstring .= $this->opt['unique'];

		// Add the IP address of the server under which the current script is executing.
		$inputstring .= getenv('SERVER_ADDR');
	
		// Add date
		$inputstring .= $day . date('ny');
	
		// Get MD5 and reverse it
		$enc = strrev(md5($inputstring));
	
		// Get only a few chars out of the string
		$enc = substr($enc, 28, 1) . substr($enc, 9, 1) . substr($enc, 21, 1) . substr($enc, 15, 1) . substr($enc, 7, 1);
			
		// Return result
		return $enc; 
	
	} // auxGenerateHash()


} 

?>