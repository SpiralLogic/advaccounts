<?php
	//
	// Copyright (c) 2003 Laurent Bedubourg
	//
	// This library is free software; you can redistribute it and/or
	// modify it under the terms of the GNU Lesser General Public
	// License as published by the Free Software Foundation; either
	// version 2.1 of the License, or (at your option) any later version.
	//
	// This library is distributed in the hope that it will be useful,
	// but WITHOUT ANY WARRANTY; without even the implied warranty of
	// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
	// Lesser General Public License for more details.
	//
	// You should have received a copy of the GNU Lesser General Public
	// License along with this library; if not, write to the Free Software
	// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
	//
	// Authors: Laurent Bedubourg <laurent.bedubourg@free.fr>
	//
	//require_once "PEAR.php";
	/**
	 * Interface to gettext native support.
	 *
	 * @author Laurent Bedubourg <laurent.bedubourg@free.fr>
	 * @access private
	 */
	class gettextNativeSupport
	{
		/**
		 * @var array
		 */
		public $_interpolation_vars = array();
		/**
		 *
		 */
		const GETTEXT_NATIVE = 1;
		/**
		 *
		 */
		const GETTEXT_PHP = 2;

		/**
		 * @param $str
		 *
		 * @return int
		 */
		public function raise_error($str) {
			Event::error($str);
			return 1;
		}

		/**
		 * @param $err
		 *
		 * @return bool
		 */
		public function is_error($err) {
			return $err > 0;
		}

		/***
		 * @static
		 *
		 * @param int $managerType
		 *
		 * @return gettext_php_support|gettextNativeSupport
		 */
		public static function i($managerType = self::GETTEXT_NATIVE) {
			if ($managerType == self::GETTEXT_NATIVE) {
				if (function_exists('gettext')) {
					return new static();
				}
			}
			// fail back to php support
			return new gettext_php_support();
		}

		/**
		 * @param $lang_code
		 * @param $encoding
		 *
		 * @return int
		 */
		function set_language($lang_code, $encoding) {
			putenv("LANG=$lang_code");
			putenv("LC_ALL=$lang_code");
			putenv("LANGUAGE=$lang_code");
			//$set = setlocale(LC_ALL, "$lang_code");
			//$set = setlocale(LC_ALL, "$encoding");
			$set = setlocale(LC_ALL, $lang_code . "." . $encoding);
			setlocale(LC_NUMERIC, 'C'); // important for numeric presentation etc.
			if ($set === false) {
				$str = sprintf('language code "%s", encoding "%s" not supported by your system',
					$lang_code, $encoding);
				//$err = new GetText_Error($str);
				//return PEAR::raise_error($err);
				return $this->raise_error("1 " . $str);
			}
			return 0;
		}

		/**
		 * @param			$domain
		 * @param bool $path
		 */
		function add_domain($domain, $path = false) {
			if ($path === false) {
				bindtextdomain($domain, "./locale/");
			} else {
				bindtextdomain($domain, $path);
			}
			//bind_textdomain_codeset($domain, $encoding);
			textdomain($domain);
		}

		/**
		 * @param $key
		 *
		 * @return string
		 */
		function _get_translation($key) {
			return gettext($key);
		}

		/**
		 * Reset interpolation variables.
		 */
		function reset() {
			$this->_interpolation_vars = array();
		}

		/**
		 * @param $key
		 * @param $value
		 */
		function set_var($key, $value) {
			$this->_interpolation_vars[$key] = $value;
		}

		/**
		 * @param $hash
		 */
		function set_vars($hash) {
			$this->_interpolation_vars = array_merge($this->_interpolation_vars,
				$hash);
		}

		/**
		 * @param $key
		 *
		 * @return int|mixed|string
		 */
		function gettext($key) {
			$value = $this->_get_translation($key);
			if ($value === false) {
				$str = sprintf('Unable to locate gettext key "%s"', $key);
				//$err = new GetText_Error($str);
				//return PEAR::raise_error($err);
				return $this->raise_error("2 " . $str);
			}
			while (preg_match('/\$\{(.*?)\}/sm', $value, $m)) {
				list($src, $var) = $m;
				// retrieve variable to interpolate in context, throw an exception
				// if not found.
				$var2 = $this->_get_var($var);
				if ($var2 === false) {
					$str = sprintf('Interpolation error, var "%s" not set', $var);
					//$err = new GetText_Error($str);
					//return PEAR::raise_error($err);
					return $this->raise_error("3 " . $str);
				}
				$value = str_replace($src, $var2, $value);
			}
			return $value;
		}

		/**
		 * @param $name
		 *
		 * @return bool
		 */
		function _get_var($name) {
			if (!array_key_exists($name, $this->_interpolation_vars)) {
				return false;
			}
			return $this->_interpolation_vars[$name];
		}
	}

	/**
	 * Implementation of get_text support for PHP.
	 *
	 * This implementation is abble to cache .po files into php files returning the
	 * domain translation hashtable.
	 *
	 * @access private
	 * @author Laurent Bedubourg <laurent.bedubourg@free.fr>
	 */
	class gettext_php_support extends gettextNativeSupport
	{
		/**
		 * @var string
		 */
		public $_path = 'locale/';
		/**
		 * @var bool
		 */
		public $_lang_code = false;
		/**
		 * @var array
		 */
		public $_domains = array();
		/**
		 * @var int
		 */
		public $_end = -1;
		/**
		 * @var array
		 */
		public $_jobs = array();

		/**
		 * Set the translation domain.
		 *
		 * @param	string $lang_code -- language code
		 * @param				 $encoding
		 *
		 * @return int
		 * @throws GetText_Error
		 */
		function set_language($lang_code, $encoding) {
			// if language already set, try to reload domains
			if ($this->_lang_code !== false and $this->_lang_code != $lang_code) {
				foreach ($this->_domains as $domain)
				{
					$this->_jobs[] = array($domain->name, $domain->path);
				}
				$this->_domains = array();
				$this->_end = -1;
			}
			$this->_lang_code = $lang_code;
			// this allow us to set the language code after
			// domain list.
			while (count($this->_jobs) > 0)
			{
				list($domain, $path) = array_shift($this->_jobs);
				$err = $this->add_domain($domain, $path);
				// error raised, break jobs
				/*if (PEAR::is_error($err)) {
						return $err;
					}*/
				if ($this->is_error($err)) {
					return $err;
				}
			}
			return 0;
		}

		/**
		 * Add a translation domain.
		 *
		 * @param string $domain		-- Domain name
		 * @param string $path			optional -- Repository path
		 *
		 * @return int
		 * @throws GetText_Error
		 */
		function add_domain($domain, $path = "./locale/") {
			if (array_key_exists($domain, $this->_domains)) {
				return 0;
			}
			if (!$this->_lang_code) {
				$this->_jobs[] = array($domain, $path);
				return 0;
			}
			$err = $this->_load_domain($domain, $path);
			if ($err != 0) {
				return $err;
			}
			$this->_end++;
			return 0;
		}

		/**
		 * Load a translation domain file.
		 *
		 * This method cache the translation hash into a php file unless
		 * GETTEXT_NO_CACHE is defined.
		 *
		 * @param	string $domain		-- Domain name
		 * @param	string $path			optional -- Repository
		 *
		 * @return int
		 * @throws GetText_Error
		 * @access private
		 */
		function _load_domain($domain, $path = "./locale") {
			$src_domain = $path . "/$this->_lang_code/LC_MESSAGES/$domain.po";
			$php_domain = $path . "/$this->_lang_code/LC_MESSAGES/$domain.php";
			if (!file_exists($src_domain)) {
				$str = sprintf('Domain file "%s" not found.', $src_domain);
				//$err = new GetText_Error($str);
				//return PEAR::raise_error($err);
				return $this->raise_error("4 " . $str);
			}
			$d = new gettext_domain();
			$d->name = $domain;
			$d->path = $path;
			if (!file_exists($php_domain) || (filemtime($php_domain) < filemtime($src_domain))) {
				// parse and compile translation table
				$parser = new gettext_php_support_parser();
				$hash = $parser->parse($src_domain);
				if (!defined('GETTEXT_NO_CACHE')) {
					$comp = new gettext_php_support_compiler();
					$err = $comp->compile($hash, $src_domain);
					/*if (PEAR::is_error($err)) {
							 return $err;
						 }*/
					if ($this->is_error($err)) {
						return $err;
					}
				}
				$d->_keys = $hash;
			} else {
				/** @noinspection PhpIncludeInspection */
				$d->_keys = include $php_domain;
			}
			$this->_domains[] = $d;
			return 0;
		}

		/**
		 * Implementation of gettext message retrieval.
		 *
		 * @param $key
		 *
		 * @return mixed
		 */
		function _get_translation($key) {
			for ($i = $this->_end; $i >= 0; $i--)
			{
				/** @noinspection PhpUndefinedMethodInspection */
				if ($this->_domains[$i]->has_key($key)) {
					/** @noinspection PhpUndefinedMethodInspection */
					return $this->_domains[$i]->get($key);
				}
			}
			return $key;
		}
	}

	/**
	 * Class representing a domain file for a specified language.
	 *
	 * @access private
	 * @author Laurent Bedubourg <laurent.bedubourg@free.fr>
	 */
	class gettext_domain
	{
		/**
		 * @var
		 */
		public $name;
		/**
		 * @var
		 */
		public $path;
		/**
		 * @var array
		 */
		public $_keys = array();

		/**
		 * @param $key
		 *
		 * @return bool
		 */
		function has_key($key) {
			return array_key_exists($key, $this->_keys);
		}

		/**
		 * @param $key
		 *
		 * @return mixed
		 */
		function get($key) {
			return $this->_keys[$key];
		}
	}

	/**
	 * This class is used to parse gettext '.po' files into php associative arrays.
	 *
	 * @access private
	 * @author Laurent Bedubourg <laurent.bedubourg@free.fr>
	 */
	class gettext_php_support_parser
	{
		/**
		 * @var array
		 */
		public $_hash = array();
		/**
		 * @var
		 */
		public $_current_key;
		/**
		 * @var
		 */
		public $_current_value;

		/**
		 * @param $str
		 *
		 * @return int
		 */
		public function raise_error($str) {
			Event::error($str);
			return 1;
		}

		/**
		 * Parse specified .po file.
		 *
		 * @param $file
		 *
		 * @return hashtable
		 * @throws GetText_Error
		 */
		function parse($file) {
			$this->_hash = array();
			$this->_current_key = false;
			$this->_current_value = "";
			if (!file_exists($file)) {
				$str = sprintf('Unable to locate file "%s"', $file);
				//$err = new GetText_Error($str);
				//return PEAR::raise_error($err);
				return $this->raise_error($str);
			}
			$i = 0;
			$lines = file($file);
			foreach ($lines as $line)
			{
				$this->_parse_line($line, ++$i);
			}
			$this->_store_key();
			return $this->_hash;
		}

		/**
		 * Parse one po line.
		 *
		 * @access private
		 *
		 * @param $line
		 *
		 * @return
		 */
		function _parse_line($line) {
			if (preg_match('/^\s*?#/', $line)) {
				return;
			}
			if (preg_match('/^\s*?msgid \"(.*?)(?!<\\\)\"/', $line, $m)) {
				$this->_store_key();
				$this->_current_key = $m[1];
				return;
			}
			if (preg_match('/^\s*?msgstr \"(.*?)(?!<\\\)\"/', $line, $m)) {
				$this->_current_value .= $m[1];
				return;
			}
			if (preg_match('/^\s*?\"(.*?)(?!<\\\)\"/', $line, $m)) {
				$this->_current_value .= $m[1];
				return;
			}
		}

		/**
		 * Store last key/value pair into building hashtable.
		 *
		 * @access private
		 */
		function _store_key() {
			if ($this->_current_key === false) {
				return;
			}
			$this->_current_value = str_replace('\\n', "\n", $this->_current_value);
			$this->_hash[$this->_current_key] = $this->_current_value;
			$this->_current_key = false;
			$this->_current_value = "";
		}
	}

	/**
	 * This class write a php file from a gettext hashtable.
	 *
	 * The produced file return the translation hashtable on include.
	 *
	 * @throws GetText_Error
	 * @access private
	 * @author Laurent Bedubourg <laurent.bedubourg@free.fr>
	 */
	class gettext_php_support_compiler
	{
		/**
		 * Write hash in an includable php file.
		 *
		 * @param $str
		 *
		 * @return int
		 */
		public function raise_error($str) {
			Event::error($str);
			return 1;
		}

		/**
		 * @param $hash
		 * @param $source_path
		 *
		 * @return int
		 */
		function compile(&$hash, $source_path) {
			$dest_path = preg_replace('/\.po$/', '.php', $source_path);
			$fp = @fopen($dest_path, "w");
			if (!$fp) {
				$str = sprintf('Unable to open "%s" in write mode.', $dest_path);
				//$err = new GetText_Error($str);
				//return PEAR::raise_error($err);
				return $this->raise_error($str);
			}
			fwrite($fp, '<?php' . "\n");
			fwrite($fp, 'return array(' . "\n");
			foreach ($hash as $key => $value)
			{
				$key = str_replace("'", "\\'", $key);
				$value = str_replace("'", "\\'", $value);
				fwrite($fp, ' \'' . $key . '\' => \'' . $value . "',\n");
			}
			fwrite($fp, ');' . "\n");
			fwrite($fp, '?>');
			fclose($fp);
			return 0;
		}
	}


