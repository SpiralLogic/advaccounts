<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  if (isset($_GET['vw'])) {
    $view_id = $_GET['vw'];
  } else {
    $view_id = Forms::findPostPrefix('view');
  }
  if ($view_id != -1) {
    $row = get_attachment($view_id);
    if ($row['filename'] != "") {
      if (Ajax::inAjax()) {
        Ajax::popup($_SERVER['DOCUMENT_URI'] . '?vw=' . $view_id);
      } else {
        $type = ($row['filetype']) ? $row['filetype'] : 'application/octet-stream';
        header("Content-type: " . $type);
        header('Content-Length: ' . $row['filesize']);
        if ($type == 'application/octet-stream') {
          header('Content-Disposition: attachment; filename=' . $row['filename']);
        } else {
          header("Content-Disposition: inline");
        }
        echo file_get_contents(COMPANY_PATH . "attachments/" . $row['unique_name']);
        exit();
      }
    }
  }
  if (isset($_GET['dl'])) {
    $download_id = $_GET['dl'];
  } else {
    $download_id = Forms::findPostPrefix('download');
  }
  if ($download_id != -1) {
    $row = get_attachment($download_id);
    if ($row['filename'] != "") {
      if (Ajax::inAjax()) {
        Ajax::redirect($_SERVER['DOCUMENT_URI'] . '?dl=' . $download_id);
      } else {
        $type = ($row['filetype']) ? $row['filetype'] : 'application/octet-stream';
        header("Content-type: " . $type);
        header('Content-Length: ' . $row['filesize']);
        header('Content-Disposition: attachment; filename=' . $row['filename']);
        echo file_get_contents(COMPANY_PATH . "attachments/" . $row['unique_name']);
        exit();
      }
    }
  }
  JS::openWindow(950, 500);
  Page::start(_($help_context = "Attach Documents"), SA_ATTACHDOCUMENT);
  list($Mode, $selected_id) = Page::simple_mode(true);
  if (isset($_GET['filterType'])) // catch up external links
  {
    $_POST['filterType'] = $_GET['filterType'];
  }
  if (isset($_GET['trans_no'])) {
    $_POST['trans_no'] = $_GET['trans_no'];
  }
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    if (isset($_FILES['filename']) && $_FILES['filename']['size'] > 0) {
      //$content = base64_encode(file_get_contents($_FILES['filename']['tmp_name']));
      $tmpname = $_FILES['filename']['tmp_name'];
      $dir     = COMPANY_PATH . "attachments";
      if (!file_exists($dir)) {
        mkdir($dir, 0777);
        $index_file = "<?php\nheader(\"Location: ../index.php\");\n?>";
        $fp         = fopen($dir . "/index.php", "w");
        fwrite($fp, $index_file);
        fclose($fp);
      }
      if ($Mode == UPDATE_ITEM && file_exists($dir . "/" . $_POST['unique_name'])) {
        unlink($dir . "/" . $_POST['unique_name']);
      }
      $unique_name = uniqid('');
      move_uploaded_file($tmpname, $dir . "/" . $unique_name);
      //save the file
      $filename = $_FILES['filename']['name'];
      $filesize = $_FILES['filename']['size'];
      $filetype = $_FILES['filename']['type'];
    } else {
      $unique_name = $filename = $filetype = "";
      $filesize    = 0;
    }
    $date = Dates::today(true);
    if ($Mode == ADD_ITEM) {
      $sql
        = "INSERT INTO attachments (type_no, trans_no, description, filename, unique_name,
			filesize, filetype, tran_date) VALUES (" . DB::escape($_POST['filterType']) . "," . DB::escape($_POST['trans_no']) . "," . DB::escape($_POST['description']) . ", " . DB::escape($filename) . ", " . DB::escape($unique_name) . ", " . DB::escape($filesize) . ", " . DB::escape($filetype) . ", '$date')";
      DB::query($sql, "Attachment could not be inserted");
      Event::success(_("Attachment has been inserted."));
    } else {
      $sql
        = "UPDATE attachments SET
			type_no=" . DB::escape($_POST['filterType']) . ",
			trans_no=" . DB::escape($_POST['trans_no']) . ",
			description=" . DB::escape($_POST['description']) . ", ";
      if ($filename != "") {
        $sql .= "filename=" . DB::escape($filename) . ",
			unique_name=" . DB::escape($unique_name) . ",
			filesize=" . DB::escape($filesize) . ",
			filetype=" . DB::escape($filetype);
      }
      $sql .= "tran_date='$date' WHERE id=" . DB::escape($selected_id);
      DB::query($sql, "Attachment could not be updated");
      Event::success(_("Attachment has been updated."));
    }
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_DELETE) {
    $row = get_attachment($selected_id);
    $dir = COMPANY_PATH . "attachments";
    if (file_exists($dir . "/" . $row['unique_name'])) {
      unlink($dir . "/" . $row['unique_name']);
    }
    $sql = "DELETE FROM attachments WHERE id = " . DB::escape($selected_id);
    DB::query($sql, "Could not delete attachment");
    Event::notice(_("Attachment has been deleted."));
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    unset($_POST['trans_no'], $_POST['description']);
    $selected_id = -1;
  }

  viewing_controls();
  if (isset($_POST['filterType'])) {
    display_rows($_POST['filterType']);
  }
  Forms::start(true);
  Table::start('tablestyle2');
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      $row                  = get_attachment($selected_id);
      $_POST['trans_no']    = $row["trans_no"];
      $_POST['description'] = $row["description"];
      Forms::hidden('trans_no', $row['trans_no']);
      Forms::hidden('unique_name', $row['unique_name']);
      Row::label(_("Transaction #"), $row['trans_no']);
    }
    Forms::hidden('selected_id', $selected_id);
  } else {
    Forms::textRowEx(_("Transaction #") . ':', 'trans_no', 10);
  }
  Forms::textRowEx(_("Description") . ':', 'description', 40);
  Forms::fileRow(_("Attached File") . ":", 'filename', 'filename');
  Table::end(1);
  if (isset($_POST['filterType'])) {
    Forms::hidden('filterType', $_POST['filterType']);
  }
  Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Forms::end();
  Page::end();


