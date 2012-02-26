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

	require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
	Page::start(_($help_context = "System Diagnostics"), SA_SETUPCOMPANY);
	// Type of requirement for positive test result
	$test_level = array(
		0 => _('Info'), 1 => _('Optional'), 2 => _('Recomended'), 3 => _('Required ')
	);
	$system_tests = array(
		'tst_mysql',
		'tst_php',
		'tst_server',
		'tst_system',
		'tst_browser',
		'tst_gettext',
		'tst_debug',
		'tst_logging',
		'tst_dbversion',
		'tst_subdirs',
		'tst_langs',
		'tst_tmpdir',
		'tst_sessionhandler',
		'tst_sessionpath',
		'tst_config',
		'tst_extconfig'
	);

	start_table('tablestyle width90');
	$th = array(_("Test"), _('Test type'), _("Value"), _("Comments"));
	table_header($th);
	$k = 0; //row colour counter
	foreach ($system_tests as $test) {
		alt_table_row_color($k);
		$result = call_user_func($test);
		if (!$result) {
			continue;
		}
		label_cell($result['descr']);
		label_cell($test_level[$result['type']]);
		$res = isset($result['test']) ? implode('<br>', (array)$result['test']) : $result['test'];
		label_cell($res);
		$comm = isset($result['comments']) ? implode('<br>', (array)$result['comments']) : '';
		$color = ($result['result'] ? 'green' : ($result['type'] == 3 ? 'red' : ($result['type'] == 2 ? 'orange' : 'green')));
		label_cell("<span style='color:$color'>" . ($result['result'] ? _('Ok') : '<span class="bold">' . $comm . '</span>') . '</span>');
		end_row();
	}
	end_table();
	Page::end();
	function tst_mysql() {
		$test['descr'] = _('MySQL version') . ' >3.23.58';
		$test['type'] = 3;
		$test['test'] = DB::getAttribute(PDO::ATTR_SERVER_VERSION);
		$test['result'] = $test['test'] > '3.23.58';
		$test['comments'] = _('Upgrade MySQL server to version at least 3.23.58');
		return $test;
	}

	function tst_php() {
		$test['descr'] = _('PHP version') . ' >4.3.2';
		$test['type'] = 3;
		$test['test'] = phpversion();
		$test['result'] = $test['test'] > '4.3.2';
		$test['comments'] = _('Upgrade PHP to version at least 4.3.2');
		return $test;
	}

	function tst_system() {
		$test['descr'] = _('Server system');
		$test['type'] = 0;
		$test['test'] = PHP_OS;
		$test['result'] = true;
		return $test;
	}

	function tst_sessionpath() {
		$test['descr'] = _('Session save path');
		$test['type'] = 0;
		$test['test'] = session_save_path();
		$test['result'] = true;
		return $test;
	}

	function tst_sessionhandler() {
		$test['descr'] = _('Session handler');
		$test['type'] = 0;
		$test['test'] = session_module_name();
		$test['result'] = true;
		return $test;
	}

	function tst_browser() {
		$test['descr'] = _('Browser type');
		$test['type'] = 0;
		$test['test'] = $_SERVER['HTTP_USER_AGENT'];
		$test['result'] = true;
		$test['comments'] = _('Any browser is supported');
		return $test;
	}

	function tst_server() {
		$test['descr'] = _('Http server type');
		$test['test'] = $_SERVER['SERVER_SOFTWARE'];
		$test['type'] = 0;
		$test['result'] = true;
		$test['comments'] = _('Any server is supported');
		return $test;
	}

	function tst_gettext() {
		$test['descr'] = _('Native gettext');
		$test['test'] = function_exists('gettext') ? _('Yes') : _('No');
		$test['type'] = 1;
		$test['result'] = true;
		$test['comments'] = _('In case of no getext support, php emulation is used');
		return $test;
	}

	function tst_debug() {
		$test['descr'] = _('Debugging mode');
		$test['type'] = 0;
		$test['test'] = Config::get('debug') ? _("Yes") : _("No");
		$test['result'] = Config::get('debug') != 0;
		$test['comments'] = _('To switch debugging on set true in config.php file');
		return $test;
	}

	function tst_logging() {
		$test['descr'] = _('Error logging');
		$test['type'] = 2;
		// if error lgging is on, but log file does not exists try write
		if (Config::get('logs_error_file') && !is_file(Config::get('logs_error_file'))) {
			fclose(fopen(Config::get('logs_error_file'), 'w'));
		}
		$test['result'] = Config::get('logs_error_file') != '' && is_writable(Config::get('logs_error_file'));
		$test['test'] = Config::get('logs_error_file') == '' ? _("Disabled") : Config::get('logs_error_file');
		if (Config::get('logs_error_file') == '') {
			$test['comments'] = _('To switch error logging set $error_logging in config.php file');
		}
		else {
			if (!is_writable(Config::get('logs_error_file'))) {
				$test['comments'] = _('Log file is not writeable');
			}
		}
		return $test;
	}

	//
	//	Installed ADV database structure version
	//
	function tst_dbversion() {
		$test['descr'] = _('Current database version');
		$test['type'] = 3;
		$test['test'] = DB_Company::get_pref('version_id');
		$test['result'] = $test['test'] == '2.2';
		$test['comments'] = _('Database structure seems to be not upgraded to current version') . ' (2.2)';
		return $test;
	}

	function tst_subdirs() {
		$comp_subdirs = array('images', 'pdf_files', 'backup', 'js_cache');
		$test['descr'] = _('Company subdirectories consistency');
		$test['type'] = 3;
		$test['test'] = array(COMPANY_PATH . '*');
		foreach ($comp_subdirs as $sub) {
			$test['test'][] = COMPANY_PATH . '*/' . $sub;
		}
		$test['result'] = true;
		if (!is_dir(COMPANY_PATH) || !is_writable(COMPANY_PATH)) {
			$test['result'] = false;
			$test['comments'][] = sprintf(_("'%s' is not writeable"), COMPANY_PATH);
			return $test;
		}
		;
		foreach (Config::get_all('db') as $n => $comp) {
			$path = COMPANY_PATH . "";
			if (!is_dir($path) || !is_writable($path)) {
				$test['result'] = false;
				$test['comments'][] = sprintf(_("'%s' is not writeable"), $path);
				continue;
			}
			;
			foreach ($comp_subdirs as $sub) {
				$spath = $path . '/' . $sub;
				if (!is_dir($spath) || !is_writable($spath)) {
					$test['result'] = false;
					$test['comments'][] = sprintf(_("'%s' is not writeable"), $spath);
				}
				else {
					$dir = opendir($spath);
					while (false !== ($fname = readdir($dir))) {
						// check only *.js files. Manually installed package can contain other
						// non-writable files which are non-crucial for normal operations
						if (preg_match('/.*(\.js)/', $fname) && !is_writable("$spath/$fname")) {
							$test['result'] = false;
							$test['comments'][] = sprintf(_("'%s' is not writeable"), "$spath/$fname");
						}
					}
				}
			}
		}
		return $test;
	}

	function tst_tmpdir() {
		$test['descr'] = _('Temporary directory');
		$test['type'] = 3;
		$test['test'] = PATH_TO_ROOT . '/tmp';
		$test['result'] = is_dir($test['test']) && is_writable($test['test']);
		$test['comments'][] = sprintf(_("'%s' is not writeable"), $test['test']);
		return $test;
	}

	function tst_langs() {
		$test['descr'] = _('Language configuration consistency');
		$test['type'] = 3;
		$test['result'] = true;
		$test['comments'] = array();
		$old = setlocale(LC_MESSAGES, '0');
		$langs = array();
		foreach (Config::get('languages.installed') as $lang) {
			$langs[] = $lang['code'];
			if ($lang['code'] == 'en_AU') {
				continue;
			} // native ADV language
			$file = PATH_TO_ROOT . '/lang/' . $lang['code'] . '/LC_MESSAGES/' . $lang['code'];
			$file .= function_exists('gettext') ? '.mo' : '.po';
			if (!is_file($file)) {
				$test['result'] = false;
				$test['comments'][] = sprintf(_('Missing %s translation file.'), $file);
			}
			if (!setlocale(LC_MESSAGES, $lang['code'] . "." . $lang['encoding'])) {
				$test['result'] = false;
				$test['comments'][] = sprintf(_('Missing system locale: %s'), $lang['code'] . "." . $lang['encoding']);
			}
			;
		}
		setlocale(LC_MESSAGES, $old);
		$test['test'] = $langs;
		return $test;
	}

	function tst_config() {
		$test['descr'] = _('Main config file');
		$test['type'] = 2;
		$test['test'] = PATH_TO_ROOT . '/config/config.php';
		$test['result'] = is_file($test['test']) && !is_writable($test['test']);
		$test['comments'][] = sprintf(_("'%s' file should be read-only"), $test['test']);
		return $test;
	}

	function tst_extconfig() {
		$test['descr'] = _('Extensions configuration files');
		$test['type'] = 3;
		$test['test'] = PATH_TO_ROOT . '/config/extensions.php';
		$test['result'] = is_file($test['test']) && is_writable($test['test']);
		$test['test'] . ',' . COMPANY_PATH . '*/extensions.php';
		$test['comments'][] = sprintf(_("'%s' file should be writeable"), $test['test']);
		foreach (Config::get_all('db') as $n => $comp) {
			$path = COMPANY_PATH . "$n";
			if (!is_dir($path)) {
				continue;
			}
			$path .= "/config/extensions.php";
			if (!is_file($path) || !is_writable($path)) {
				$test['result'] = false;
				$test['comments'][] = sprintf(_("'%s' is not writeable"), $path);
				continue;
			}
			;
		}
		return $test;
	}

?>
