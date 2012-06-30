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
  trait Singleton
  {
    /**
     * @var null
     */
    protected static $i = NULL;
    /***
     * @static
     * @return
     */
    public static function i($class = null)
    {
      global $dic;
      if (!$dic instanceof \ADV\Core\DIC) {
        if (static::$i === NULL) {
          static::$i = new static;
        }
        return static::$i;
      }
      $namespaced_class = $class_name = $class ? get_class($class) : get_called_class();
      $lastNsPos        = strripos($namespaced_class, '\\');
      if ($lastNsPos) {
        $class_name = substr($namespaced_class, $lastNsPos + 1);
      }
      if ($class && static::$i === NULL) {
        $dic[$class_name] = function() use ($class) { return $class; };
        static::$i        = $class_name;
      }
      if (static::$i === NULL) {
        $args             = (func_num_args() > 1) ? array_slice(func_get_args(), 1) : [];
        $dic[$class_name] = function() use ($namespaced_class, $args)
        {
          if (!$args) {
            return new $namespaced_class;
          }
          $ref = new \ReflectionClass($namespaced_class);
          return $ref->newInstanceArgs($args);
        };
        static::$i        = $class_name;
      }
      return $dic[static::$i];
    }
  }
