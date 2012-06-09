<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      7/06/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core\Traits;

  /**

   */
  trait StaticAccess
  {
    /**
     * @static
     *
     * @param $func
     * @param $args
     *
     * @return mixed
     */
    use Singleton;
    /**
     * @static
     *
     * @param $func
     * @param $args
     *
     * @return mixed
     */
    public static function __callStatic($func, $args)
    {

      if (method_exists(static::i(), '_' . $func)) {
        return call_user_func_array(array(static::i(), '_' . $func), $args);
      }
    }
    /**
     * @param $func
     * @param $args
     *
     * @return mixed
     */
    public function __call($func, $args)
    {
      if (method_exists($this, '_' . $func)) {
        return call_user_func_array(array($this, '_' . $func), $args);
      }
    }
  }
