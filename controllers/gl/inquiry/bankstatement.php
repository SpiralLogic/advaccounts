<?php


  $file = DOCROOT . 'tmp/test.csv';
  Page::start(_($help_context = "Reconcile TO Bank Statement Compare"), SA_RECONCILE);
  $id                      = uniqid();
  $_SESSION['barcodefile'] = $id;
  if (!count($_POST)) {
    if (Session::getGlobal('bank_date')) {
      $_POST['bank_date']         = Session::getGlobal('bank_date');
      $_POST['_bank_date_update'] = $_POST['bank_date'];
    }
    if (Session::getGlobal('bank_account')) {
      $_POST['bank_account'] = Session::getGlobal('bank_account');
    }
  }
  if (Forms::isListUpdated('bank_account')) {
    Session::setGlobal('bank_account', $_POST['bank_account']);
    Ajax::activate('bank_date');
  }
  if (Forms::isListUpdated('bank_date')) {
    $_POST['reconcile_date'] = Input::post('bank_date') == '' ? Dates::today() : Dates::sqlToDate($_POST['bank_date']);
    Session::setGlobal('bank_date', $_POST['bank_date']);
  }
  Ajax::activate('trans');
  Display::div_start('trans');
  Forms::start(true);
  echo "<div class='center'><input
          type='hidden'  name='go' value=1 /><input
          type='hidden' name='unique' value='$id' /> ";
  //echo "<input type='file' name='csvitems' autofocus/><button>Go</button><br>";
  Bank_Account::cells(_("Account:"), 'bank_account', null, true);
  Bank_UI::reconcile_cells(_("Bank Statement:"), Input::post('bank_account'), 'bank_date', null, true, _("New"));
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
          DB::insert('temprec')->values(['date'=> $date, 'amount'=> $amount, 'memo'=> $memo, 'rb'=> $rb])->exec();
        }
      }
    }
  }
  $begin   = Dates::beginMonth(Input::post('bank_date'), null, Dates::now());
  $end     = Dates::endMonth(Input::post('bank_date'), null, Dates::now());
  $sql     = "SELECT * FROM (SELECT b.trans_no,b.trans_date,b.ref,b.amount,b.reconciled,t.date,t.amount as bank_amount,t.memo FROM (SELECT bt.trans_no, bt.ref, bt.trans_date, IF( bt.trans_no IS null,
        SUM( g.amount ), bt.amount ) AS amount, bt.reconciled
              FROM bank_trans bt
              LEFT OUTER JOIN bank_trans g ON g.undeposited = bt.id
              WHERE   bt.bank_act = " . DB::quote(Input::post('bank_account')) . " AND bt.trans_date <= '" . Dates::dateToSql(Input::post('bank_date')) . "' AND bt.undeposited=0
              AND (bt.reconciled IS null OR bt.reconciled='" . Dates::dateToSql(Input::post('bank_date')) . "') AND bt.amount!=0 GROUP BY bt.id ORDER BY trans_date DESC) b  LEFT OUTER  JOIN (SELECT * FROM temprec WHERE date >=
                            '" . Dates::dateToSql($begin) . "' AND date <=
                                          '" . Dates::dateToSql($end) . "' ) t ON t.amount = b.amount UNION SELECT b.trans_no,b.trans_date,b.ref,b.amount,b.reconciled,t.date,
              t.amount as bank_amount,
              t.memo FROM (SELECT bt.trans_no, bt.ref, bt.trans_date, IF( bt.trans_no IS null,
        SUM( g.amount ), bt.amount ) AS amount, bt.reconciled
              FROM bank_trans bt
              LEFT OUTER JOIN bank_trans g ON g.undeposited = bt.id
              WHERE   bt.bank_act = " . DB::quote(Input::post('bank_account')) . " AND bt.trans_date <= '" . Dates::dateToSql(Input::post('bank_date')) . "' AND bt.undeposited=0
              AND (bt.reconciled IS null OR bt.reconciled='" . Dates::dateToSql(Input::post('bank_date')) . "') AND bt.amount!=0 GROUP BY bt.id ) b  RIGHT OUTER  JOIN (SELECT * FROM temprec WHERE date >=
                                          '" . Dates::dateToSql($begin) . "' AND date <=
                                                        '" . Dates::dateToSql($end) . "' ) t ON t.amount = b.amount) f ORDER BY IF(trans_date is NULL,date,trans_date),IF(amount is NULL,bank_amount,amount)";
  $result  = DB::query($sql);
  $bankrec = DB::fetchAll();
  echo '<table class="grid">';
  echo "<thead><th>trans no</th><th>date</th><th>reference</th><th>amount</th><th>reconciled</th><th>bank date</th><th>bank amount</th><th>bank memo</th></thead>";
  foreach ($bankrec as $b) {
    if (($b['trans_date'] && $b['reconciled'] && !$b['date']) || ($b['date'] && !$b['reconciled'])) {
      echo  '<tr class="overduebg">';
    } elseif ($b['reconciled']) {
      echo  '<tr style="background-color:lightblue">';
    } else {
      echo  '<tr>';
    }
    $b['reconciled'] = ($b['reconciled']) ? 'YES' : 'NO';
    $b['date']       = Dates::sqlToDate($b['date']);
    $b['trans_date'] = Dates::sqlToDate($b['trans_date']);
    foreach ($b as $c) {
      echo "<td>$c</td>";
    }
    echo '</tr>';
  }
  echo '</table></div>';
  Display::div_end();
  Page::end();
