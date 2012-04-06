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
  require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");

  JS::open_window(900, 500);
  JS::footerFile('/js/allocate.js');
  Page::start(_($help_context = "Allocate Customer Payment or Credit Note"), SA_SALESALLOC);

  if (isset($_POST['Process'])) {
    if (Gl_Allocation::check()) {
      $_SESSION['alloc']->write();
      Sales_Allocation::clear_allocations();
      $_POST['Cancel'] = 1;
    }
  }

  if (isset($_POST['Cancel'])) {
    Sales_Allocation::clear_allocations();
    $forward = (isset($_POST['inquiry'])) ? "/sales/inquiry/customer_allocation_inquiry.php" : "/sales/allocations/customer_allocation_main.php";
    Display::meta_forward($forward);
  }
  if (isset($_GET['trans_no']) && isset($_GET['trans_type'])) {
    Sales_Allocation::clear_allocations();
    $_SESSION['alloc'] = new Gl_Allocation($_GET['trans_type'], $_GET['trans_no']);
  }
  if (get_post('UpdateDisplay')) {
    $_SESSION['alloc']->read();
    Ajax::i()->activate('alloc_tbl');
  }
  if (isset($_SESSION['alloc'])) {
    Sales_Allocation::edit_allocations_for_transaction($_SESSION['alloc']->type, $_SESSION['alloc']->trans_no);
  }
  Page::end();


?>
