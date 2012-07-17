<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 8/07/12
   * Time: 4:50 AM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\App\Application;
  /**

   */
  class Module
  {
    /** @var */
    public $name;
    /**
     * @var null
     */
    public $icon;
    /**
     * @var array
     */
    public $leftAppFunctions = [];
    /**
     * @var array
     */
    public $rightAppFunctions = [];
    /**
     * @param      $name
     * @param null $icon
     */
    public function __contruct($name, $icon = null) {

      $this->name              = $name;
      $this->icon              = $icon;
      $this->leftAppFunctions  = array();
      $this->rightAppFunctions = array();
    }
    /**
     * @param        $label
     * @param string $link
     * @param string $access
     *
     * @return Func
     */
    public function addLeftFunction($label, $link = "", $access = SA_OPEN) {
      $appfunction = new Func($label, $link, $access);
      //array_push($this->leftAppFunctions,$appfunction);
      $this->leftAppFunctions[] = $appfunction;
      return $appfunction;
    }
    /**
     * @param        $label
     * @param string $link
     * @param string $access
     *
     * @return Func
     */
    public function addRightFunction($label, $link = "", $access = SA_OPEN) {
      $appfunction = new Func($label, $link, $access);
      //array_push($this->rightAppFunctions,$appfunction);
      $this->rightAppFunctions[] = $appfunction;
      return $appfunction;
    }
  }
