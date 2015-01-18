<?php
namespace Darya\Common;

/**
 * Darya's set of utility functions.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Tools {
	
	/**
	 * Produces an <a> tag from a given URL and string.
	 * 
	 * @param string $url  The URL for the tag's href attribute
	 * @param string $text The text body for the tag
	 * @return string
	 */
	public static function href($url, $text) {
		$url = trim($url);
		$external = substr($url, 0, 7) == 'http://' || substr($url, 0, 2) == '//';
		$format = '<a href="%s">%s</a>';
		return $external ? sprintf($format, $url, $text) : sprintf($format, URL_BASE.'/'.$url, $text);
	}
	
	/**
	 * Wraps a given JavaScript string in <script> tags.
	 * 
	 * @param string $javascript
	 * @return string
	 */
	public static function script($javascript) {
		return '<script type="text/javascript">'.$javascript.'</script>';
	}
	
	public static function processPost($post) {
		$data = array();
		
		if (is_array($post)) {
			foreach ($post as $field => $keys) {
				foreach ($keys as $key => $value) {
					$data[$key][$field] = $value;
				}
			}
		}
		
		return $data;
	}
	
	/**
	 * Trim a string's leading and trailing whitespace.
	 * 
	 * @param string $str
	 * @return string
	 */
	public static function trim($str) {
		// return preg_replace('/\s+/',' ',$str);
		return str_replace('  ',' ',$str);
	}
	
	/**
	 * Ternary operator shortcut function.
	 * If $c is not set, it simply returns $a 
	 * if it is set and otherwise return $b.
	 * 
	 * @param mixed $a First value
	 * @param mixed $b 
	 * @param mixed $c [optional] Condition
	 * @return mixed
	 */
	public static function ternary($a, $b, $c = null) {
		if (isset($c)) {
			return $c ? $a : $b;
		} else {
			return $a || isset($a) ? $a : $b;
		}
	}
	
	/**
	 * Determine whether a given haystack starts
	 * with a given needle.
	 * 
	 * @param string $haystack
	 * @param string $needle
	 * @return bool
	 * @see http://stackoverflow.com/questions/834303
	 */
	public static function startsWith($haystack, $needle) {
		return $needle === "" || strpos($haystack, $needle) === 0;
	}
	
	/**
	 * Determine whether a given haystack ends
	 * with a given needle.
	 * 
	 * @param string $haystack
	 * @param string $needle
	 * @return bool
	 * @see http://stackoverflow.com/questions/834303
	 */
	public static function endsWith($haystack, $needle) {
		if (is_array($needle)) {
			foreach ($needle as $n) {
				if (static::endsWith($haystack, $n)) {
					return true;
				}
			}
			
			return false;
		} else {
			return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
		}
	}
	
	/**
	 * Shortcut for retrieving an object that describes the difference between 
	 * two dates. If a second date is not given, it will use the current time.
	 * 
	 * @param string $date1 Date string
	 * @param string $date2 [optional] Date string, defaults to current time ('now')
	 * @return \DateInterval
	 */
	public static function dateDiff($date1, $date2 = null) {
		$date2 = $date2 ?: 'now';
		$date1 = new \DateTime($date1);
		$date2 = new \DateTime($date2);
		return $date1->diff($date2);
	}
	
	/**
	 * Returns the difference between two dates as a string in a pretty format.
	 * 
	 * Example output:
	 * "1 minute ago"
	 * "2 hours ago"
	 * "3 days from now"
	 * "4 months ago"
	 * 
	 * @param string|int $date Date string or timestamp int
	 * @return string
	 * @uses Tools::dateDiff() Used to calculates the difference between now and the given date
	 */
	public static function prettyDate($date) {
		$diff = is_numeric($date) ? static::dateDiff(date('Y-m-d H:i:s'), $date) : static::dateDiff($date);
		$intervals = array('s' => 'second', 'i' => 'minute', 'h' => 'hour', 'd' => 'day', 'm' => 'month', 'y' => 'year');
		$result = '';
		
		foreach ($intervals as $k => $v) {
			$d = $diff->$k;
			
			if ($d) {
				$result = "$d $v" . ($d <> 1 ? 's' : '');
			}
		}
		
		return $result ? $result . ($diff->invert ? ' from now' : ' ago') : 'Just now';
	}
	
	/**
	 * Captures the output of invoking var_dump with the given arguments,
	 * returning the result HTML-encoded & wrapped in <pre> tags.
	 * 
	 * @return string
	 */
	public static function dump() {
		ob_start();
		call_user_func_array('var_dump', func_get_args());
		return '<pre>' . htmlentities(ob_get_clean()) . '</pre>';
	}
	
	/**
	 * Converts a delimited string to a camelcase string. The default
	 * delimiter is a hyphen.
	 * 
	 * For example, 'super-swag-class' would become 'SuperSwagClass'
	 * 
	 * @param string $str
	 * @param string $delim [optional]
	 * @return string
	 */
	public static function delimToCamel($str, $delim = '-') {
		if ($delim != '-') {
			$str = str_replace($delim, '-', $str);
		}
		
		return preg_replace_callback('/\-[a-z]/', function ($matches) {
			return strtoupper(trim($matches[0], '-'));
		}, ucfirst($str));
	}
	
	/**
	 * Converts a camelcase string to a delimited string. The default
	 * delimiter is a hyphen.
	 * 
	 * For example, 'SuperSwagClass' would become 'super-swag-class'
	 * 
	 * @param string $str
	 * @param string $delim [optional]
	 * @return string
	 */
	public static function camelToDelim($str, $delim = '-') {
		return strtolower(preg_replace_callback('/[A-Z]/', function ($match) use ($delim) {
			return $delim . $match[0];
		}, lcfirst($str)));
	}
	
	/**
	 * Replace any number of consecutive backslashes and forward slashes in a 
	 * given string with a single forward slash after removing leading and
	 * trailing slashes.
	 * 
	 * @param string $path
	 * @param bool   $ignoreLeadingSlash Whether to retain a leading slash
	 * @return string
	 */
	public static function normalisePath($path, $ignoreLeadingSlash = false) {
		return preg_replace('#[\\\|/]+#', '/', $ignoreLeadingSlash ? rtrim($path, '\/') : trim($path, '\/'));
	}
	
	/**
	 * A wrapper around dirname that prevents returning '.' in the case of a
	 * filename without a path.
	 * 
	 * @param string $path
	 * @return string
	 */
	public static function dirname($path) {
		$dirname = dirname($path);
		return $dirname != '.' ? $dirname : '';
	}
	
	/**
	 * Convert a given number of bytes into a human readable file size format. 
	 * 
	 * @param int $bytes
	 * @param int $precision [optional] Defaults to 2
	 * @return string
	 * @see http://codeaid.net/php/convert-size-in-bytes-to-a-human-readable-format-(php)
	 */
	public static function filesize($bytes, $precision = 2) {
		$unit = array('B','KB','MB','GB','TB','PB','EB');
		
		return @round(
			$bytes / pow(1024, ($i = floor(log($bytes, 1024)))), $precision
		) . ' ' . @$unit[$i];
	}
	
}
