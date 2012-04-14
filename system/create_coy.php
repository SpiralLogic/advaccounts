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
  Page::start(_($help_context = "Create/Update Company"), SA_CREATECOMPANY);
  if (isset($_GET['selected_id'])) {
    $selected_id = $_GET['selected_id'];
  }
  elseif (isset($_POST['selected_id'])) {
    $selected_id = $_POST['selected_id'];
  }
  else {
    $selected_id = -1;
  }
  if (isset($_GET['c']) && $_GET['c'] == 'df') {
    handle_delete();
    $selected_id = -1;
  }
  if (isset($_GET['c']) && $_GET['c'] == 'u') {
    if (handle_submit($selected_id)) {
      $selected_id = -1;
    }
  }
  display_companies();
  Display::link_no_params($_SERVER['DOCUMENT_URI'], _("Create a new company"));
  display_company_edit($selected_id);
  Page::end();
  /**
   * @param $selected_id
   *
   * @return bool
   */
  function check_data(&$selected_id) {
    if ($_POST['name'] == "" || $_POST['host'] == "" || $_POST['dbuser'] == "" || $_POST['dbname'] == "") {
      return FALSE;
    }
    if ($selected_id == -1 && (!isset($_GET['ul']) || $_GET['ul'] != 1)) {
      Event::error(_("When creating a new company, you must provide a Database script file."));
      return FALSE;
    }
    foreach (Config::get_all('db') as $id => $con) {
      if ($id != $selected_id && $_POST['host'] == $con['host'] && $_POST['dbname'] == $con['dbname']
      ) {
      }
    }
    return TRUE;
  }

  /**
   * @param $selected_id
   *
   * @return bool
   */
  function handle_submit(&$selected_id) {
    $comp_subdirs = Config::get('company_subdirs');
    $error = FALSE;
    if (!check_data($selected_id)) {
      return FALSE;
    }
    $id = $_GET['id'];
    $connections = Config::get_all('db');
    $new = !isset($connections[$id]);
    $db_connection['name'] = $_POST['name'];
    $db_connection['host'] = $_POST['host'];
    $db_connection['dbuser'] = $_POST['dbuser'];
    $db_connection['dbpassword'] = $_POST['dbpassword'];
    $db_connection['dbname'] = $_POST['dbname'];
    Config::set($id, $db_connection, 'db');
    if ((bool) $_POST['def'] == TRUE) {
      Config::set('default.company', $id);
    }
    if (isset($_GET['ul']) && $_GET['ul'] == 1) {
      $conn = Config::get('db.' . $id);
      if (($db = DB_Utils::create($conn)) == 0) {
        Event::error(_("Error creating Database: ") . $conn['dbname'] . _(", Please create it manually"));
        $error = TRUE;
      }
      else {
        $filename = $_FILES['uploadfile']['tmp_name'];
        if (is_uploaded_file($filename)) {
          if (!DB_Utils::import($filename, $conn, $id)) {
            Event::error(_('Cannot create new company due to bugs in sql file.'));
            $error = TRUE;
          }
          else {
            if (isset($_POST['admpassword']) && $_POST['admpassword'] != "") {
              DB::query("UPDATE users set password = '" . md5($_POST['admpassword']) . "' WHERE user_id = 'admin'");
            }
          }
        }
        else {
          Event::error(_("Error uploading Database Script, please upload it manually"));
          $error = TRUE;
        }
      }
      if ($error) {
        remove_connection($id);
        return FALSE;
      }
    }
    else {
      if ($_GET['c'] = 'u') {
        $conn = Config::get('db.' . $id);
        if (($db = DB_Utils::create($conn)) == 0) {
          Event::error(_("Error connecting to Database: ") . $conn['dbname'] . _(", Please correct it"));
        }
        elseif ($_POST['admpassword'] != "") {
          DB::query("UPDATE users set password = '" . md5($_POST['admpassword']) . "' WHERE user_id = 'admin'");
        }
      }
    }
    if ($new) {
      create_comp_dirs(COMPANY_PATH . "$id", $comp_subdirs = Config::get('company_subdirs'));
    }
    $exts = DB_Company::get_company_extensions();
    advaccounting::write_extensions($exts, $id);
    Event::success($new ? _('New company has been created.') : _('Company has been updated.'));
    return TRUE;
  }

  function handle_delete() {
    $id = $_GET['id'];
    // First make sure all company directories from the one under removal are writable.
    // Without this after operation we end up with changed per-company owners!
    for ($i = $id; $i < count(Config::get_all('db')); $i++) {
      if (!is_dir(COMPANY_PATH . DS . $i) || !is_writable(COMPANY_PATH . DS . $i)) {
        Event::error(_('Broken company subdirectories system. You have to remove this company manually.'));
        return;
      }
    }
    // make sure config file is writable
    // rename directory to temporary name to ensure all
    // other subdirectories will have right owners even after
    // unsuccessfull removal.
    $cdir = COMPANY_PATH . DS . $id;
    $tmpname = COMPANY_PATH . 'old_' . $id;
    if (!@rename($cdir, $tmpname)) {
      Event::error(_('Cannot rename subdirectory to temporary name.'));
      return;
    }
    // 'shift' company directories names
    for ($i = $id + 1; $i < count(Config::get_all('db')); $i++) {
      if (!rename(COMPANY_PATH . DS . $i, COMPANY_PATH . DS . ($i - 1))) {
        Event::error(_("Cannot rename company subdirectory"));
        return;
      }
    }
    $err = remove_connection($id);
    if ($err == 0) {
      Event::error(_("Error removing Database: ") . _(", please remove it manually"));
    }
    if (Config::get('default.company') == $id) {
      Config::set('default.company', 1);
    }
    // finally remove renamed company directory
    @Files::flush_dir($tmpname, TRUE);
    if (!@rmdir($tmpname)) {
      Event::error(_("Cannot remove temporary renamed company data directory ") . $tmpname);
      return;
    }
    Event::notice(_("Selected company as been deleted"));
  }

  function display_companies() {
    $coyno = User::i()->company;
    echo "
			<script language='javascript'>
			function deleteCompany(id) {
				if (!confirm('" . _("Are you sure you want to delete company no. ") . "'+id))
					return
				document.location.replace('create_coy.php?c=df&id='+id)
			}
			</script>";
    start_table('tablestyle');
    $th = array(
      _("Company"), _("Database Host"), _("Database User"), _("Database Name"), _("Table Pref"), _("Default"), "", ""
    );
    table_header($th);
    $k = 0;
    $conn = Config::get_all('db');
    $n = count($conn);
    for ($i = 0; $i < $n; $i++) {
      if ($i == Config::get('default.company')) {
        $what = _("Yes");
      }
      else {
        $what = _("No");
      }
      if ($i == $coyno) {
        start_row("class='stockmankobg'");
      }
      else {
        alt_table_row_color($k);
      }
      label_cell($conn[$i]['name']);
      label_cell($conn[$i]['host']);
      label_cell($conn[$i]['dbuser']);
      label_cell($conn[$i]['dbname']);
      label_cell($what);
      $edit = _("Edit");
      $delete = _("Delete");
      if (User::graphic_links()) {
        $edit = set_icon(ICON_EDIT, $edit);
        $delete = set_icon(ICON_DELETE, $delete);
      }
      label_cell("<a href='" . $_SERVER['DOCUMENT_URI'] . "?selected_id=$i'>$edit</a>");
      label_cell($i == $coyno ? '' : "<a href=''>$delete</a>");
      end_row();
    }
    end_table();
    Event::warning(_("The marked company is the current company which cannot be deleted."), 0, 0, "class='currentfg'");
  }

  /**
   * @param $selected_id
   */
  function display_company_edit($selected_id) {
    if ($selected_id != -1) {
      $n = $selected_id;
    }
    else {
      $n = count(Config::get_all('db'));
    }
    start_form(TRUE);
    echo "
			<script language='javascript'>
			function updateCompany() {
				if (document.forms[0].uploadfile.value!='' && document.forms[0].dbname.value!='') {
					document.forms[0].action='create_coy.php?c=u&ul=1&id=" . $n . "&fn=' + document.forms[0].uploadfile.value
				}
				else {
					document.forms[0].action='create_coy.php?c=u&id=" . $n . "'
				}
				document.forms[0].submit()
			}
			</script>";
    start_table('tablestyle2');
    if ($selected_id != -1) {
      $conn = Config::get('db.' . $selected_id);
      $_POST['name'] = $conn['name'];
      $_POST['host'] = $conn['host'];
      $_POST['dbuser'] = $conn['dbuser'];
      $_POST['dbpassword'] = $conn['dbpassword'];
      $_POST['dbname'] = $conn['dbname'];
      if ($selected_id == Config::get('default.company')) {
        $_POST['def'] = TRUE;
      }
      else {
        $_POST['def'] = FALSE;
      }
      $_POST['dbcreate'] = FALSE;
      hidden('selected_id', $selected_id);
      hidden('dbpassword', $_POST['dbpassword']);
    }
    text_row_ex(_("Company"), 'name', 30);
    text_row_ex(_("Host"), 'host', 30);
    text_row_ex(_("Database User"), 'dbuser', 30);
    if ($selected_id == -1) {
      text_row_ex(_("Database Password"), 'dbpassword', 30);
    }
    text_row_ex(_("Database Name"), 'dbname', 30);
    yesno_list_row(_("Default"), 'def', NULL, "", "", FALSE);
    file_row(_("Database Script"), "uploadfile");
    text_row_ex(_("New script Admin Password"), 'admpassword', 20);
    end_table();
    Event::warning(_("Choose from Database scripts in SQL folder. No Database is created without a script."), 0, 1);
    echo "<div class='center'><input type='button' style='width:150px' value='" . _("Save") . "'></div>";
    end_form();
  }


