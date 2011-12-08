<?php
	/**********************************************************************
	Copyright (C) Advanced Group PTY LTD
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	// Prevent register_globals vulnerability
	/**
	 *
	 */
	class Language
	{
		/**
		 * @var
		 */
		public $name;
		/**
		 * @var string
		 */
		public $code; // eg. ar_EG, en_GB
		/**
		 * @var
		 */
		public $encoding; // eg. UTF-8, CP1256, ISO8859-1
		/**
		 * @var string
		 */
		public $dir; // Currently support for Left-to-Right (ltr) and
		// Right-To-Left (rtl)
		/**
		 * @var
		 */
		public $is_locale_file;

		/**
		 * @param        $name
		 * @param        $code
		 * @param        $encoding
		 * @param string $dir
		 */
		public function __construct($name, $code, $encoding, $dir = 'ltr') {
			$this->name = $name;
			$this->code = $code ? $code : 'en_GB';
			$this->encoding = $encoding;
			$this->dir = $dir;
		}

		/**
		 * @param $code
		 */
		public function set_language($code) {
			$changed = $this->code != $code;
			$lang = Arr::search_value($code, Config::get('languages.installed'), 'code');
			if ($lang && $changed) {
				// flush cache as we can use several languages in one account
				Files::flush_dir(COMPANY_PATH . '/js_cache');
				$this->name = $lang['name'];
				$this->code = $lang['code'];
				$this->encoding = $lang['encoding'];
				$this->dir = isset($lang['rtl']) ? 'rtl' : 'ltr';
				$locale = DOCROOT . "lang/" . $this->code . "/locale.php";
				$this->is_locale_file = file_exists($locale);
			}
			$_SESSION['get_text']->set_language($this->code, $this->encoding);
			$_SESSION['get_text']->add_domain($this->code, PATH_TO_ROOT . "/lang");
			// Necessary for ajax calls. Due to bug in php 4.3.10 for this
			// version set globally in php.ini
			ini_set('default_charset', $this->encoding);
			if (isset($_SESSION["App"]) && $changed) {
				Session::i()->App->init();
			} // refresh menu
		}
	}

	if (!function_exists("_")) {
		/**
		 * @param $text
		 *
		 * @return mixed
		 */
		function _($text) {
			$retVal = $_SESSION['get_text']->gettext($text);
			if ($retVal == "") {
				return $text;
			}
			return $retVal;
		}
	}
?>