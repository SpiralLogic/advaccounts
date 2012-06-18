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
      if ($class && static::$i === NULL) {
        $class_name       = get_called_class();
        $dic[$class_name] = $dic->share(function() use ($class) { return  $class; });
        static::$i        = $class_name;
      }
      if (static::$i === NULL) {
        $class_name = $class = get_called_class();
        if ($lastNsPos = strripos($class, '\\')) {
          $class_name = substr($class, $lastNsPos + 1);
        }
        $dic[$class_name] = $dic->share(function() use ($class) { return new $class; });
        static::$i        = $class_name;
      }
      return $dic[static::$i];
    }
  }
