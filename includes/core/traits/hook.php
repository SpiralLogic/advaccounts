<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core\Traits;
  /**

   */
  trait Hook {

    /** @var \Hook $hooks */
    public static $hooks = NULL;
    /**
     * @static
     *
     * @param       $hook
     * @param       $object
     * @param       $function
     * @param array $arguments
     */
    public static function registerHook($hook, $object, $function, $arguments = array()) {
      if (self::$hooks === NULL) {
        self::$hooks = new \ADV\Core\Hook();
      }
      $callback = $object . '::' . $function;

      self::$hooks->add($hook, $callback, $arguments);
    }
  }
