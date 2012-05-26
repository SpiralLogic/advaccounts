<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  if (get_post('view')) {
    if (!get_post('backups')) {
      Event::error(_('Select backup file first.'));
    } else {
      $filename = BACKUP_PATH . get_post('backups');
      if (Ajax::in_ajax()) {
        Ajax::i()->popup($filename);
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
  if (get_post('download')) {
    download_file(BACKUP_PATH . get_post('backups'));
    exit;
  }
  Page::start(_($help_context = "Backup and Restore Database"), SA_BACKUP);
  check_paths();
  $db_name     = User::i()->company;
  $connections = Config::get_all('db');
  $conn        = $connections[$db_name];
  if (get_post('creat')) {
    generate_backup($conn, get_post('comp'), get_post('comments'));
    Ajax::i()->activate('backups');
  }
  ;
  if (get_post('restore')) {
    if (DB_Utils::import(BACKUP_PATH . get_post('backups'), $conn)) {
      Event::success(_("Restore backup completed."));
    }
  }
  if (get_post('deldump')) {
    if (unlink(BACKUP_PATH . get_post('backups'))) {
      Event::notice(_("File successfully deleted.") . " " . _("Filename") . ": " . get_post('backups'));
      Ajax::i()->activate('backups');
    } else {
      Event::error(_("Can't delete backup file."));
    }
  }
  ;
  if (get_post('upload')) {
    $tmpname = $_FILES['uploadfile']['tmp_name'];
    $fname   = $_FILES['uploadfile']['name'];
    if (!preg_match("/.sql(.zip|.gz)?$/", $fname)) {
      Event::error(_("You can only upload *.sql backup files"));
    } elseif (is_uploaded_file($tmpname)) {
      rename($tmpname, BACKUP_PATH . $fname);
      Event::notice("File uploaded to backup directory");
      Ajax::i()->activate('backups');
    } else {
      Event::error(_("File was not uploaded into the system."));
    }
  }
  start_form(true);
  Table::startOuter('tablestyle2');
  Table::section(1);
  Table::sectionTitle(_("Create backup"));
  textarea_row(_("Comments:"), 'comments', null, 30, 8);
  compress_list_row(_("Compression:"), 'comp');
  submit_row('creat', _("Create Backup"), false, "colspan=2 class='center'", '', 'process');
  Table::section(2);
  Table::sectionTitle(_("Backup scripts maintenance"));
  Row::start();
  echo "<td style='padding-left:20px'class='left'>" . get_backup_file_combo() . "</td>";
  echo "<td class='top'>";
  Table::start();
  submit_row('view', _("View Backup"), false, '', '', true);
  submit_row('download', _("Download Backup"), false, '', '', false);
  submit_row('restore', _("Restore Backup"), false, '', '', 'process');
  submit_js_confirm('restore', _("You are about to restore database from backup file.\nDo you want to continue?"));
  submit_row('deldump', _("Delete Backup"), false, '', '', true);
  // don't use 'delete' name or IE js errors appear
  submit_js_confirm('deldump', sprintf(_("You are about to remove selected backup file.\nDo you want to continue ?")));
  Table::end();
  echo "</td>";
  Row::end();
  Row::start();
  echo "<td style='padding-left:20px' class='left'><input name='uploadfile' type='file'></td>";
  submit_cells('upload', _("Upload file"), '', '', true);
  Row::end();
  Table::endOuter();
  end_form();
  Page::end();

