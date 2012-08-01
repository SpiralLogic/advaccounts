<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.core
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core;
  /**
   */
  class Arr
  {
    /**
     * @static
     *
     * @param $array
     * @param $index
     * @param $elements
     * Inserts $elements into $array at position $index.
     * $elements is list of any objects
     *
     * @return bool
     */
    public static function insert(&$array, $index, $elements) {
      $elements = (array) ($elements);
      $head     = array_splice($array, 0, $index);
      $array    = array_merge($head, $elements, $array);
      return true;
    }
    /**
     * @static
     *
     * @param     $array
     * @param     $index
     * @param int $len
     *
     * @return bool
     */
    public static function remove(&$array, $index, $len = 1) {
      array_splice($array, $index, $len);
      return true;
    }
    /**
     * @static
     *
     * @param array      $array
     * @param int|string $key
     * @param mixed      $default
     *
     * @return mixed null
     */
    public static function get(array $array, $key, $default = null) {
      return (isset($array[$key])) ? $array[$key] : $default;
    }
    /**
     * @static
     *
     * @param $array
     * @param $index
     * @param $len
     * @param $elements
     *
     * @return bool
     */
    public static function substitute(&$array, $index, $len, $elements) {
      array_splice($array, $index, $len);
      Arr::insert($array, $index, $elements);
      return true;
    }
    /**
     * @static
     *
     * @param             &$array
     * @param array|mixed $elements elements to append,
     */
    public static function append(&$array, $elements = []) {
      $elements = (array) $elements;
      foreach ($elements as $key => $el) {
        if (is_int($key)) {
          $array[] = $el;
        } else {
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
    public static function searchValue($needle, $haystack, $valuekey = null) {
      foreach ($haystack as $value) {
        if ($valuekey === null) {
          $val = $value;
        } elseif (is_array($value)) {
          $val = $value[$valuekey];
        } else {
          continue;
        }
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
    public static function searchKey($needle, $haystack, $valuekey = null) {
      foreach ($haystack as $key => $value) {
        $val = isset($valuekey) ? $value[$valuekey] : $value;
        if ($needle == $val) {
          return $key;
        }
      }
      return null;
    }
  }