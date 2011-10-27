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
			$head  = array_splice($array, 0, $index);
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

		static function natsort($aryData, $strIndex, $strSortBy, $strSortType = false) {
			//    if the parameters are invalid
			if (!is_array($aryData) || !$strSortBy
			)
				//    return the array
				return $aryData;
			//    create our temporary arrays
			$arySort = $aryResult = array();

			//    loop through the array
			foreach ($aryData as $key => $aryRow) {
				$arySort[$strIndex ? $aryRow[$strIndex] : $key] = $aryRow[$strSortBy];
			}

			//    apply the natural sort
			natsort($arySort);

			//    if the sort type is descending
			if ($strSortType == "desc")
				arsort($arySort);

			//    loop through the sorted and original data
			foreach ($arySort as $arySortKey => $arySorted) {
				if ($strIndex) {
					foreach ($aryData as $aryOriginal) {
						if ($aryOriginal[$strIndex] == $arySortKey)
							array_push($aryResult, $aryOriginal);
					}
				} else $aryResult[$arySortKey] = $aryData[$arySortKey];
			}

			return $aryResult;
		}
	}
