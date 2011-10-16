<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 4/10/11
	 * Time: 8:24 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Arr {
		//-----------------------------------------------------------------------------
		//	Inserts $elements into $array at position $index.
		//	$elements is list of any objects
		//
		static function	insert(&$array, $index, $elements) {
			if (!is_array($elements)) $elements = array($elements);
			$head = array_splice($array, 0, $index);
			$array = array_merge($head, $elements, $array);
		}

		static function	remove(&$array, $index, $len = 1) {
			array_splice($array, $index, $len);
		}

		static function	substitute(&$array, $index, $len, $elements) {
			array_splice($array, $index, $len);
			Arr::insert($array, $index, $elements);
		}

		static function	append(&$array, $elements) {
			foreach ($elements as $key => $el) {
				if (is_int($key)) $array[] = $el; else
					$array[$key] = $el;
			}
		}

		static function	search_value($needle, $haystack, $valuekey = null) {
			if (!is_array($haystack)) throw new Adv_Exception('haystack needs to be an array');
			foreach ($haystack as $key => $value) {
				$val = isset($valuekey) ? $value[$valuekey] : $value;
				if ($needle == $val) {
					return $value;
				}
			}
			return null;
		}

		static function	search_key($needle, $haystack, $valuekey = null) {
			foreach ($haystack as $key => $value) {
				$val = isset($valuekey) ? $value[$valuekey] : $value;
				if ($needle == $val) {
					return $key;
				}
			}
			return null;
		}
	}
