<?php
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
    }
    else {
      $filename = BACKUP_PATH . Input::post('backups');
      if (Ajax::in_ajax()) {
        Ajax::i()->popup($filename);
      }
      else {
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
  $db_name = User::i()->company;
  $connections = Config::get_all('db');
  $conn = $connections[$db_name];
  if (Input::post('creat')) {
    generate_backup($conn, Input::post('comp'), Input::post('comments'));
    Ajax::i()->activate('backups');
  }
  ;
  if (Input::post('restore')) {
    if (DB_Utils::import(BACKUP_PATH . Input::post('backups'), $conn)) {
      Event::success(_("Restore backup completed."));
    }
  }
  if (Input::post('deldump')) {
    if (unlink(BACKUP_PATH . Input::post('backups'))) {
      Event::notice(_("File successfully deleted.") . " " . _("Filename") . ": " . Input::post('backups'));
      Ajax::i()->activate('backups');
    }
    else {
      Event::error(_("Can't delete backup file."));
    }
  }
  ;
  if (Input::post('upload')) {
    $tmpname = $_FILES['uploadfile']['tmp_name'];
    $fname = $_FILES['uploadfile']['name'];
    if (!preg_match("/.sql(.zip|.gz)?$/", $fname)) {
      Event::error(_("You can only upload *.sql backup files"));
    }
    elseif (is_uploaded_file($tmpname)) {
      rename($tmpname, BACKUP_PATH . $fname);
      Event::notice("File uploaded to backup directory");
      Ajax::i()->activate('backups');
    }
    else {
      Event::error(_("File was not uploaded into the system."));
    }
  }
  Form::start(TRUE);
  Table::startOuter('tablestyle2');
  Table::section(1);
  Table::sectionTitle(_("Create backup"));
   Form::textareaRow(_("Comments:"), 'comments', NULL, 30, 8);
  compress_list_row(_("Compression:"), 'comp');
  Form::submitRow('creat', _("Create Backup"), FALSE, "colspan=2 class='center'", '', 'process');
  Table::section(2);
  Table::sectionTitle(_("Backup scripts maintenance"));
  Row::start();
  echo "<td style='padding-left:20px'class='left'>" . get_backup_file_combo() . "</td>";
  echo "<td class='top'>";
  Table::start();
  Form::submitRow('view', _("View Backup"), FALSE, '', '', TRUE);
  Form::submitRow('download', _("Download Backup"), FALSE, '', '', FALSE);
  Form::submitRow('restore', _("Restore Backup"), FALSE, '', '', 'process');
  Form::submitConfirm('restore', _("You are about to restore database from backup file.\nDo you want to continue?"));
  Form::submitRow('deldump', _("Delete Backup"), FALSE, '', '', TRUE);
  // don't use 'delete' name or IE js errors appear
  Form::submitConfirm('deldump', sprintf(_("You are about to remove selected backup file.\nDo you want to continue ?")));
  Table::end();
  echo "</td>";
  Row::end();
  Row::start();
  echo "<td style='padding-left:20px' class='left'><input name='uploadfile' type='file'></td>";
  Form::submitCells('upload', _("Upload file"), '', '', TRUE);
  Row::end();
  Table::endOuter();
  Form::end();
  Page::end();

