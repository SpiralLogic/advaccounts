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
  } elseif (isset($_POST['selected_id'])) {
    $selected_id = $_POST['selected_id'];
  } else {
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
  function check_data()
  {
    if ($_POST['code'] == "" || $_POST['name'] == "" || $_POST['encoding'] == "") {
      Event::error(_("Language name, code nor encoding cannot be empty"));

      return false;
    }

    return true;
  }

  /**
   * @return bool
   */
  function handle_submit()
  {
    $installed_languages = Config::get('languages.installed');
    if (!check_data()) {
      return false;
    }
    $id = $_GET['id'];
    if ($_POST['dflt']) {
      Config::set('default.language', $_POST['code']);
    }
    $installed_languages[$id]['code']     = $_POST['code'];
    $installed_languages[$id]['name']     = $_POST['name'];
    $installed_languages[$id]['encoding'] = $_POST['encoding'];
    $installed_languages[$id]['rtl']      = (bool) $_POST['rtl'];
    $language                             = Config::get('languages.installed');
    $language                             = $language[$id]['code'];
    $filename                             = LANG_PATH . '$language' . DS . 'LC_MESSAGES';
    if (!Files::save_to_file($filename, '')) {
      return false;
    }
    $directory = LANG_PATH . $_POST['code'];
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

    return true;
  }

  function handle_delete()
  {
    $id       = $_GET['id'];
    $language = Config::get('languages.installed');
    $language = $language[$id]['code'];
    $filename = LANG_PATH . $language . DS . 'LC_MESSAGES';
    if ($language == Config::get('default.language')) {
      // on delete set default to current.
      Config::set('default.language', $_SESSION['Language']->code);
    }
    Config::remove('languages.installed', $id);
    if (!Files::save_to_file($filename, '')) {
      return;
    }
    $filename = LANG_PATH . $language;
    Files::flush_dir($filename);
    rmdir($filename);
    Display::meta_forward($_SERVER['DOCUMENT_URI']);
  }

  function display_languages()
  {
    $language = $_SESSION["language"]->code;
    echo "
            <script language='javascript'>
            function deleteLanguage(id)
            {
                if (!confirm('" . _("Are you sure you want to delete language no. ") . "'+id))

                    return
                document.location.replace('inst_lang.php?c=df&id='+id)
            }
            </script>";
    Table::start('tablestyle grid');
    $th = array(_("Language"), _("Name"), _("Encoding"), _("Right To Left"), _("Default"), "", "");
    Table::header($th);
    $k    = 0;
    $conn = Config::get('languages.installed');
    $n    = count($conn);
    for ($i = 0; $i < $n; $i++) {
      if ($conn[$i]['code'] == $language) {
        Row::start("class='stockmankobg'");
      } else {
      }
      Cell::label($conn[$i]['code']);
      Cell::label($conn[$i]['name']);
      Cell::label($conn[$i]['encoding']);
      if (isset($conn[$i]['rtl']) && $conn[$i]['rtl']) {
        $rtl = _("Yes");
      } else {
        $rtl = _("No");
      }
      Cell::label($rtl);
      Cell::label(Config::get('default.language') == $conn[$i]['code'] ? _("Yes") : _("No"));
      $edit   = _("Edit");
      $delete = _("Delete");
      if (User::graphic_links()) {
        $edit   = Forms::setIcon(ICON_EDIT, $edit);
        $delete = Forms::setIcon(ICON_DELETE, $delete);
      }
      Cell::label("<a href='" . $_SERVER['DOCUMENT_URI'] . "?selected_id=$i'>$edit</a>");
      Cell::label($conn[$i]['code'] == $language ? '' : "<a href=''>$delete</a>");
      Row::end();
    }
    Table::end();
    Event::warning(_("The marked language is the current language which cannot be deleted."), 0, 0, "class='currentfg'");
  }

  /**
   * @param $selected_id
   */
  function display_language_edit($selected_id)
  {
    if ($selected_id != -1) {
      $n = $selected_id;
    } else {
      $n = count(Config::get('languages.installed'));
    }
    Forms::start(true);
    echo "
            <script language='javascript'>
            function updateLanguage()
            {
                document.forms[0].action='inst_lang.php?c=u&id=" . $n . "'
                document.forms[0].Forms::submit()
            }
            </script>";
    Table::start('tablestyle2');
    if ($selected_id != -1) {
      $languages         = Config::get('languages.installed');
      $conn              = $languages[$selected_id];
      $_POST['code']     = $conn['code'];
      $_POST['name']     = $conn['name'];
      $_POST['encoding'] = $conn['encoding'];
      if (isset($conn['rtl'])) {
        $_POST['rtl'] = $conn['rtl'];
      } else {
        $_POST['rtl'] = false;
      }
      $_POST['dflt'] = Config::set('default.language', $conn['code']);
      Forms::hidden('selected_id', $selected_id);
    }
    Forms::textRowEx(_("Language Code"), 'code', 20);
    Forms::textRowEx(_("Language Name"), 'name', 20);
    Forms::textRowEx(_("Encoding"), 'encoding', 20);
    Forms::yesnoListRow(_("Right To Left"), 'rtl', null, "", "", false);
    Forms::yesnoListRow(_("Default Language"), 'dflt', null, "", "", false);
    Forms::fileRow(_("Language File") . " (PO)", 'uploadfile');
    Forms::fileRow(_("Language File") . " (MO)", 'uploadfile2');
    Table::end(0);
    Event::warning(_("Select your language files from your local harddisk."), 0, 1);
    echo "<div class='center'><input type='button' style='width:150px' value='" . _("Save") . "'></div>";
    Forms::end();
  }
