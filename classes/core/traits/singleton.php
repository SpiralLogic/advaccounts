<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 19/04/12
   * Time: 11:56 AM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\Core\Traits;

  /**

   */
  trait Singleton {

    /**
     * @var null
     */
    static protected $i = NULL;
    /***
     * @static
     * @return Ajax
     * Returns Ajax Instance
     */
    static public function i() {
      (static::$i === NULL) and  static::$i = new static;
      return static::$i;
    }
  }
