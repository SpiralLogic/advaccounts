<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 22/10/11
	 * Time: 6:45 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class Voiding {
		public static function void($type, $type_no, $date_, $memo_) {
			$void_entry = static::get($type, $type_no);

			if ($void_entry != null)
				return false;

			switch ($type) {
				case ST_JOURNAL : // it's a journal entry
					if (!exists_gl_trans($type, $type_no))
						return false;
					void_journal_trans($type, $type_no);
					break;

				case ST_BANKPAYMENT : // it's a payment
				case ST_BANKDEPOSIT : // it's a deposit
				case ST_BANKTRANSFER : // it's a transfer
					if (!exists_bank_trans($type, $type_no))
						return false;
					void_bank_trans($type, $type_no);
					break;

				case ST_SALESINVOICE : // it's a customer invoice
				case ST_CUSTCREDIT : // it's a customer credit note
				case ST_CUSTPAYMENT : // it's a customer payment
				case ST_CUSTREFUND : // it's a customer refund

				case ST_CUSTDELIVERY : // it's a customer dispatch
					if (!exists_customer_trans($type, $type_no))
						return false;
					if ($type == 13) // added 04 Oct 2008 by Joe Hunt. If delivery note has a not voided invoice, then NO.
					{
						$delivery = get_customer_trans($type_no, $type);
						if ($delivery['trans_link'] != 0) {
							if (static::get_entry(10, $delivery['trans_link']) === false)
								return false;
						}
					}
					post_void_customer_trans($type, $type_no);
					break;

				case ST_LOCTRANSFER : // it's a stock transfer
					if (get_stock_transfer_items($type_no) == null)
						return false;
					void_stock_transfer($type_no);
					break;

				case ST_INVADJUST : // it's a stock adjustment
					if (get_stock_adjustment_items($type_no) == null)
						return false;
					void_stock_adjustment($type_no);
					break;

				case ST_PURCHORDER : // it's a PO
				case ST_SUPPRECEIVE : // it's a GRN
					return false;
				case ST_SUPPINVOICE : // it's a suppler invoice
				case ST_SUPPCREDIT : // it's a supplier credit note
				case ST_SUPPAYMENT : // it's a supplier payment
					if (!exists_supp_trans($type, $type_no))
						return false;
					if (!post_void_supp_trans($type, $type_no))
						return false;
					break;

				case ST_WORKORDER : // it's a work order
					if (!get_work_order($type_no, true))
						return false;
					void_work_order($type_no);
					break;

				case ST_MANUISSUE : // it's a work order issue
					if (!exists_work_order_issue($type_no))
						return false;
					void_work_order_issue($type_no);
					break;

				case ST_MANURECEIVE : // it's a work order production
					if (!exists_work_order_produce($type_no))
						return false;
					void_work_order_produce($type_no);
					break;

				case ST_SALESORDER: // it's a sales order
				case ST_SALESQUOTE: // it's a sales quotation
					return false;

				case ST_COSTUPDATE : // it's a stock cost update
					return false;
					break;
			}

			// only add an entry if it's actually been voided
			DB_AuditTrail::add($type, $type_no, $date_, _("Voided.") . "\n" . $memo_);
			static::add($type, $type_no, $date_, $memo_);
			return true;
		}

		//--------------------------------------------------------------------------------------------------

		public static function get($type, $type_no) {
			$sql = "SELECT * FROM voided WHERE type=" . DBOld::escape($type)
			 . " AND id=" . DBOld::escape($type_no);

			$result = DBOld::query($sql, "could not query voided transaction table");
			return DBOld::fetch($result);
		}
public static function has($type,$type_no) {
	$sql = "SELECT * FROM voided WHERE type=" . DBOld::escape($type)
		 . " AND id=" . DBOld::escape($type_no);

		$result = DBOld::query($sql, "could not query voided transaction table");

	return DBOld::num_rows($result);

}
		//--------------------------------------------------------------------------------------------------

		public static function add($type, $type_no, $date_, $memo_) {
			$date = Dates::date2sql($date_);
			$sql = "INSERT INTO voided (type, id, date_, memo_)
			VALUES (" . DBOld::escape($type) . ", " . DBOld::escape($type_no) . ", "
			 . DBOld::escape($date) . ", " . DBOld::escape($memo_) . ")";

			DBOld::query($sql, "could not add voided transaction entry");
		}
		//--------------------------------------------------------------------------------------------------

	}
