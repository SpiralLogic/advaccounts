<?php
/*  if (!User::i()->can_access(SA_DEBUGGING)) {
    throw new Adv_Exception("Administrator access only");
  }*/
  /*  echo '<pre>';
  $sql
    = "SELECT reconciled,bank_act FROM bank_trans
               WHERE bank_act=13 AND reconciled IS NOT NULL AND amount!=0
               GROUP BY reconciled";
  $results = DB::query($sql)->fetchAll();
  foreach ($results as $row) {
    $recon = GL_Account::get_max_reconciled(Dates::sql2date($row['reconciled']), $row['bank_act'])->fetch();
    $values = array(
      'bank_act' => $row['bank_act'],
      'begin_balance' => $recon['beg_balance'],
      'end_balance' => $recon['end_balance'],
      'date' => $row['reconciled']
    );
    try {
      DB::insert('bank_reconciles')->values($values)->exec();
    }
    catch (ADV\Core\DB\DBDuplicateException $e) {
      continue;
    }
  }*/
var_dump(PDO::CASE_UPPER ===1);
var_dump(PDO::CASE_UPPER ==1);
