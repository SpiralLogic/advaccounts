SELECT * FROM (SELECT b.trans_no,b.trans_date,b.ref,b.amount,b.reconciled,t.date,t.amount as bank_amount,t.memo FROM (SELECT bt.trans_no, bt.ref, bt.trans_date, IF( bt.trans_no IS null,
        SUM( g.amount ), bt.amount ) AS amount, bt.reconciled
              FROM bank_trans bt
              LEFT OUTER JOIN bank_trans g ON g.undeposited = bt.id
              WHERE   bt.bank_act = 5 AND bt.undeposited=0
               AND bt.amount!=0 GROUP BY bt.id ) b  LEFT OUTER  JOIN (SELECT * FROM temprec) t ON t.amount = b.amount UNION SELECT b.trans_no,b.trans_date,b.ref,b.amount,b.reconciled,t.date,
              t.amount as bank_amount,
              t.memo FROM (SELECT bt.trans_no, bt.ref, bt.trans_date, IF( bt.trans_no IS null,
        SUM( g.amount ), bt.amount ) AS amount, bt.reconciled
              FROM bank_trans bt
              LEFT OUTER JOIN bank_trans g ON g.undeposited = bt.id
              WHERE   bt.bank_act = 5 AND  bt.undeposited=0 AND bt.amount!=0 GROUP BY bt.id ) b  RIGHT OUTER  JOIN (SELECT * FROM temprec) t ON t.amount = b.amount) f ORDER BY IF(trans_date is NULL,date,trans_date),IF(amount is NULL,bank_amount,amount)
