<?php
  /**********************************************************************
  Copyright (C) Advanced Group PTY LTD
  Released under the terms of the GNU General Public License, GPL,
  as published by the Free Software Foundation, either version 3
  of the License, or (at your option) any later version.
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
  See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
   ***********************************************************************/
  class Apps_Purchases extends Application {

    function __construct() {
      global $installed_extensions;
      parent::__construct("Purchases", _($this->help_context = "&Purchases"));

      $this->add_module(_("Purchases"));
      $this->add_lapp_function(0, _("Purchase &Order Entry"),
        "purchases/po_entry_items.php?NewOrder=Yes", SA_PURCHASEORDER);
      $this->add_lapp_function(0, _("Purchase Orders &Inquiry"),
        "purchases/inquiry/po_search_completed.php?", SA_SUPPTRANSVIEW);

      $this->add_rapp_function(0, _("&Outstanding Purchase Orders Maintenance"),
        "purchases/inquiry/po_search.php?", SA_SUPPTRANSVIEW);
      $this->add_rapp_function(0, _("Supplier &Invoices"),
        "purchases/supplier_invoice.php?New=1", SA_SUPPLIERINVOICE);
      $this->add_rapp_function(0, _("Supplier &Credit Notes"),
        "purchases/supplier_credit.php?New=1", SA_SUPPLIERCREDIT);

      $this->add_module(_("Inquiries and Reports"));

      $this->add_lapp_function(1, _("&Payments to Suppliers"),
        "purchases/supplier_payment.php?", SA_SUPPLIERPAYMNT);
      $this->add_lapp_function(1, _("&Allocate Supplier Payments or Credit Notes"),
        "purchases/allocations/supplier_allocation_main.php?", SA_SUPPLIERALLOC);

      $this->add_lapp_function(1, _("Supplier Transaction &Inquiry"),
        "purchases/inquiry/supplier_inquiry.php?", SA_SUPPTRANSVIEW);

      $this->add_lapp_function(1, _("Supplier Allocation &Inquiry"),
        "purchases/inquiry/supplier_allocation_inquiry.php?", SA_SUPPLIERALLOC);

      $this->add_rapp_function(1, _("Supplier and Purchasing &Reports"),
        "reporting/reports_main.php?Class=1", SA_SUPPTRANSVIEW);

      $this->add_module(_("Maintenance"));
      $this->add_lapp_function(2, _("&Suppliers"),
        "purchases/manage/suppliers.php?", SA_SUPPLIER);
      if (count($installed_extensions) > 0) {
        foreach ($installed_extensions as $mod) {
          if (@$mod['active'] && $mod['type'] == 'plugin' && $mod["tab"] == "purchases") {
            $this->add_rapp_function(2, $mod["title"],
              "modules/" . $mod["path"] . "/" . $mod["filename"] . "?",
              isset($mod["access"]) ? $mod["access"] : SA_OPEN);
          }
        }
      }
    }
  }

?>
