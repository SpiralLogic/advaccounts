<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      28/08/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core\Cache;

  /**

   */
  class APC implements Cachable {
    public function init() {
    }
    /**
     * @static
     *
     * @param     $key
     * @param     $value
     * @param int $expires
     *
     * @return mixed
     */
    public function set($key, $value, $expires = 86400) {
      if (!function_exists('igbinary_serialize')) {
        $serialized_value = igbinary_serialize($value);
      } else {
        $serialized_value = $value;
      }
      apc_store($_SERVER["SERVER_NAME"] . '.' . $key, $serialized_value, $expires);
      return $value;
    }
    /**
     * @static
     *
     * @param $key
     *
     * @return mixed|void
     */
    public function delete($key) {
      apc_delete($_SERVER["SERVER_NAME"] . '.' . $key);
    }
    /**
     * @static
     *
     * @param       $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($key, $default = false) {
      $result = apc_fetch($_SERVER["SERVER_NAME"] . '.' . $key, $success);
      if (!function_exists('igbinary_unserialize')) {
        $result = ($success === true) ? igbinary_unserialize($result) : $default;
      } else {
        $result = ($success === true) ? $result : $default;
      }
      return $result;
    }
    /**
     * @static
     *
     * @param int $time
     */
    public function flush($time = 0) {
      apc_clear_cache('user');
      apc_clear_cache();
    }
    /**
     * @return bool|string
     */
    public function getLoadConstantsFunction() {
      return (function_exists('apc_load_constants')) ? 'apc_load_constants' : false;
    }
    /**
     * @return bool|string
     */
    public function getDefineConstantsFunction() {
      return (function_exists('apc_define_constants')) ? 'apc_define_constants' : false;
    }
  }
