<?php

	/*     * *******************************************************************
			Copyright (C) FrontAccounting, LLC.
			Released under the terms of the GNU General Public License, GPL,
			as published by the Free Software Foundation, either version 3
			of the License, or (at your option) any later version.
			This program is distributed in the hope that it will be useful,
			but WITHOUT ANY WARRANTY; without even the implied warranty of
			MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
			See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
		 * ********************************************************************* */

	include_once(APP_PATH . 'applications/application.php');
	include_once(APP_PATH . 'applications/customers.php');
	include_once(APP_PATH . 'applications/contacts.php');
	include_once(APP_PATH . 'applications/suppliers.php');
	include_once(APP_PATH . 'applications/inventory.php');
	include_once(APP_PATH . 'applications/items.php');
	include_once(APP_PATH . 'applications/advanced.php');
	include_once(APP_PATH . 'applications/manufacturing.php');
	include_once(APP_PATH . 'applications/dimensions.php');
	include_once(APP_PATH . 'applications/generalledger.php');
	include_once(APP_PATH . 'applications/setup.php');
	include_once(APP_PATH . 'config/installed_extensions.php');
	if (count($installed_extensions) > 0) {
		foreach ($installed_extensions as $ext) {
			if ($ext['type'] == 'module')
				include_once(APP_PATH . "" . $ext['path'] . "/" . $ext['filename']);
		}
	}

	class frontaccounting {

		var $user;
		var $settings;
		var $applications;
		var $selected_application;
		// GUI
		var $menu;

		//var $renderer;
		function frontaccounting() {
			//$this->renderer =& renderer::getInstance();
		}

		function add_application(&$app) {
			if ($app->enabled) // skip inactive modules
				$this->applications[$app->id] = &$app;
		}

		function get_application($id) {
			if (isset($this->applications[$id]))
				return $this->applications[$id];
			return null;
		}

		function get_selected_application() {
			if (isset($this->selected_application))
				return $this->applications[$this->selected_application];
			foreach ($this->applications as $application)
			{
				return $application;
			}
			return null;
		}

		function display() {

			include(APP_PATH . "themes/" . user_theme() . "/renderer.php");
			$this->init();
			$rend = renderer::getInstance();
			$rend->wa_header();
			//$rend->menu_header($this->menu);
			$rend->display_applications($this);
			//$rend->menu_footer($this->menu);
			$rend->wa_footer();
		}

		function init() {
			global $installed_extensions;
			$this->menu = new menu(_("Main  Menu"));
			$this->menu->add_item(_("Main  Menu"), "index.php");
			$this->menu->add_item(_("Logout"), "/account/access/logout.php");
			$this->applications = array();
			$this->add_application(new customers_app());
			$this->add_application(new contacts_app());
			$this->add_application(new suppliers_app());
			$this->add_application(new inventory_app());
			$this->add_application(new items_app());
			$this->add_application(new advanced_app());
			$this->add_application(new manufacturing_app());
			$this->add_application(new dimensions_app());
			$this->add_application(new general_ledger_app());
			if (count($installed_extensions) > 0) {
				// Do not use global array directly here, or you suffer
				// from buggy php behaviour (unexpected loop break
				// because of same var usage in class constructor).
				$extensions = $installed_extensions;
				foreach ($extensions as $ext) {
					if (@($ext['active'] && $ext['type'] == 'module')) { // supressed warnings before 2.2 upgrade
						$_SESSION['get_text']->add_domain($_SESSION['language']->code,
						 $ext['path'] . "/lang");
						$class = $ext['tab'] . "_app";
						if (class_exists($class))
							$this->add_application(new $class());
						$_SESSION['get_text']->add_domain($_SESSION['language']->code,
						 PATH_TO_ROOT . "/lang");
					}
				}
			}

			$this->add_application(new setup_app());
		}
	}

?>