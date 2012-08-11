<?php
  $result = DB::select()->from('creditor_trans')->where('creditor_id=', 2866)->andWhere('tran_date=', Dates::dateToSql('11/08/2012'))->andWhere('type=', ST_SUPPAYMENT)->fetch()->one();
  var_dump($result);exit;
