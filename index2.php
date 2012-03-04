<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Maidenii
	 * Date: 25/02/12
	 * Time: 1:52 PM
	 * To change this template use File | Settings | File Templates.
	 */
include('bootstrap.php');
	function getTransactions($debtorno, $incAllocations = true, $month) {
		$dateend = date('Y-m-d', mktime(0, 0, 0, $month, 0));
		$datestart = date('Y-m-d', mktime(0, 0, 0, $month - 2, 1));
		$sql = "
		select d.name AS name,d.debtor_no AS debtor_no,d.credit_limit AS credit_limit,cs.reason_description AS reason_description,
		sum((if(((dt.type = 11) or (dt.type = 1) or (dt.type = 12) or (dt.type = 2)),-(1),1) * ((((dt.ov_amount + dt.ov_gst) + dt.ov_freight) + dt.ov_freight_tax) + dt.ov_discount))) AS Balance,
		sum(if(((to_days('$dateend') - to_days(if((dt.type = 10),dt.due_date,dt.tran_date))) >= 0),(if(((dt.type = 11) or (dt.type = 1) or (dt.type = 12) or (dt.type = 2)),-(1),1) * ((((dt.ov_amount + dt.ov_gst) + dt.ov_freight) + dt.ov_freight_tax) + dt.ov_discount)),0)) AS Due,
		sum(if(((to_days('$dateend') - to_days(if((dt.type = 10),dt.due_date,dt.tran_date))) >= 30),(if(((dt.type = 11) or (dt.type = 1) or (dt.type = 12) or (dt.type = 2)),-(1),1) * ((((dt.ov_amount + dt.ov_gst) + dt.ov_freight) + dt.ov_freight_tax) + dt.ov_discount)),0)) AS Overdue1,
		sum(if(((to_days('$dateend') - to_days(if((dt.type = 10),dt.due_date,dt.tran_date))) >= 60),(if(((dt.type = 11) or (dt.type = 1) or (dt.type = 12) or (dt.type = 2)),-(1),1) * ((((dt.ov_amount + dt.ov_gst) + dt.ov_freight) + dt.ov_freight_tax) + dt.ov_discount)),0)) AS Overdue2
		from debtors ,payment_terms,credit_status,debtor_trans
		where ((d.pt = pt.terms_indicator) and (d.credit_status = cs.id) and (dt.tran_date <= '$dateend') and (dt.type <> 13) and (d.debtor_no = dt.debtor_no) and (d.debtor_no ='$debtorno'))
		group by d.name,pt.terms,pt.days_before_due,pt.day_in_following_month,d.credit_limit,cs.dissallow_invoices,cs.reason_description	";
		return DB::query($sql, "No transactions were returned");
	}
var_dump(getTransactions('4823'));
