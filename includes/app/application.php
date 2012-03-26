<?php

  /* * ********************************************************************
        Copyright (C) Advanced Group PTY LTD
        Released under the terms of the GNU General Public License, GPL,
        as published by the Free Software Foundation, either version 3
        of the License, or (at your option) any later version.
        This program is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
        See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
       * ********************************************************************* */
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

?>
