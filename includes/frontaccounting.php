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
	include('application.php');
	class frontaccounting
	{
		public $user;
		public $settings;
		public $applications;
		public $selected_application;
		public $menu;

		function __construct()
		{
			Session::hasLogin();
			$installed_extensions = Config::get('extensions.installed');
			$this->menu = new menu(_("Main  Menu"));
			$this->menu->add_item(_("Main  Menu"), "index.php");
			$this->menu->add_item(_("Logout"), "/account/access/logout.php");
			$this->applications = array();
			$apps = Config::get('apps.active');
			foreach ($apps as $app) {
				$app = 'App_' . $app;
				$this->add_application(new $app());
			}
			if (count($installed_extensions) > 0) {
				// Do not use global array directly here, or you suffer
				// from buggy php behaviour (unexpected loop break
				// because of same var usage in class constructor).
				$extensions = $installed_extensions;
				foreach ($extensions as $ext) {
					$ext = 'App_' . $ext['name'];
					$this->add_application(new $ext());
				}
				Session::$get_text->add_domain(Session::$lang->code, PATH_TO_ROOT . "/lang");
			}
			$this->add_application(new App_System());
		}

		function add_application(&$app)
		{
			if ($app->enabled) // skip inactive modules
			{
				$this->applications[$app->id] = &$app;
			}
		}

		function get_application($id)
		{
			if (isset($this->applications[$id])) {
				return $this->applications[$id];
			}
			return null;
		}

		function get_selected_application()
		{
			if (isset($this->selected_application)) {
				return $this->applications[$this->selected_application];
			}
			foreach ($this->applications as $application) {
				return $application;
			}
			return null;
		}

		function display()
		{
			$rend = Renderer::get();
			$rend->header();
			//$rend->menu_header($this->menu);
			$rend->display_applications($this);
			//$rend->menu_footer($this->menu);
			$rend->footer();
		}

		public static function init()
		{
			if (!isset($_SESSION["App"])) {
				$_SESSION["App"] = new frontaccounting();
			}
		}

		/**
		 * @return Returns the array sorted as required
		 *
		 * @param $aryData			 Array containing data to sort
		 * @param $strIndex			name of column to use as an index
		 * @param $strSortBy		 Column to sort the array by
		 * @param $strSortType	 String containing either asc or desc [default to asc]
		 *
		 * @desc Naturally sorts an array using by the column $strSortBy
		 */
		public static function write_extensions($extensions = null, $company = -1)
		{
			global $installed_extensions, $next_extension_id;
			if (!isset($extensions)) {
				$extensions = $installed_extensions;
			}
			if (!isset($next_extension_id)) {
				$next_extension_id = 1;
			}
			//	$exts = Arr::natsort($extensions, 'name', 'name');
			//	$extensions = $exts;
			$msg = "<?php\n\n";
			if ($company == -1) {
				$msg .= "/* List of installed additional modules and plugins. If adding extensions manually
			to the list make sure they have unique, so far not used extension_ids as a keys,
			and \$next_extension_id is also updated.

			'name' - name for identification purposes;
			'type' - type of extension: 'module' or 'plugin'
			'path' - FA root based installation path
			'filename' - name of module menu file, or plugin filename; related to path.
			'tab' - index of the module tab (new for module, or one of standard module names for plugin);
			'title' - is the menu text (for plugin) or new tab name
			'active' - current status of extension
			'acc_file' - (optional) file name with \$security_areas/\$security_sections extensions;
				related to 'path'
			'access' - security area code in string form
		*/
		\n\$next_extension_id = $next_extension_id; // unique id for next installed extension\n\n";
			} else {
				$msg .= "/*
			Do not edit this file manually. This copy of global file is overwritten
			by extensions editor.
		*/\n\n";
			}
			$msg .= "\$installed_extensions = array (\n";
			foreach ($extensions as $i => $ext) {
				$msg .= "\t$i => ";
				$msg .= "array ( ";
				$t = '';
				foreach ($ext as $key => $val) {
					$msg .= $t . "'$key' => '$val',\n";
					$t = "\t\t\t";
				}
				$msg .= "\t\t),\n";
			}
			$msg .= "\t);\n?>";
			$filename = PATH_TO_ROOT . ($company == -1 ? '' : '/company/' . $company) . '/installed_extensions.php';
			// Check if the file is writable first.
			if (!$zp = fopen($filename, 'w')) {
				Errors::error(sprintf(_("Cannot open the extension setup file '%s' for writing."), $filename));
				return false;
			} else {
				if (!fwrite($zp, $msg)) {
					Errors::error(sprintf(_("Cannot write to the extensions setup file '%s'."), $filename));
					fclose($zp);
					return false;
				}
				// Close file
				fclose($zp);
			}
			return true;
		}
	}

?>