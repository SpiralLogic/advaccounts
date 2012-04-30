<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/

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
    $res = isset($result['test']) ? implode('<br>', (array) $result['test']) : $result['test'];
    label_cell($res);
    $comm = isset($result['comments']) ? implode('<br>', (array) $result['comments']) : '';
    $color = ($result['result'] ? 'green' : ($result['type'] == 3 ? 'red' : ($result['type'] == 2 ? 'orange' : 'green')));
    label_cell("<span style='color:$color'>" . ($result['result'] ? _('Ok') :
      '<span class="bold">' . $comm . '</span>') . '</span>');
    end_row();
  }
  end_table();
  Page::end();
  /**
   * @return array
   */
  function tst_mysql() {
    $test['descr'] = _('MySQL version') . ' >5.0';
    $test['type'] = 3;
    $test['test'] = DB::getAttribute(PDO::ATTR_SERVER_VERSION);
    $test['result'] = $test['test'] > '5.0';
    $test['comments'] = _('Upgrade MySQL server to version at least 5.1');
    return $test;
  }

  /**
   * @return array
   */
  function tst_php() {
    $test['descr'] = _('PHP version') . ' >5.4';
    $test['type'] = 3;
    $test['test'] = phpversion();
    $test['result'] = $test['test'] > '5.3';
    $test['comments'] = _('Upgrade PHP to version at least 5.4');
    return $test;
  }

  /**
   * @return array
   */
  function tst_system() {
    $test['descr'] = _('Server system');
    $test['type'] = 0;
    $test['test'] = PHP_OS;
    $test['result'] = TRUE;
    return $test;
  }

  /**
   * @return array
   */
  function tst_sessionpath() {
    $test['descr'] = _('Session save path');
    $test['type'] = 0;
    $test['test'] = session_save_path();
    $test['result'] = TRUE;
    return $test;
  }

  /**
   * @return array
   */
  function tst_sessionhandler() {
    $test['descr'] = _('Session handler');
    $test['type'] = 2;
    $test['test'] = session_module_name();
    $test['result'] = ($test['test'] == 'memcached');
    $test['comments'] = 'For better performance Memcached is recommended.';
    return $test;
  }

  /**
   * @return array
   */
  function tst_browser() {
    $test['descr'] = _('Browser type');
    $test['type'] = 0;
    $test['test'] = $_SERVER['HTTP_USER_AGENT'];
    $test['result'] = TRUE;
    $test['comments'] = _('Any browser is supported');
    return $test;
  }

  /**
   * @return array
   */
  function tst_server() {
    $test['descr'] = _('Http server type');
    $test['test'] = $_SERVER['SERVER_SOFTWARE'];
    $test['type'] = 0;
    $test['result'] = TRUE;
    $test['comments'] = _('Any server is supported');
    return $test;
  }

  /**
   * @return array
   */
  function tst_gettext() {
    $test['descr'] = _('Native gettext');
    $test['test'] = function_exists('gettext') ? _('Yes') : _('No');
    $test['type'] = 1;
    $test['result'] = TRUE;
    $test['comments'] = _('In case of no getext support, php emulation is used');
    return $test;
  }

  /**
   * @return array
   */
  function tst_debug() {
    $test['descr'] = _('Debugging mode');
    $test['type'] = 0;
    $test['test'] = Config::get('debug.enabled') ? _("Yes") : _("No");
    $test['result'] = Config::get('debug.enabled') != 0;
    $test['comments'] = _('To switch debugging on set true in config.php file');
    return $test;
  }

  /**
   * @return array
   */
  function tst_logging() {
    $test['descr'] = _('Error logging');
    $test['type'] = 2;
    // if error lgging is on, but log file does not exists try write
    if (Config::get('debug.log_file') && !is_file(Config::get('debug.log_file'))) {
      fclose(fopen(Config::get('debug.log_file'), 'w'));
    }
    $test['result'] = Config::get('debug.log_file') != '' && is_writable(Config::get('debug.log_file'));
    $test['test'] = Config::get('debug.log_file') == '' ? _("Disabled") : Config::get('debug.log_file');
    if (Config::get('debug.log_file') == '') {
      $test['comments'] = _('To switch error logging set $error_logging in config.php file');
    }
    else {
      if (!is_writable(Config::get('debug.log_file'))) {
        $test['comments'] = _('Log file is not writeable');
      }
    }
    return $test;
  }

  //
  //	Installed ADV database structure version
  //
  /**
   * @return array
   */
  function tst_subdirs() {
    $comp_subdirs = array('images', 'pdf_files', 'backup', 'js_cache');
    $test['descr'] = _('Company subdirectories consistency');
    $test['type'] = 3;
    $test['test'] = array(COMPANY_PATH . '*');
    foreach ($comp_subdirs as $sub) {
      $test['test'][] = COMPANY_PATH . '*/' . $sub;
    }
    $test['result'] = TRUE;
    if (!is_dir(COMPANY_PATH) || !is_writable(COMPANY_PATH)) {
      $test['result'] = FALSE;
      $test['comments'][] = sprintf(_("'%s' is not writeable"), COMPANY_PATH);
      return $test;
    }
    ;
    foreach (Config::get_all('db') as $n => $comp) {
      $path = COMPANY_PATH . "";
      if (!is_dir($path) || !is_writable($path)) {
        $test['result'] = FALSE;
        $test['comments'][] = sprintf(_("'%s' is not writeable"), $path);
        continue;
      }
      ;
      foreach ($comp_subdirs as $sub) {
        $spath = $path . '/' . $sub;
        if (!is_dir($spath) || !is_writable($spath)) {
          $test['result'] = FALSE;
          $test['comments'][] = sprintf(_("'%s' is not writeable"), $spath);
        }
        else {
          $dir = opendir($spath);
          while (FALSE !== ($fname = readdir($dir))) {
            // check only *.js files. Manually installed package can contain other
            // non-writable files which are non-crucial for normal operations
            if (preg_match('/.*(\.js)/', $fname) && !is_writable("$spath/$fname")) {
              $test['result'] = FALSE;
              $test['comments'][] = sprintf(_("'%s' is not writeable"), "$spath/$fname");
            }
          }
        }
      }
    }
    return $test;
  }

  /**
   * @return array
   */
  function tst_tmpdir() {
    $test['descr'] = _('Temporary directory');
    $test['type'] = 3;
    $test['test'] = DOCROOT . 'tmp';
    $test['result'] = is_dir($test['test']) && is_writable($test['test']);
    $test['comments'][] = sprintf(_("'%s' is not writeable"), $test['test']);
    return $test;
  }

  /**
   * @return array
   */
  function tst_langs() {
    $test['descr'] = _('Language configuration consistency');
    $test['type'] = 3;
    $test['result'] = TRUE;
    $test['comments'] = array();
    $old = setlocale(LC_MESSAGES, '0');
    $langs = array();
    foreach (Config::get('languages.installed') as $lang) {
      $langs[] = $lang['code'];
      if ($lang['code'] == 'en_AU') {
        continue;
      } // native ADV language
      $file = LANG_PATH . $lang['code'] . DS.'LC_MESSAGES' .DS. $lang['code'];
      $file .= function_exists('gettext') ? '.mo' : '.po';
      if (!is_file($file)) {
        $test['result'] = FALSE;
        $test['comments'][] = sprintf(_('Missing %s translation file.'), $file);
      }
      if (!setlocale(LC_MESSAGES, $lang['code'] . "." . $lang['encoding'])) {
        $test['result'] = FALSE;
        $test['comments'][] = sprintf(_('Missing system locale: %s'), $lang['code'] . "." . $lang['encoding']);
      }
      ;
    }
    setlocale(LC_MESSAGES, $old);
    $test['test'] = $langs;
    return $test;
  }

  /**
   * @return array
   */
  function tst_config() {
    $test['descr'] = _('Main config file');
    $test['type'] = 2;
    $test['test'] = DOCROOT . 'config'.DS.'config.php';
    $test['result'] = is_file($test['test']) && !is_writable($test['test']);
    $test['comments'][] = sprintf(_("'%s' file should be read-only"), $test['test']);
    return $test;
  }

  /**
   * @return array
   */
  function tst_extconfig() {
    $test['descr'] = _('Extensions configuration files');
    $test['type'] = 3;
    $test['test'] = DOCROOT . 'config'.DS.'extensions.php';
    $test['result'] = is_file($test['test']) && is_writable($test['test']);
    $test['comments'][] = sprintf(_("'%s' file should be writeable"), $test['test']);
    foreach (Config::get_all('db') as $n => $comp) {
      $path = COMPANY_PATH . "$n";
      if (!is_dir($path)) {
        continue;
      }
      $path .= "/config/extensions.php";
      if (!is_file($path) || !is_writable($path)) {
        $test['result'] = FALSE;
        $test['comments'][] = sprintf(_("'%s' is not writeable"), $path);
        continue;
      }
      ;
    }
    return $test;
  }

