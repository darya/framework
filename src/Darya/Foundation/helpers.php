<?php
/**
 * Helper functions for the Darya framework.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */

if (!function_exists('asplode')) {
	function asplode($delimiter, $string, $limit, $pad = null) {
		return array_pad(explode($delimiter, $string, $limit), $limit, $pad);
	}
}

if (!function_exists('read_json_file')) {
	function read_json_file($path) {
		return json_decode(file_get_contents($path), true);
	}
}

if (!function_exists('file_read_json')) {
	function file_read_json($path) {
		return read_json_file($path);
	}
}
