<?php

	/*     * ********************************************************************
			Copyright (C) FrontAccounting, LLC.
			Released under the terms of the GNU General Public License, GPL,
			as published by the Free Software Foundation, either version 3
			of the License, or (at your option) any later version.
			This program is distributed in the hope that it will be useful,
			but WITHOUT ANY WARRANTY; without even the implied warranty of
			MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
			See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
		 * ********************************************************************* */
	class app_function
	{
		public $label;
		public $link;
		public $access;

		function app_function($label, $link, $access = 'SA_OPEN')
		{
			$this->label  = $label;
			$this->link   = $link;
			$this->access = $access;
		}
	}

	class module
	{
		public $name;
		public $icon;
		public $lappfunctions;
		public $rappfunctions;

		function module($name, $icon = null)
		{
			$this->name          = $name;
			$this->icon          = $icon;
			$this->lappfunctions = array();
			$this->rappfunctions = array();
		}

		function add_lapp_function($label, $link = "", $access = 'SA_OPEN')
		{
			$appfunction = new app_function($label, $link, $access);
			//array_push($this->lappfunctions,$appfunction);
			$this->lappfunctions[] = $appfunction;
			return $appfunction;
		}

		function add_rapp_function($label, $link = "", $access = 'SA_OPEN')
		{
			$appfunction = new app_function($label, $link, $access);
			//array_push($this->rappfunctions,$appfunction);
			$this->rappfunctions[] = $appfunction;
			return $appfunction;
		}
	}

	abstract class Application
	{
		public $id;
		public $name;
		public $direct = false;
		public $help_context;
		public $modules;
		public $enabled;

		function __construct($id, $name, $enabled = true)
		{
			$this->id      = $id;
			$this->name    = $name;
			$this->enabled = $enabled;
			$this->modules = array();
		}

		function add_module($name, $icon = null)
		{
			$module = new module($name, $icon);
			//array_push($this->modules,$module);
			$this->modules[] = $module;
			return $module;
		}

		function add_lapp_function($level, $label, $link = "", $access = 'SA_OPEN')
		{
			$this->modules[$level]->lappfunctions[] = new app_function($label, $link, $access);
		}

		function add_rapp_function($level, $label, $link = "", $access = 'SA_OPEN')
		{
			$this->modules[$level]->rappfunctions[] = new app_function($label, $link, $access);
		}
	}

?>