<?php


  $file = DOCROOT . 'tmp/test.csv';
  Page::start(_($help_context = "Reconcile TO Bank Statement Compare"), SA_RECONCILE);
  if (!count($_POST)) {

    if (Session::getGlobal('bank_account')) {
      $_POST['bank_account'] = Session::getGlobal('bank_account');
    }
  }
  if (Forms::isListUpdated('bank_account')) {
    Session::setGlobal('bank_account', $_POST['bank_account']);
  }

  Ajax::activate('trans');
  Display::div_start('trans');
  Forms::start(true);
  echo "<div class='center'><input
          type='hidden'  name='go' value=1 /><input type='file' name='csvitems' autofocus/><button>Go</button><br>";
  Bank_Account::cells(_("Account:"), 'bank_account', null, true);
  Forms::end();
  if (sizeof($_FILES) != 0) {
    if (file_exists($file)) {
      unlink($file);
    }
    if (move_uploaded_file($_FILES['csvitems']['tmp_name'], $file)) {
      ini_set('auto_detect_line_endings', 1);
      $csvitems = [];
      $file     = fopen($file, 'r');
      while (($item = fgetcsv($file, 1000, ',')) !== false) {
        if (isset($item[5]) && strlen($item[5] > $item[3])) {
          $memo = $item[4] . ' - ' . $item[5];
          $rb   = (is_numeric($item[6])) ? $item[6] : 0;
        } else {
          $memo = $item[2] . ' - ' . $item[3];
          $rb   = (is_numeric($item[4])) ? $item[4] : 0;
        }
        $amount = $item[1];
        $date   = Dates::dateToSql($item[0]);
        $result = DB::select('COUNT(*) as count')->from('temprec')->where('date=', $date)->andWhere('amount=', $amount)->andWhere('rb=', $rb)->fetch()->one();
        if ($result['count'] == 0) {
          DB::insert('temprec')->values(['date'=> $date, 'amount'=> $amount, 'memo'=> $memo, 'rb'=> $rb, 'bank_account_id'=> $_POST['bank_account']])->exec();
        }
      }
    }
  }

  Display::div_end();
  Page::end();
