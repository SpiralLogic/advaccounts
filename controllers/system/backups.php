<?php
  use ADV\Core\DB\Utils;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  if (Input::post('view')) {
    if (!Input::post('backups')) {
      Event::error(_('Select backup file first.'));
    } else {
      $filename = BACKUP_PATH . Input::post('backups');
      if (Ajax::inAjax()) {
        Ajax::popup($filename);
      } else {
        header('Content-type: application/octet-stream');
        header('Content-Length: ' . filesize($filename));
        header("Content-Disposition: inline; filename=$filename");
        readfile($filename);
        exit();
      }
    }
  }
  ;
  if (Input::post('download')) {
    download_file(BACKUP_PATH . Input::post('backups'));
    exit;
  }
  Page::start(_($help_context = "Backup and Restore Database"), SA_BACKUP);
  check_paths();
  $db_name     = User::i()->company;
  $connections = Config::getAll('db');
  $conn        = $connections[$db_name];
  if (Input::post('creat')) {
    generate_backup($conn, Input::post('comp'), Input::post('comments'));
    Ajax::activate('backups');
  }
  ;
  if (Input::post('restore')) {
    if (Utils::import(BACKUP_PATH . Input::post('backups'), $conn)) {
      Event::success(_("Restore backup completed."));
    }
  }
  if (Input::post('deldump')) {
    if (unlink(BACKUP_PATH . Input::post('backups'))) {
      Event::notice(_("File successfully deleted.") . " " . _("Filename") . ": " . Input::post('backups'));
      Ajax::activate('backups');
    } else {
      Event::error(_("Can't delete backup file."));
    }
  }
  ;
  if (Input::post('upload')) {
    $tmpname = $_FILES['uploadfile']['tmp_name'];
    $fname   = $_FILES['uploadfile']['name'];
    if (!preg_match("/.sql(.zip|.gz)?$/", $fname)) {
      Event::error(_("You can only upload *.sql backup files"));
    } elseif (is_uploaded_file($tmpname)) {
      rename($tmpname, BACKUP_PATH . $fname);
      Event::notice("File uploaded to backup directory");
      Ajax::activate('backups');
    } else {
      Event::error(_("File was not uploaded into the system."));
    }
  }
  Forms::start(true);
  Table::startOuter('tablestyle2');
  Table::section(1);
  Table::sectionTitle(_("Create backup"));
  Forms::textareaRow(_("Comments:"), 'comments', null, 30, 8);
  compress_list_row(_("Compression:"), 'comp');
  Forms::submitRow('creat', _("Create Backup"), false, "colspan=2 class='center'", '', 'process');
  Table::section(2);
  Table::sectionTitle(_("Backup scripts maintenance"));
  Row::start();
  echo "<td style='padding-left:20px'class='left'>" . get_backup_file_combo() . "</td>";
  echo "<td class='top'>";
  Table::start();
  Forms::submitRow('view', _("View Backup"), false, '', '', true);
  Forms::submitRow('download', _("Download Backup"), false, '', '', false);
  Forms::submitRow('restore', _("Restore Backup"), false, '', '', 'process');
  Forms::submitConfirm('restore', _("You are about to restore database from backup file.\nDo you want to continue?"));
  Forms::submitRow('deldump', _("Delete Backup"), false, '', '', true);
  // don't use 'delete' name or IE js errors appear
  Forms::submitConfirm('deldump', sprintf(_("You are about to remove selected backup file.\nDo you want to continue ?")));
  Table::end();
  echo "</td>";
  Row::end();
  Row::start();
  echo "<td style='padding-left:20px' class='left'><input name='uploadfile' type='file'></td>";
  Forms::submitCells('upload', _("Upload file"), '', '', true);
  Row::end();
  Table::endOuter();
  Forms::end();
  Page::end();

