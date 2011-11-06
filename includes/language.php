<?php
	/**********************************************************************
	Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	// Prevent register_globals vulnerability
	class language
	{
		public $name;
		public $code; // eg. ar_EG, en_GB
		public $encoding; // eg. UTF-8, CP1256, ISO8859-1
		public $dir; // Currently support for Left-to-Right (ltr) and
		// Right-To-Left (rtl)
		public $is_locale_file;

		function language($name, $code, $encoding, $dir = 'ltr')
		{
			$this->name     = $name;
			$this->code     = $code ? $code : 'en_GB';
			$this->encoding = $encoding;
			$this->dir      = $dir;
		}

		function get_language_dir()
		{
			return "lang/" . $this->code;
		}

		function get_current_language_dir()
		{
			$lang = $_SESSION['language'];
			return "lang/" . $lang->code;
		}

		function set_language($code)
		{
			$changed = $this->code != $code;
			$lang    = Arr::search_value($code, Config::get('languages.installed'), 'code');
			if ($lang && $changed) {
				// flush cache as we can use several languages in one account
				Files::flush_dir(COMPANY_PATH . '/js_cache');
				$this->name           = $lang['name'];
				$this->code           = $lang['code'];
				$this->encoding       = $lang['encoding'];
				$this->dir            = isset($lang['rtl']) ? 'rtl' : 'ltr';
				$locale               = APP_PATH . "lang/" . $this->code . "/locale.php";
				$this->is_locale_file = file_exists($locale);
			}
			$_SESSION['get_text']->set_language($this->code, $this->encoding);
			$_SESSION['get_text']->add_domain($this->code, PATH_TO_ROOT . "/lang");
			// Necessary for ajax calls. Due to bug in php 4.3.10 for this
			// version set globally in php.ini
			ini_set('default_charset', $this->encoding);
			if (isset($_SESSION['App']) && $changed) {
				$_SESSION['App']->init();
			} // refresh menu
		}

		static function write_lang()
		{
			$conn = Arr::natsort(Config::get('languages.installed'), 'code', 'code');
			//Config::set('languages.installed', $conn);
			$installed_languages = Config::get('languages.installed');
			$n                   = count($installed_languages);
			$msg                 = "<?php\n\n";
			$msg .= "/* How to make new entries here\n\n";
			$msg .= "-- if adding languages at the beginning of the list, make sure it's index is set to 0 (it has ' 0 => ')\n";
			$msg .= "-- 'code' should match the name of the directory for the language under \\lang\n";
			$msg .= "-- 'name' is the name that will be displayed in the language selection list (in Users and Display Setup)\n";
			$msg .= "-- 'rtl' only needs to be set for right-to-left languages like Arabic and Hebrew\n\n";
			$msg .= "*/\n\n\n";
			$msg .= "\return array (\n";
			if ($n > 0) {
				$msg .= "\t0 => ";
			}
			for (
				$i = 0; $i < $n; $i++
			)
			{
				if ($i > 0) {
					$msg .= "\t\tarray ";
} else {
					$msg .= "array ";
				}
				$msg .= "('code' => '" . $installed_languages[$i]['code'] . "', ";
				$msg .= "'name' => '" . $installed_languages[$i]['name'] . "', ";
				$msg .= "'encoding' => '" . $installed_languages[$i]['encoding'] . "'";
				if (isset($installed_languages[$i]['rtl']) && $installed_languages[$i]['rtl']) {
					$msg .= ", 'rtl' => true),\n";
} else {
					$msg .= "),\n";
				}
			}
			$msg .= "\t);\n";
			$path     = APP_PATH . "lang";
			$filename = $path . '/installed_languages.php';
			// Check if directory exists and is writable first.
			if (file_exists($path) && is_writable($path)) {
				if (!$zp = fopen($filename, 'w')) {
					Errors::error(_("Cannot open the languages file - ") . $filename);
					return false;
} else {
					if (!fwrite($zp, $msg)) {
						Errors::error(_("Cannot write to the language file - ") . $filename);
						fclose($zp);
						return false;
					}
					// Close file
					fclose($zp);
				}
			} else {
				Errors::error(_("The language files folder ") . $path . _(" is not writable. Change its permissions so it is, then re-run the operation."));
				return false;
			}
			return true;
		}
	}

	if (!function_exists("_")) {
		function _($text)
		{
			$retVal = $_SESSION['get_text']->gettext($text);
			if ($retVal == "") {
				return $text;
			}
			return $retVal;
		}
	}
?>