<?php


  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      22/09/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Controller;

  use ADV\App\Display;
  use ADV\App\User;
  use ADV\App\Page;
  use ADV\App\Application\Module;
  use ADV\Core\View;

  /**

   */
  abstract class Menu {
    protected $direct = false;
    protected $modules = [];
    /** @var \ADV\App\User */
    protected $User;
    /** @var */
    public $id;
    /** @var */
    public $name;
    /** @var */
    public $help_context;
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
      $this->User = User::i();

      $this->id           = strtolower($this->name);
      $this->name         = $this->help_context ? : $this->name;
      $this->help_context = _($this->name);
      $this->modules      = [];
      $this->extensions   = $installed_extensions;
      $this->buildMenu();
      if (count($this->extensions) > 0) {
        $this->addExtensions();
      }
      $this->display();
    }
    abstract function buildMenu();
    /**
     * @param      $name
     * @param null $icon
     *
     * @return Module
     */
    public function add_module($name, $icon = null) {

      $module          = new Module($name, $icon);
      $this->modules[] = $module;
      return $module;
    }
    protected function addExtensions() {
      foreach ($this->extensions as $mod) {
        if (@$mod['active'] && $mod['type'] == 'plugin' && $mod['tab'] == $this->id) {
        }
      }
    }
    protected function display() {

      Page::start(_($help_context = "Main Menu"), SA_OPEN, false, true);
      if ($this->direct) {
        Display::meta_forward($this->direct);
      }

      foreach ($this->modules as $module) {
        $app            = new View('application');
        $app['colspan'] = (count($module->rightAppFunctions) > 0) ? 2 : 1;
        $app['name']    = $module->name;
        foreach ([$module->leftAppFunctions, $module->rightAppFunctions] as $modules) {
          $mods = [];
          foreach ($modules as $func) {
            $mod['access'] = $this->User->hasAccess($func->access);
            $mod['label']  = $func->label;
            if ($mod['access']) {
              $mod['link'] = Display::menu_link($func->link, $func->label);
            } else {
              $mod['anchor'] = Display::access_string($func->label, true);
            }
            $mods[] = $mod;
          }
          $app->set((!$app['lmods']) ? 'lmods' : 'rmods', $mods);
        }
        $app->render();
      }
      Page::end();
    }
  }
