<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Maidenii
	 * Date: 5/12/11
	 * Time: 7:42 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class Purch_UI
	{
		// SUPPLIERS
		public static function credit_row($supplier, $credit, $parms = '') {
			label_row(_("Current Credit:"), "<a target='_blank' " . ($credit < 0 ? 'class="redfg openWindow"' :
			 '') . "href='/purchases/inquiry/supplier_inquiry.php?supplier_id=" . $supplier . "' >" . Num::price_format($credit) . "</a>", $parms);
		}

		public static function trans_view($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
			$viewer = "purchases/view/";
			switch ($type) {
				case ST_PURCHORDER:
					$viewer .= "view_po.php";
					break;
				case ST_SUPPINVOICE:
					$viewer .= "view_supp_invoice.php";
					break;
				case ST_SUPPCREDIT:
					$viewer .= "view_supp_credit.php";
					break;
				case ST_SUPPAYMENT:
					$viewer .= "view_supp_payment.php";
					break;
				case ST_SUPPRECEIVE:
					$viewer .= "view_grn.php";
					break;
				default:
					return null;
			}
			$viewer .= "?trans_no=$trans_no";
			if ($label == "") {
				$label = $trans_no;
			}
			return Display::viewer_link($label, $viewer, $class, $id, $icon);
		}
	}
