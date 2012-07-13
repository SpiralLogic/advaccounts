<?php
  $_POST['bank_date']    = '31/08/2011';
  $_POST['bank_account'] = '5';
  $begin                 = Dates::beginMonth(Input::post('bank_date'), null, Dates::now());
  $end                   = Dates::endMonth(Input::post('bank_date'), null, Dates::now());
  $sql                   = "SELECT bt.type, bt.trans_no, bt.ref, bt.id, bt.trans_date, IF( bt.trans_no IS null, SUM( g.amount ), bt.amount ) AS amount
             , bt.person_id, bt.person_type_id , bt.reconciled FROM bank_trans bt LEFT OUTER JOIN bank_trans g ON g.undeposited = bt.id
             WHERE bt.bank_act = " . DB::quote(Input::post('bank_account')) . "
             AND bt.trans_date <= '" . Dates::dateToSql(Input::post('bank_date')) . "'
             AND bt.undeposited=0
             AND (bt.reconciled IS null OR bt.reconciled='" . Dates::dateToSql(Input::post('bank_date')) . "')
             AND bt.amount!=0 GROUP BY bt.id ORDER BY trans_date, amount DESC";
  $result                = DB::query($sql);
  $rec                   = DB::fetchAll();
  $sql                   = "SELECT date as state_date, amount as state_amount,memo FROM temprec WHERE date >='" . Dates::dateToSql($begin) . "' AND date <='" . Dates::dateToSql($end) . "' ORDER BY date,amount";
  $result                = DB::query($sql);
  $state                 = DB::fetchAll();
  $recced                = $unrecced = [];
  $emptyrec              = array_combine(array_keys($rec[0]), array_values(array_pad([], count($rec[0]), '')));
  $emptystate            = array_combine(array_keys($state[0]), array_values(array_pad([], count($state[0]), '')));
  while ($v = array_shift($state)) {
    $amount = $v['state_amfount'];
    foreach ($rec as $p=> $q) {
      if ($q['amount'] == $amount) {
        $matched = $rec[$p] + $v;
        unset($rec[$p]);
        $recced[] = $matched;
        continue 2;
      }
    }
    $newv = $emptyrec;
    Arr::append($newv, $v);
    $recced[] = $newv;
  }
  foreach ($rec as &$r) {
    Arr::append($r, $emptystate);
  }
  Arr::append($recced, $rec);
  function sortByOrder($a, $b) {
    if (isset($a['trans_date']) && isset($b['trans_date'])) {
      return Dates::isGreaterThan($a['trans_date'], $b['trans_date']);
    } elseif (!isset($a['trans_date']) && isset($b['trans_date'])) {
      return Dates::isGreaterThan($a['state_date'], $b['trans_date']);
    } elseif (isset($a['trans_date']) && !isset($b['trans_date'])) {
      return Dates::isGreaterThan($a['trans_date'], $b['state_date']);
    }
    return Dates::isGreaterThan($a['state_date'], $b['state_date']);
  }

  global $systypes_array;
  usort($recced, 'sortByOrder');
  Page::start('tee');
  echo '<table class="grid">';
  echo "<thead><th>type</th><th>trans no</th><th>reference</th><th>date</th><th>debit</th><th>credit</th><th>info</th><th>gl</th><th>reconciled</th><th>bank date</th><th>bank amount</th><th>bank memo</th></thead>";
  foreach ($recced as $b) {
    if (($b['trans_date'] && $b['reconciled'] && !$b['state_date']) || ($b['state_date'] && !$b['reconciled'])) {
      echo  '<tr class="overduebg">';
    } elseif ($b['reconciled']) {
      echo  '<tr style="background-color:lightblue">';
    } else {
      echo  '<tr>';
    }
    $b['state_date'] = Dates::sqlToDate($b['state_date']);
    $b['trans_date'] = Dates::sqlToDate($b['trans_date']);
    foreach ($b as $d => $c) {
      switch ($d) {
        case 'type':
          echo '<td>' . $systypes_array[$c] . '</td>';
          break;
        case 'trans_no':
          echo '<td>' . GL_UI::viewTrans($b["type"], $c) . '</td>';
          break;
        case 'id':
          break;
        case 'person_type_id':
          $gl=($b['type'] != 15) ? GL_UI::view($b["type"], $b["trans_no"]) : '';
        echo  '<td>' . $gl. '</td>';
        break;
        case 'person_id':
          echo  '<td>' . formatPerson($b) . '</td>';
          break;
        case 'reconciled':
          $name   = "rec_" . $b['id'];
          $hidden = 'last[' . $b['id'] . ']';
          $value  = $b['reconciled'] != '';
          echo  '<td>' . Forms::checkbox(null, $name, $value, true, _('Reconcile this transaction')) . Forms::hidden($hidden, $value, false) . '</td>';
          break;

        case 'ref':
          echo '<td>' . substr($b['ref'], 0, 6) . '</td>';
          break;
        case 'amount':
          echo ($c > 0) ? '<td class="bold">' . Num::priceFormat($c) . '</td><td></td>' : '<td></td><td class="bold">' . Num::priceFormat(-1 * $c) . '</td>';
          break;
        default:
          echo "<td>$c</td>";
      }
    }
    echo '</tr>';
  }
  echo '</table></div>';
  function formatPerson($row) {
    if ($row['type'] == ST_BANKTRANSFER) {
      return DB_Comments::get_string(ST_BANKTRANSFER, $row['trans_no']);
    } elseif ($row['type'] == ST_GROUPDEPOSIT) {
      $sql     = "SELECT bank_trans.ref,bank_trans.person_type_id,bank_trans.trans_no,bank_trans.person_id,bank_trans.amount,
      			comments.memo_ FROM bank_trans LEFT JOIN comments ON (bank_trans.type=comments.type AND bank_trans.trans_no=comments.id)
      			WHERE bank_trans.bank_act='" . 5 . "' AND bank_trans.type != " . ST_GROUPDEPOSIT . " AND bank_trans.undeposited>0 AND (undeposited = " . $row['id'] . ")";
      $result  = DB::query($sql, 'Couldn\'t get deposit references');
      $content = '';
      foreach ($result as $trans) {
        $name = Bank::payment_person_name($trans["person_type_id"], $trans["person_id"], true, $trans["trans_no"]);
        $content .= $trans['ref'] . ' <span class="u">' . $name . ' ($' . Num::priceFormat($trans['amount']) . ')</span>: ' . $trans['memo_'] . '<br>';
      }
      return $content;
    }
    return Bank::payment_person_name($row["person_type_id"], $row["person_id"], true, $row["trans_no"]);
  }
Page::end();
