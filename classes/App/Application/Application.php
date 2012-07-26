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
  namespace ADV\App\Application;
  /**

   */
  abstract class Application
  {
    /** @var */
    public $id;
    /** @var */
    public $name;
    /**
     * @var bool
     */
    public $direct = false;
    /** @var */
    public $help_context;
    /**
     * @var array
     */
    public $modules;
    /**
     * @var bool
     */
    public $enabled = true;
    /**
     * @internal param $id
     * @internal param $name
     * @internal param bool $enabled
     */
    public function __construct() {
      global $installed_extensions;
      $this->id           = strtolower($this->name);
      $this->name         = $this->help_context ? : $this->name;
      $this->help_context = _($this->name);
      $this->modules      = array();
      $this->extensions   = $installed_extensions;
      $this->buildMenu();
      if (count($this->extensions) > 0) {
        $this->addExtensions();
      }
    }
    abstract function buildMenu();
    /**
     * @param      $name
     * @param null $icon
     *
     * @return Application\Module
     */
    public function add_module($name, $icon = null) {
      $module = new Module($name, $icon);
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
    public function addLeftFunction($level, $label, $link = "", $access = SA_OPEN) {
      $this->modules[$level]->leftAppFunctions[] = new Func($label, $link, $access);
    }
    /**
     * @param        $level
     * @param        $label
     * @param string $link
     * @param string $access
     */
    public function addRightFunction($level, $label, $link = "", $access = SA_OPEN) {
      $this->modules[$level]->rightAppFunctions[] = new Func($label, $link, $access);
    }
    protected function addExtensions() {
      foreach ($this->extensions as $mod) {
        if (@$mod['active'] && $mod['type'] == 'plugin' && $mod['tab'] == $this->id) {
          $this->addRightFunction(2, $mod['title'], 'modules/' . $mod['path'] . '/' . $mod['filename'] . '?', isset($mod['access']) ?
            $mod['access'] : SA_OPEN);
        }
      }
    }
  }

