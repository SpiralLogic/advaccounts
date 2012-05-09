<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class Purch_UI {

    // SUPPLIERS
    /**
     * @static
     *
     * @param        $supplier
     * @param        $credit
     * @param string $parms
     */
    static public function credit_row($supplier, $credit, $parms = '') {
      Row::label(_("Current Credit:"), "<a target='_blank' " . ($credit < 0 ? 'class="redfg openWindow"' :
        '') . "href='/purchases/inquiry/supplier_inquiry.php?supplier_id=" . $supplier . "' >" . Num::price_format($credit) . "</a>", $parms);
    }
    /**
     * @static
     *
     * @param        $type
     * @param        $trans_no
     * @param string $label
     * @param bool   $icon
     * @param string $class
     * @param string $id
     *
     * @return null|string
     */
    static public function trans_view($type, $trans_no, $label = "", $icon = FALSE, $class = '', $id = '') {
      $viewer = "/purchases/view/";
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
          return NULL;
      }
      $viewer .= "?trans_no=$trans_no";
      if ($label == "") {
        $label = $trans_no;
      }
      return Display::viewer_link($label, $viewer, $class, $id, $icon);
    }
  }
