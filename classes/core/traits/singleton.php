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
    protected static $i = null;
    /***
     * @static
     * @return
     */
    public static function i($class = null) {
      /** @var \ADV\Core\DIC $dic  */
      $dic = \ADV\Core\DIC::getInstance();
      if (!$dic instanceof \ADV\Core\DIC) {
        if (static::$i === null) {
          static::$i = new static;
        }
        return static::$i;
      }
      if (static::$i !== null) {
        return $dic->get(static::$i);
      }
      $namespaced_class = $class_name = $class ? get_class($class) : get_called_class();
      $lastNsPos        = strripos($namespaced_class, '\\');
      if ($lastNsPos) {
        $class_name = substr($namespaced_class, $lastNsPos + 1);
      }
      if ($class && static::$i === null) {
        $dic->set($class_name, function() use ($class) {
          return $class;
        });
        static::$i = $class_name;
      }
      if (static::$i === null) {
        $args = (func_num_args() > 1) ? array_slice(func_get_args(), 1) : [];
        $dic->set($class_name, function() use ($namespaced_class, $args) {
          if (!$args) {
            return new $namespaced_class;
          }
          $ref = new \ReflectionClass($namespaced_class);
          return $ref->newInstanceArgs($args);
        });
        static::$i = $class_name;
      }
      return $dic->get(static::$i);
    }
  }
