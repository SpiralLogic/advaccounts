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

  use ADV\Core\Event;

  /**

   */
  trait StaticAccess2
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
    public static function __callStatic($func, $args) {
      try {
        return call_user_func_array(array(static::i(), ltrim($func, '_')), $args);
      } catch (\ADV\Core\Exception $e) {
        return Event::error('Call to undefined static method ' . $func . ' in class ' . get_called_class());
      }
    }
    /**
     * @param $func
     * @param $args
     *
     * @return mixed
     */
    public function __call($func, $args) {
      try {
        return call_user_func_array(array(static::i(), ltrim($func, '_')), $args);
      } catch (\ADV\Core\Exception $e) {
        return Event::error('Call to undefined static method ' . $func . ' in class ' . get_called_class());
      }
    }
  }
