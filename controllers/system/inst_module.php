<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  Page::start(_($help_context = "Install/Activate extensions"), SA_CREATEMODULES);
  list($Mode, $selected_id) = Page::simple_mode(true);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    if (handle_submit()) {
      if ($selected_id != -1) {
        Event::success(_("Extension data has been updated."));
      } else {
        Event::success(_("Extension has been installed."));
      }
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_DELETE) {
    handle_delete($selected_id);
    $Mode = MODE_RESET;
  }
  if (Input::post('Update')) {
    $exts = DB_Company::get_company_extensions();
    foreach ($exts as $i => $ext) {
      $exts[$i]['active'] = Form::hasPost('Active' . $i);
    }
    advaccounting::write_extensions($exts, Input::post('extset'));
    $installed_extensions = $exts;
    Event::notice(_('Current active extensions set has been saved.'));
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    unset($_POST);
  }
  Form::start(true);
  if (Form::isListUpdated('extset')) {
    Ajax::i()->activate('_page_body');
  }
  echo "<div class='center'>" . _('Extensions:') . "&nbsp;&nbsp;";
  echo Extensions::view('extset', null, true);
  echo "</div><br>";
  $set = Input::post('extset',null,-1);
  if ($set == -1) {
    display_extensions();
    display_ext_edit($Mode, $selected_id);
  } else {
    company_extensions($set);
  }
  Form::end();
  Page::end();
  /**
   * @param $extensions
   *
   * @return bool
   */
  function update_extensions($extensions)
  {
    if (!advaccounting::write_extensions($extensions)) {
      Event::notice(_("Cannot update system extensions list."));

      return false;
    }
    // update per company files
    $cnt = count(Config::get_all('db'));
    for ($i = 0; $i < $cnt; $i++) {
      $newexts = $extensions;
      // update 'active' status
      $exts = DB_Company::get_company_extensions($i);
      foreach ($exts as $key => $ext) {
        if (isset($newexts[$key])) {
          $newexts[$key]['active'] = $exts[$key]['active'];
        }
      }
      if (!advaccounting::write_extensions($newexts, $i)) {
        Event::notice(sprintf(_("Cannot update extensions list for company '%s'."), Config::get('db.' . $i)['name']));

        return false;
      }
    }

    return true;
  }

  /**
   * @param $id
   * @param $exts
   *
   * @return bool
   */
  function check_data($id, $exts)
  {
    if ($_POST['name'] == "") {
      Event::error(_("Extension name cannot be empty."));

      return false;
    }
    foreach ($exts as $n => $ext) {
      if ($_POST['name'] == $ext['name'] && $id != $n) {
        Event::error(_("Extension name have to be unique."));

        return false;
      }
    }
    if ($_POST['title'] == "") {
      Event::error(_("Extension title cannot be empty."));

      return false;
    }
    if ($_POST['path'] == "") {
      Event::error(_("Extension folder name cannot be empty."));

      return false;
    }
    if ($id == -1 && !is_uploaded_file($_FILES['uploadfile']['tmp_name'])) {
      Event::error(_("You have to select plugin file to upload"));

      return false;
    }

    return true;
  }

  /**
   * @param $selected_id
   *
   * @return bool
   */
  function handle_submit($selected_id)
  {
    global $next_extension_id;
    $extensions = DB_Company::get_company_extensions();
    if (!check_data($selected_id, $extensions)) {
      return false;
    }
    $id = $selected_id == -1 ? $next_extension_id : $selected_id;
    if ($selected_id != -1 && $extensions[$id]['type'] != 'plugin') {
      Event::error(_('Module installation support is not implemented yet. You have to do it manually.'));

      return;
    }
    $extensions[$id]['tab']    = $_POST['tab'];
    $extensions[$id]['name']   = $_POST['name'];
    $extensions[$id]['path']   = $_POST['path'];
    $extensions[$id]['title']  = $_POST['title'];
    $extensions[$id]['active'] = Form::hasPost('active');
    // Currently we support only plugin extensions here.
    $extensions[$id]['type'] = 'plugin';
    $directory               = DOCROOT . "modules/" . $_POST['path'];
    if (!file_exists($directory)) {
      mkdir($directory);
    }
    if (is_uploaded_file($_FILES['uploadfile']['tmp_name'])) {
      $extensions[$id]['filename'] = $_FILES['uploadfile']['name'];
      $file1                       = $_FILES['uploadfile']['tmp_name'];
      $file2                       = $directory . "/" . $_FILES['uploadfile']['name'];
      if (file_exists($file2)) {
        unlink($file2);
      }
      move_uploaded_file($file1, $file2);
    } else {
      $extensions[$id]['filename'] = Input::post('filename');
    }
    if (is_uploaded_file($_FILES['uploadfile2']['tmp_name'])) {
      $file1 = $_FILES['uploadfile2']['tmp_name'];
      $file2 = $directory . "/" . $_FILES['uploadfile2']['name'];
      if (file_exists($file2)) {
        unlink($file2);
      }
      move_uploaded_file($file1, $file2);
      $db_name = User::i()->company;
      DB_Utils::import($file2, Config::get('db.' . $db_name));
    }
    if (is_uploaded_file($_FILES['uploadfile3']['tmp_name'])) {
      $extensions[$id]['acc_file'] = $_FILES['uploadfile3']['name'];
      $file1                       = $_FILES['uploadfile3']['tmp_name'];
      $file2                       = $directory . "/" . $_FILES['uploadfile3']['name'];
      if (file_exists($file2)) {
        unlink($file2);
      }
      move_uploaded_file($file1, $file2);
    } else {
      $extensions[$id]['acc_file'] = Input::post('acc_file');
    }
    // security area guess for plugins
    if ($extensions[$id]['type'] == 'plugin') {
      $exttext = file_get_contents(DOCROOT . '/modules/' . $extensions[$id]['path'] . '/' . $extensions[$id]['filename']);
      $area    = SA_OPEN;
      if (preg_match('/.*\$page_security\s*=\s*[\'"]([^\'"]*)/', $exttext, $match)) {
        $area = trim($match[1]);
      }
      $extensions[$id]['access'] = $area;
    }
    if ($selected_id == -1) {
      $next_extension_id++;
    }
    if (!update_extensions($extensions)) {
      return false;
    }

    return true;
  }

  /**
   * @param $selected_id
   *
   * @return bool
   */
  function handle_delete($selected_id)
  {
    $extensions = DB_Company::get_company_extensions();
    $id         = $selected_id;
    $filename   = DOCROOT . ($extensions[$id]['type'] == 'plugin' ? "modules" . DS : DS) . $extensions[$id]['path'];
    Files::flush_dir($filename);
    rmdir($filename);
    unset($extensions[$id]);
    if (update_extensions($extensions)) {
      Event::notice(_("Selected extension has been successfully deleted"));
    }

    return true;
  }

  function display_extensions()
  {
    Table::start('tablestyle grid');
    $th = array(
      _("Name"), _("Tab"), _("Link text"), _("Folder"), _("Filename"), _("Access extensions"), "", ""
    );
    Table::header($th);
    $k    = 0;
    $mods = DB_Company::get_company_extensions();
    foreach ($mods as $i => $mod) {
      $is_mod = $mod['type'] == 'module';

      Cell::label($mod['name']);
      Cell::label($is_mod ? $mod['title'] : Display::access_string(ADVAccounting::i()->applications[$mod['tab']]->name, true));
      $ttl = Display::access_string($mod['title']);
      Cell::label($ttl[0]);
      Cell::label($mod['path']);
      Cell::label($mod['filename']);
      Cell::label(@$mod['acc_file']);
      if ($is_mod) {
        Cell::label(''); // not implemented (yet)
      } else {
        Form::buttonEditCell("Edit" . $i, _("Edit"));
      }
      Form::buttonDeleteCell("Delete" . $i, _("Delete"));
      Form::submitConfirm(MODE_DELETE . $i, _('You are about to delete this extension\nDo you want to continue?'));
      Row::end();
    }
    Table::end(1);
  }

  /**
   * @param $id
   */
  function company_extensions($id)
  {
    Table::start('tablestyle grid');
    $th = array(_("Name"), _("Tab"), _("Link text"), _("Active"));
    // get all available extensions and display
    // with current status stored in company directory.
    $mods = DB_Company::get_company_extensions();
    $exts = DB_Company::get_company_extensions($id);
    foreach ($mods as $key => $ins) {
      foreach ($exts as $ext) {
        if ($ext['name'] == $ins['name']) {
          $mods[$key]['active'] = @$ext['active'];
          continue 2;
        }
      }
    }
    Table::header($th);
    $k = 0;
    foreach ($mods as $i => $mod) {

      Cell::label($mod['name']);
      Cell::label($mod['type'] == 'module' ? $mod['title'] :
                    Display::access_string(ADVAccounting::i()->applications[$mod['tab']]->name, true));
      $ttl = Display::access_string($mod['title']);
      Cell::label($ttl[0]);
       Form::checkCells(null, 'Active' . $i, @$mod['active'] ? 1 : 0, false, false, "class='center'");
      Row::end();
    }
    Table::end(1);
    Form::submitCenter('Update', _('Update'), true, false, 'default');
  }

  /**
   * @param $Mode
   * @param $selected_id
   */
  function display_ext_edit($Mode, $selected_id)
  {
    $extensions = DB_Company::get_company_extensions();
    Table::start('tablestyle2');
    if ($selected_id != -1 && $extensions[$selected_id]['type'] == 'plugin') {
      if ($Mode == MODE_EDIT) {
        $mod               = $extensions[$selected_id];
        $_POST['tab']      = $mod['tab'];
        $_POST['name']     = $mod['name'];
        $_POST['title']    = $mod['title'];
        $_POST['path']     = $mod['path'];
        $_POST['filename'] = $mod['filename'];
        $_POST['acc_file'] = @$mod['acc_file'];
        Form::hidden('filename', $_POST['filename']);
        Form::hidden('acc_file', $_POST['acc_file']);
      }
      Form::hidden('selected_id', $selected_id);
    }
     Form::textRowEx(_("Name"), 'name', 30);
     Form::textRowEx(_("Folder"), 'path', 20);
    Users::tabs_row(_("Menu Tab"), 'tab', null, true);
     Form::textRowEx(_("Menu Link Text"), 'title', 30);
     Form::recordStatusListRow(_("Default status"), 'active');
     Form::fileRow(_("Module File"), 'uploadfile');
     Form::fileRow(_("Access Levels Extensions"), 'uploadfile3');
     Form::fileRow(_("SQL File"), 'uploadfile2');
    Table::end(0);
    Event::warning(_("Select your module PHP file from your local harddisk."), 0, 1);
    Form::submitAddUpdateCenter($selected_id == -1, '', 'both');
  }

