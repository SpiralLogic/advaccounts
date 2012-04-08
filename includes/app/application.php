<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  /**

   */
  class app_function {

    /**
     * @var
     */
    public $label;
    /**
     * @var
     */
    public $link;
    /**
     * @var string
     */
    public $access;
    /**
     * @param        $label
     * @param        $link
     * @param string $access
     */
    function app_function($label, $link, $access = SA_OPEN) {
      $this->label = $label;
      $this->link = e($link);
      $this->access = $access;
    }
  }

  /**

   */
  class module {

    /**
     * @var
     */
    public $name;
    /**
     * @var null
     */
    public $icon;
    /**
     * @var array
     */
    public $lappfunctions;
    /**
     * @var array
     */
    public $rappfunctions;
    /**
     * @param      $name
     * @param null $icon
     */
    public function module($name, $icon = NULL) {
      $this->name = $name;
      $this->icon = $icon;
      $this->lappfunctions = array();
      $this->rappfunctions = array();
    }
    /**
     * @param        $label
     * @param string $link
     * @param string $access
     *
     * @return app_function
     */
    public function add_lapp_function($label, $link = "", $access = SA_OPEN) {
      $appfunction = new app_function($label, $link, $access);
      //array_push($this->lappfunctions,$appfunction);
      $this->lappfunctions[] = $appfunction;
      return $appfunction;
    }
    /**
     * @param        $label
     * @param string $link
     * @param string $access
     *
     * @return app_function
     */
    public function add_rapp_function($label, $link = "", $access = SA_OPEN) {
      $appfunction = new app_function($label, $link, $access);
      //array_push($this->rappfunctions,$appfunction);
      $this->rappfunctions[] = $appfunction;
      return $appfunction;
    }
  }

  /**

   */
  abstract class Application {

    /**
     * @var
     */
    public $id;
    /**
     * @var
     */
    public $name;
    /**
     * @var bool
     */
    public $direct = FALSE;
    /**
     * @var
     */
    public $help_context;
    /**
     * @var array
     */
    public $modules;
    /**
     * @var bool
     */
    public $enabled;
    /**
     * @param      $id
     * @param      $name
     * @param bool $enabled
     */
    public function __construct($id, $name, $enabled = TRUE) {
      $this->id = $id;
      $this->name = $name;
      $this->enabled = $enabled;
      $this->modules = array();
    }
    /**
     * @param      $name
     * @param null $icon
     *
     * @return module
     */
    public function add_module($name, $icon = NULL) {
      $module = new module($name, $icon);
      //array_push($this->modules,$module);
      $this->modules[] = $module;
      return $module;
    }
    /**
     * @param        $level
     * @param        $label
     * @param string $link
     * @param string $access
     */
    public function add_lapp_function($level, $label, $link = "", $access = SA_OPEN) {
      $this->modules[$level]->lappfunctions[] = new app_function($label, $link, $access);
    }
    /**
     * @param        $level
     * @param        $label
     * @param string $link
     * @param string $access
     */
    public function add_rapp_function($level, $label, $link = "", $access = SA_OPEN) {
      $this->modules[$level]->rappfunctions[] = new app_function($label, $link, $access);
    }
  }


