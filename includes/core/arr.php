<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 4/10/11
	 * Time: 8:24 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Arr
	{
		/**
		 * @static
		 *
		 * @param $array
		 * @param $index
		 * @param $elements
		 *
		 * Inserts $elements into $array at position $index.
		 * $elements is list of any objects
		 *
		 */
		static function insert(&$array, $index, $elements) {
			if (!is_array($elements)) {
				$elements = array($elements);
			}
			$head = array_splice($array, 0, $index);
			$array = array_merge($head, $elements, $array);
			return true;
		}
		/**
		 * @static
		 *
		 * @param     $array
		 * @param     $index
		 * @param int $len
		 */
		static function remove(&$array, $index, $len = 1) {
			array_splice($array, $index, $len);
			return true;
		}
		/**
		 * @static
		 *
		 * @param $array
		 * @param $index
		 * @param $len
		 * @param $elements
		 */
		static function substitute(&$array, $index, $len, $elements) {
			array_splice($array, $index, $len);
			Arr::insert($array, $index, $elements);
			return true;
		}
		/**
		 * @static
		 *
		 * @param $array
		 * @param $elements
		 */
		static function append(&$array, $elements) {
			foreach ($elements as $key => $el) {
				if (is_int($key)) {
					$array[] = $el;
				}
				else
				{
					$array[$key] = $el;
				}
			}
		}
		/**
		 * @static
		 *
		 * @param       $needle
		 * @param array $haystack
		 * @param null  $valuekey
		 *
		 * @return int|null
		 */
		static function search_value($needle, $haystack, $valuekey = null) {
			foreach ($haystack as $value) {
				$val = isset($valuekey) ? $value[$valuekey] : $value;
				if ($needle == $val) {
					return $value;
				}
			}
			return null;
		}
		/**
		 * @static
		 *
		 * @param      $needle
		 * @param      $haystack
		 * @param null $valuekey
		 *
		 * @return int|null|string
		 */
		static function search_key($needle, $haystack, $valuekey = null) {
			foreach ($haystack as $key => $value) {
				$val = isset($valuekey) ? $value[$valuekey] : $value;
				if ($needle == $val) {
					return $key;
				}
			}
			return null;
		}
		/**
		 * @return Returns the array sorted as required
		 *
		 * @param        $aryData     Array containing data to sort
		 * @param string $strIndex    name of column to use as an index
		 * @param string $strSortBy   Column to sort the array by
		 * @param        $strSortType String containing either asc or desc [default to asc]
		 *
		 * @desc Naturally sorts an array using by the column $strSortBy
		 */
		static function natsort($aryData, $strIndex, $strSortBy, $strSortType = false) {
			// if the parameters are invalid
			if (!is_array($aryData) || !$strSortBy
			) // return the array
			{
				return $aryData;
			}
			// create our temporary arrays
			$arySort = $aryResult = array();
			// loop through the array
			foreach ($aryData as $key => $aryRow) {
				$arySort[$strIndex ? $aryRow[$strIndex] : $key] = $aryRow[$strSortBy];
			}
			// apply the natural sort
			natsort($arySort);
			// if the sort type is descending
			if ($strSortType == "desc") {
				arsort($arySort);
			}
			// loop through the sorted and original data
			/** @noinspection PhpUnusedLocalVariableInspection */
			foreach ($arySort as $arySortKey => $arySorted) {
				if ($strIndex) {
					foreach ($aryData as $aryOriginal) {
						if ($aryOriginal[$strIndex] == $arySortKey) {
							array_push($aryResult, $aryOriginal);
						}
					}
				}
				else {
					$aryResult[$arySortKey] = $aryData[$arySortKey];
				}
			}
			return $aryResult;
		}
	}
