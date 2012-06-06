<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  /**

   */
  class app_function
  {
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
    public function app_function($label, $link, $access = SA_OPEN)
    {
      $this->label  = $label;
      $this->link   = e($link);
      $this->access = $access;
    }
  }
  /**

   */
  class module
  {
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
    public function module($name, $icon = null)
    {
      $this->name          = $name;
      $this->icon          = $icon;
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
    public function add_lapp_function($label, $link = "", $access = SA_OPEN)
    {
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
    public function add_rapp_function($label, $link = "", $access = SA_OPEN)
    {
      $appfunction = new app_function($label, $link, $access);
      //array_push($this->rappfunctions,$appfunction);
      $this->rappfunctions[] = $appfunction;
      return $appfunction;
    }
  }

  /**

   */interface IApplication
  {
    function buildMenu();
  }
  /**

   */
  abstract class Application implements IApplication
  {
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
    public $direct = false;
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
    public $enabled=true;
    /**
     * @internal param $id
     * @internal param $name
     * @internal param bool $enabled
     */
    public function __construct()
    {
      global $installed_extensions;
      $this->id      = strtolower($this->name);
      $this->name    = $this->help_context ? : $name;
      $this->help_context    = _($this->name);
      $this->modules = array();
      $this->extensions = $installed_extensions;
      $this->buildMenu();
      if (count($this->extensions) > 0) {
        $this->add_extensions();
      }
    }
    /**
     * @param      $name
     * @param null $icon
     *
     * @return module
     */
    public function add_module($name, $icon = null)
    {
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
    public function add_lapp_function($level, $label, $link = "", $access = SA_OPEN)
    {
      $this->modules[$level]->lappfunctions[] = new app_function($label, $link, $access);
    }
    /**
     * @param        $level
     * @param        $label
     * @param string $link
     * @param string $access
     */
    public function add_rapp_function($level, $label, $link = "", $access = SA_OPEN)
    {
      $this->modules[$level]->rappfunctions[] = new app_function($label, $link, $access);
    }
    protected function add_extensions()
    {
      foreach ($this->extensions as $mod) {
        if (@$mod['active'] && $mod['type'] == 'plugin' && $mod['tab'] == $this->id) {
          $this->add_rapp_function(2, $mod['title'], 'modules/' . $mod['path'] . '/' . $mod['filename'] . '?', isset($mod['access']) ? $mod['access'] : SA_OPEN);
        }
      }
    }
  }

