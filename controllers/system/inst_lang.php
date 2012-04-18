<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/


  Page::start(_($help_context = "Install/Update Languages"), SA_CREATELANGUAGE);
  if (isset($_GET['selected_id'])) {
    $selected_id = $_GET['selected_id'];
  }
  elseif (isset($_POST['selected_id'])) {
    $selected_id = $_POST['selected_id'];
  }
  else {
    $selected_id = -1;
  }

  if (isset($_GET['c'])) {
    if ($_GET['c'] == 'df') {
      handle_delete();
    }
    if ($_GET['c'] == 'u') {
      if (handle_submit()) {
        //Display::meta_forward($_SERVER['DOCUMENT_URI']);
      }
    }
  }
  display_languages();
  Display::link_no_params($_SERVER['DOCUMENT_URI'], _("Create a new language"));
  display_language_edit($selected_id);
  Page::end();
  /**
   * @return bool
   */
  function check_data() {
    if ($_POST['code'] == "" || $_POST['name'] == "" || $_POST['encoding'] == "") {
      Event::error(_("Language name, code nor encoding cannot be empty"));
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @return bool
   */
  function handle_submit() {
    $installed_languages = Config::get('languages.installed');
    if (!check_data()) {
      return FALSE;
    }
    $id = $_GET['id'];
    if ($_POST['dflt']) {
      Config::set('default.lang', $_POST['code']);
    }
    $installed_languages[$id]['code'] = $_POST['code'];
    $installed_languages[$id]['name'] = $_POST['name'];
    $installed_languages[$id]['encoding'] = $_POST['encoding'];
    $installed_languages[$id]['rtl'] = (bool) $_POST['rtl'];
    $lang = Config::get('languages.installed');
    $lang = $lang[$id]['code'];
    $filename = LANG_PATH . '$lang'.DS.'LC_MESSAGES';
    if (!Files::save_to_file($filename, '')) {
      return FALSE;
    }
    $directory =LANG_PATH . $_POST['code'];
    if (!file_exists($directory)) {
      mkdir($directory);
      mkdir($directory . "/LC_MESSAGES");
    }
    if (is_uploaded_file($_FILES['uploadfile']['tmp_name'])) {
      $file1 = $_FILES['uploadfile']['tmp_name'];
      $file2 = $directory . "/LC_MESSAGES/" . $_POST['code'] . ".po";
      if (file_exists($file2)) {
        unlink($file2);
      }
      move_uploaded_file($file1, $file2);
    }
    if (is_uploaded_file($_FILES['uploadfile2']['tmp_name'])) {
      $file1 = $_FILES['uploadfile2']['tmp_name'];
      $file2 = $directory . "/LC_MESSAGES/" . $_POST['code'] . ".mo";
      if (file_exists($file2)) {
        unlink($file2);
      }
      move_uploaded_file($file1, $file2);
    }
    Config::set('languages.installed', $installed_languages);
    return TRUE;
  }

  function handle_delete() {
    $id = $_GET['id'];
    $lang = Config::get('languages.installed');
    $lang = $lang[$id]['code'];
    $filename = LANG_PATH . $lang.DS.'LC_MESSAGES';
    if ($lang == Config::get('default.lang')) {
      // on delete set default to current.
      Config::set('default.lang', $_SESSION['Language']->code);
    }
    Config::remove('languages.installed', $id);
    if (!Files::save_to_file($filename, '')) {
      return;
    }
    $filename = LANG_PATH . $lang;
    Files::flush_dir($filename);
    rmdir($filename);
    Display::meta_forward($_SERVER['DOCUMENT_URI']);
  }

  function display_languages() {
    $lang = $_SESSION["language"]->code;
    echo "
			<script language='javascript'>
			function deleteLanguage(id) {
				if (!confirm('" . _("Are you sure you want to delete language no. ") . "'+id))
					return
				document.location.replace('inst_lang.php?c=df&id='+id)
			}
			</script>";
    start_table('tablestyle');
    $th = array(_("Language"), _("Name"), _("Encoding"), _("Right To Left"), _("Default"), "", "");
    table_header($th);
    $k = 0;
    $conn = Config::get('languages.installed');
    $n = count($conn);
    for ($i = 0; $i < $n; $i++) {
      if ($conn[$i]['code'] == $lang) {
        start_row("class='stockmankobg'");
      }
      else {
        alt_table_row_color($k);
      }
      label_cell($conn[$i]['code']);
      label_cell($conn[$i]['name']);
      label_cell($conn[$i]['encoding']);
      if (isset($conn[$i]['rtl']) && $conn[$i]['rtl']) {
        $rtl = _("Yes");
      }
      else {
        $rtl = _("No");
      }
      label_cell($rtl);
      label_cell(Config::get('default.lang') == $conn[$i]['code'] ? _("Yes") : _("No"));
      $edit = _("Edit");
      $delete = _("Delete");
      if (User::graphic_links()) {
        $edit = set_icon(ICON_EDIT, $edit);
        $delete = set_icon(ICON_DELETE, $delete);
      }
      label_cell("<a href='" . $_SERVER['DOCUMENT_URI'] . "?selected_id=$i'>$edit</a>");
      label_cell($conn[$i]['code'] == $lang ? '' : "<a href=''>$delete</a>");
      end_row();
    }
    end_table();
    Event::warning(_("The marked language is the current language which cannot be deleted."), 0, 0, "class='currentfg'");
  }

  /**
   * @param $selected_id
   */
  function display_language_edit($selected_id) {
    if ($selected_id != -1) {
      $n = $selected_id;
    }
    else {
      $n = count(Config::get('languages.installed'));
    }
    start_form(TRUE);
    echo "
			<script language='javascript'>
			function updateLanguage() {
				document.forms[0].action='inst_lang.php?c=u&id=" . $n . "'
				document.forms[0].submit()
			}
			</script>";
    start_table('tablestyle2');
    if ($selected_id != -1) {
      $languages = Config::get('languages.installed');
      $conn = $languages[$selected_id];
      $_POST['code'] = $conn['code'];
      $_POST['name'] = $conn['name'];
      $_POST['encoding'] = $conn['encoding'];
      if (isset($conn['rtl'])) {
        $_POST['rtl'] = $conn['rtl'];
      }
      else {
        $_POST['rtl'] = FALSE;
      }
      $_POST['dflt'] = Config::set('default.lang', $conn['code']);
      hidden('selected_id', $selected_id);
    }
    text_row_ex(_("Language Code"), 'code', 20);
    text_row_ex(_("Language Name"), 'name', 20);
    text_row_ex(_("Encoding"), 'encoding', 20);
    yesno_list_row(_("Right To Left"), 'rtl', NULL, "", "", FALSE);
    yesno_list_row(_("Default Language"), 'dflt', NULL, "", "", FALSE);
    file_row(_("Language File") . " (PO)", 'uploadfile');
    file_row(_("Language File") . " (MO)", 'uploadfile2');
    end_table(0);
    Event::warning(_("Select your language files from your local harddisk."), 0, 1);
    echo "<div class='center'><input type='button' style='width:150px' value='" . _("Save") . "'></div>";
    end_form();
  }
