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
	class Session extends Input
	{

		/**
		 * @static
		 * @return Session|mixed
		 */
		public static function i() {

				if (static::$i === null) {
				static::$i = new static;
			}
			return static::$i;
		}
		/**
		 * @static
		 *
		 */
		public static function kill() {
			session_unset();
			session_destroy();
		}
		/**
		 * @static
		 *
		 */
		public static function regenerate() {
			session_regenerate_id();
		}
		/**
		 * @var Session
		 */
		private static $i = null;
		/***
		 * @var gettextNativeSupport|gettext_php_support
		 */
		public static $get_text;
		/**
		 * @var array
		 */
		protected $_session = array();
		/**
		 *
		 */
		final protected function __construct() {
			ini_set('session.gc_maxlifetime', 3200); // 10hrs
			session_name('ADV' . md5($_SERVER['SERVER_NAME']));
			if (class_exists('Memcached', false)) {
				ini_set('session.save_handler', 'Memcached');
				ini_set('session.save_path', '127.0.0.1:11211');
				if (Memcached::HAVE_IGBINARY) {
					ini_set('session.serialize_handler', 'igbinary');
				}
			}
				if (!session_start()) {
				ini_set('session.save_handler', 'files');
				if (!session_start()) {
					die('sessions fucked out');
				}
			}
			if (isset($_SESSION['HTTP_USER_AGENT'])) {
				if ($_SESSION['HTTP_USER_AGENT'] != sha1($_SERVER['HTTP_USER_AGENT'])) {
				}
			} else {
				$_SESSION['HTTP_USER_AGENT'] = sha1($_SERVER['HTTP_USER_AGENT']);
			}
			header("Cache-control: private");

			$this->setTextSupport();
						Language::set();

			$this->_session = &$_SESSION;
			// Ajax communication object

			if (class_exists('Ajax', false)) {
				Ajax::i();
			}
		}
		/**
		 * @return mixed
		 */
		protected function setTextSupport() {
			if (isset($_SESSION['get_text'])) {
				static::$get_text = $_SESSION['get_text'];
				return;
			}
			static::$get_text = $_SESSION['get_text'] = gettextNativeSupport::init();
		}
		/**
		 * @param $var
		 *
		 * @return null
		 */
		public function __get($var) {
			return isset($this->_session[$var]) ? $this->_session[$var] : null;
		}
		/**
		 * @param $var
		 * @param $value
		 */
		public function __set($var, $value) {
			$this->_session[$var] = $value;
		}
	}
