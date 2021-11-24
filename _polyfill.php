<?
	if (!function_exists('intdiv')) {
		function intdiv($a, $b) {
			$a = (int) $a;
			$b = (int) $b;
			return ($a - fmod($a, $b)) / $b;
		}
	}

	if (!function_exists('json_last_error_msg')) {
		function json_last_error_msg() {
			static $ERRORS = array(
					JSON_ERROR_NONE => 'No error',
					JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
					JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
					JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
					JSON_ERROR_SYNTAX => 'Syntax error',
					JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
			);

			$error = json_last_error();
			return isset($ERRORS[$error]) ? $ERRORS[$error] : 'Unknown error';
		}
	}

	function array_merge_recursive_distinct(array &$array1, array &$array2)
	{
			$merged = $array1;

			foreach ($array2 as $key => &$value) {
					if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
							$merged[$key] = array_merge_recursive_distinct($merged[$key], $value);
					} else {
							$merged[$key] = $value;
					}
			}

			return $merged;
	}?>