<?php


  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      22/09/12
   * @href      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Controller;

  use ADV\App\Display;
  use ADV\Core\Arr;
  use ADV\App\Application\Func;
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
    public function __construct($session, $user) {
      $this->User         = $user;
      $this->id           = strtolower($this->name);
      $this->name         = $this->help_context ? : $this->name;
      $this->help_context = _($this->name);
      $this->modules      = [];
    }
    abstract function buildMenu();
    /**
     * @param      $name
     *
     * @internal param null $icon
     * @return $this
     */
    public function add_module($name) {
      $this->modules[$name]    = ['right'=> [], 'left'=> []];
      $this->rightAppFunctions =& $this->modules[$name]['right'];
      $this->leftAppFunctions  =& $this->modules[$name]['left'];
      return $this;
    }
    public function getModules() {
      $modules = [];
      foreach ($this->modules as $name => $module) {
        $functions = [];
        Arr::append($functions, $module['right']);
        Arr::append($functions, $module['left']);
        foreach ($functions as &$func) {
          $func = str_replace('&','',$func);
        }
        $modules[] = ['title'=>$name, 'modules'=> $functions];
    }
      return $modules;
    }
    public function display() {
      $this->buildMenu();
      Page::start(_($help_context = "Main Menu"), SA_OPEN, false, true);
      if ($this->direct) {
        Display::meta_forward($this->direct);
      }
      foreach ($this->modules as $name => $module) {
        $app            = new View('application');
        $app['colspan'] = (count($module['right']) > 0) ? 2 : 1;
        $app['name']    = $name;
        foreach ([$module['left'], $module['right']] as $modules) {
          $mods = [];
          foreach ($modules as $func) {
            $mod['access'] = $this->User->hasAccess($func['access']);
            $mod['label']  = $func['label'];
            if ($mod['access']) {
              $mod['href'] = Display::menu_link($func['href'], $func['label']);
            } else {
              $mod['anchor'] = Display::access_string($func['label'], true);
            }
            $mods[] = $mod;
          }
          $app->set((!$app['lmods']) ? 'lmods' : 'rmods', $mods);
        }
        $app->render();
      }
      Page::end();
    }
    /**
     * @param        $label
     * @param string $href
     * @param string $access
     *
     * @return Func
     */
    public function addLeftFunction($label, $href = "", $access = SA_OPEN) {
      $appfunction              = ['label'=> $label, 'href'=> $href, 'access'=> $access];
      $this->leftAppFunctions[] = $appfunction;
      return $appfunction;
    }
    /**
     * @param        $label
     * @param string $href
     * @param string $access
     *
     * @return Func
     */
    public function addRightFunction($label, $href = "", $access = SA_OPEN) {
      $appfunction               = ['label'=> $label, 'href'=> $href, 'access'=> $access];
      $this->rightAppFunctions[] = $appfunction;
      return $appfunction;
    }
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
  }
