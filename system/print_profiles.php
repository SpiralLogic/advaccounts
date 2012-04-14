<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  //require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");

  Page::start(_($help_context = "Printing Profiles"), SA_PRINTPROFILE);
  $selected_id = get_post('profile_id', '');

  if (get_post('submit')) {
    $error = 0;
    if ($_POST['profile_id'] == '' && empty($_POST['name'])) {
      $error = 1;
      Event::error(_("Printing profile name cannot be empty."));
      JS::set_focus('name');
    }
    if (!$error) {
      $prof = array('' => get_post('Prn')); // store default value/profile name
      foreach (get_reports() as $rep => $descr) {
        $val = get_post('Prn' . $rep);
        $prof[$rep] = $val;
      }
      if ($_POST['profile_id'] == '') {
        $_POST['profile_id'] = get_post('name');
      }
      Printer::update_profile($_POST['profile_id'], $prof);
      if ($selected_id == '') {
        Event::success(_('New printing profile has been created'));
        clear_form($selected_id);
      }
      else {
        Event::success(_('Printing profile has been updated'));
      }
    }
  }
  if (get_post('delete')) {
    if (!check_delete(get_post('name'))) {
      Printer::delete_profile($selected_id);
      Event::notice(_('Selected printing profile has been deleted'));
      clear_form($selected_id);
    }
  }
  if (get_post('_profile_id_update')) {
    Ajax::i()->activate('_page_body');
  }
  start_form();
  start_table();
  Reports_UI::print_profiles_row(_('Select printing profile') . ':', 'profile_id', NULL, _('New printing profile'), TRUE);
  end_table();
  echo '<hr>';
  start_table();
  if (get_post('profile_id') == '') {
    text_row(_("Printing Profile Name") . ':', 'name', NULL, 30, 30);
  }
  else {
    label_cells(_("Printing Profile Name") . ':', get_post('profile_id'));
  }
  end_table(1);
  $result = Printer::get_profile(get_post('profile_id'));
  $prints = array();
  while ($myrow = DB::fetch($result)) {
    $prints[$myrow['report']] = $myrow['printer'];
  }
  start_table('tablestyle');
  $th = array(_("Report Id"), _("Description"), _("Printer"));
  table_header($th);
  $k = 0;
  $unkn = 0;
  foreach (get_reports() as $rep => $descr) {
    alt_table_row_color($k);
    label_cell($rep == '' ? '-' : $rep, 'class=center');
    label_cell($descr == '' ? '???<sup>1)</sup>' : _($descr));
    $_POST['Prn' . $rep] = isset($prints[$rep]) ? $prints[$rep] : '';
    echo '<td>';
    echo Reports_UI::printers('Prn' . $rep, NULL, $rep == '' ? _('Browser support') : _('Default'));
    echo '</td>';
    if ($descr == '') {
      $unkn = 1;
    }
    end_row();
  }
  end_table();
  if ($unkn) {
    Event::warning('<sup>1)</sup>&nbsp;-&nbsp;' . _("no title was found in this report definition file."), 0, 1, '');
  }
  else {
    echo '<br>';
  }
  Display::div_start('controls');
  if (get_post('profile_id') == '') {
    submit_center('submit', _("Add New Profile"), TRUE, '', 'default');
  }
  else {
    submit_center_first('submit', _("Update Profile"), _('Update printer profile'), 'default');
    submit_center_last('delete', _("Delete Profile"), _('Delete printer profile (only if not used by any user)'), TRUE);
  }
  Display::div_end();
  end_form();
  Page::end();
  // Returns array of defined reports
  //
  function get_reports() {
    if (Config::get('debug.enabled') || !isset($_SESSION['reports'])) {
      // to save time, store in session.
      $paths = array(
        PATH_TO_ROOT . '/reporting/', COMPANY_PATH . 'reporting/'
      );
      $reports = array('' => _('Default printing destination'));
      foreach ($paths as $dirno => $path) {
        $repdir = opendir($path);
        while (FALSE !== ($fname = readdir($repdir))) {
          // reports have filenames in form rep(repid).php
          // where repid must contain at least one digit (reports_main.php is not ;)
          if (is_file($path . $fname) //				&& preg_match('/.*[^0-9]([0-9]+)[.]php/', $fname, $match))
            && preg_match('/rep(.*[0-9]+.*)[.]php/', $fname, $match)
          ) {
            $repno = $match[1];
            $title = '';
            $line = file_get_contents($path . $fname);
            if (preg_match('/.*(ADVReport\()\s*_\([\'"]([^\'"]*)/', $line, $match)) {
              $title = trim($match[2]);
            }
            else // for any 3rd party printouts without ADVReport() class use
            {
              if (preg_match('/.*(\$Title).*[\'"](.*)[\'"].+/', $line, $match)) {
                $title = trim($match[2]);
              }
            }
            $reports[$repno] = $title;
          }
        }
        closedir();
      }
      ksort($reports);
      $_SESSION['reports'] = $reports;
    }
    return $_SESSION['reports'];
  }

  /**
   * @param $selected_id
   */
  function clear_form(&$selected_id) {
    $selected_id = '';
    $_POST['name'] = '';
    Ajax::i()->activate('_page_body');
  }

  /**
   * @param $name
   *
   * @return int
   */
  function check_delete($name) {
    // check if selected profile is used by any user
    if ($name == '') {
      return 0;
    } // cannot delete system default profile
    $sql = "SELECT * FROM users WHERE print_profile=" . DB::escape($name);
    $res = DB::query($sql, 'cannot check printing profile usage');
    return DB::num_rows($res);
  }


