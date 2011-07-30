<!DOCTYPE HTML>
<html dir='ltr'>
<head><title>Customers</title>
   <meta http-equiv='Content-type' content='text/html; charset=iso-8859-1'>
   <link rel='apple-touch-icon' href='/company/images/advanced-icon.png'/>
   <link href='/themes/default/default.css,jquery-ui-1.8.7.css,jquery.calculator.css,jquery.fileupload-ui.css' rel='stylesheet' type='text/css'>
   <script src='/js/JsHttpRequest.js,behaviour.js,utils.js,inserts.js'></script>
   <script src='/js/js2/jquery-1.6.2.min.js,jquery-ui-1.8.14.min.js'></script>
</head>
<body>
<div id='content'>
<div id="sidemenu" class="ui-widget-shadow ui-corner-all">
   <h3><a href="#">Search</a></h3>

   <div id="search">

      <a id="orders" href="/sales/inquiry/sales_orders_view.php?type=30">Orders/Quotes</a>
      <a id="invoices" href="/sales/inquiry/customer_inquiry.php?">Invoice/Delivery</a>
      <a id="purchaseOrders" href="/purchasing/inquiry/po_search_completed.php?">Purchase Order</a>
      <a id="supplierInvoices" href="/purchasing/inquiry/supplier_inquiry.php?">Supplier Invoices</a></div>

   <h3><a href="#">Create</a></h3>

   <div>
      <a href="/sales/sales_order_entry.php?NewQuotation=Yes">Quote</a>
      <a href="/sales/sales_order_entry.php?NewOrder=Yes">Order</a>
      <a href="/sales/sales_order_entry.php?NewInvoice=0">Direct Invoice</a>
      <a href="/purchasing/po_entry_items.php?NewOrder=Yes">Purchase Order</a>
   </div>
   <h3><a href="#">Customer Search</a></h3>

   <div>
      <input size="14" id="quickCustomer"/>
   </div>
</div>
<div id='top'>
   <p>Advanced Group PTY LTD | dev.advaccounts | Sol Jennings</p>
   <ul>
      <li><a href='../admin/display_prefs.php?'>Preferences</a></li>
      <li><a href='../admin/change_current_user_password.php?selected_id=admin'>Change password</a></li>
      <li><a href='../access/logout.php?'>Logout</a></li>
   </ul>
</div>
<div id='logo'>
   <h1>Advanced Accounting 2.2.11<span style='padding-left:280px;'><img id='ajaxmark' src='../themes/default/images/ajax-loader.gif' align='center' style='visibility:hidden;'></span></h1>
</div>
<div id="_tabs2">
   <div class="menu_container">
      <ul class='menu'>
         <li><a href='../index.php?application=orders' accesskey='S'><u>S</u>ales</a></li>
         <li class='active'><a href='../contacts/customers.php' accesskey='C'><u>C</u>ustomers</a></li>
         <li><a href='../index.php?application=AP' accesskey='P'><u>P</u>urchases</a></li>
         <li><a href='../index.php?application=stock' accesskey='I'><u>I</u>tems and Inventory</a></li>
         <li><a href='../items/items.php'>Items</a></li>
         <li><a href='../index.php?application=Advanced' accesskey='A'><u>A</u>dvanced</a></li>
         <li><a href='../index.php?application=manuf' accesskey='M'><u>M</u>anufacturing</a></li>
         <li><a href='../index.php?application=GL' accesskey='B'><u>B</u>anking</a></li>
         <li><a href='../index.php?application=system' accesskey='E'>S<u>e</u>tup</a></li>
      </ul>
   </div>
</div>
<div id='wrapper'>
<center>
   <table id='title'>
      <tr>
         <td width='100%' class='titletext'>Customers</td>
         <td align=right><span id='hints'></span></td>
      </tr>
   </table>
</center>
<div id='msgbox'></div>
<div id='_page_body'>
<div id='tabs0'>
<ul>
   <li><a title='Details' href='#tabs0-1'><span>Details</span></a></li>
   <li><a title='Accounts' href='#tabs0-2'><span>Accounts</span></a></li>
   <li><a title='Customer Contacts' href='#tabs0-3'><span>Customer Contacts</span></a></li>
   <li><a title='Extra Shipping Info' href='#tabs0-4'><span>Extra Shipping Info</span></a></li>
   <li><a title='Invoices' href='#tabs0-5'><span>Invoices</span></a></li>
</ul>
<div id='custsearch'>
   <table style='margin:0 auto; padding-bottom:5px; font-weight:bold'>
      <tr>
         <td style='width:750px'><label for='customer'>Search Customer:</label><input id='customer' size='80'></td>
      </tr>
   </table>
</div>
<form method='post' action='/contacts/customers.php'>
<div id="tabs0-1" style="text-align:center">
   <div id='customerIDs' style='display:inline-block'>
      <table style='margin:0 auto; padding-bottom:5px; font-weight:bold'>
         <tr>
            <td><label for='name'>Customer name:</label><input id='name' name='name' size='50'>
            <td style='width:90px'>Customer ID:</td>
            <td><input id='id' name='id' size='10' maxlength='7'/></td>
      </table>
   </div>
   <center>
      <table class='tablestyle2' cellpadding=5 cellspacing=0>
         <tr valign=top>
            <td>
               <table class='tablestyle_inner '>
                  <tr class='tableheader'>
                     <td colspan=2 class='tableheader'>Shipping Details</td>
                  </tr>
                  <tr>
                     <td id='branchSelect' colspan='2' style='text-align:center; margin:0 auto; '><select id='branchList' name='branchList'>
                        <option value='New Address'>0</option>
                     </select>
                        <button id='addBranch' name='addBranch' class='ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only'>Add new address</button>
                     </td>
                  </tr>
                  <tr>
                     <td class='label'>Contact:</td>
                     <td><input type="text" name="br_contact_name" size="35" maxlength="40" value=""></td>
                  </tr>
                  <tr>
                     <td class='label'>Phone Number:</td>
                     <td><input type="text" name="br_phone" size="32" maxlength="30" value=""></td>
                  </tr>
                  <tr>
                     <td class='label'>2nd Phone Number:</td>
                     <td><input type="text" name="br_phone2" size="32" maxlength="30" value=""></td>
                  </tr>
                  <tr>
                     <td class='label'>Fax Number:</td>
                     <td><input type="text" name="br_fax" size="32" maxlength="30" value=""></td>
                  </tr>
                  <tr>
                     <td class='label'>Email:</td>
                     <td><input type="text" name="br_email" size="35" maxlength="55" value=""></td>
                  </tr>
                  <tr>
                     <td class='label'>Street:</td>
                     <td><textarea name='br_br_address' cols='35' rows='2'></textarea></td>
                  </tr>
                  <tr>
                     <td class='label'><label for='br_city'>City: </label></td>
                     <td><input id='br_city' size='35' name='br_city'/></td>
                  </tr>
                  <tr>
                     <td class='label'><label for='br_state'>State: </label></td>
                     <td><input id='br_state' maxlength='35' size='35' name='br_state'>
                  <tr>
                     <td class='label'><label for='br_postcode'>Postcode: </label></td>
                     <td><input id='br_postcode' size='35' name='br_postcode'/></td>
                  </tr>
               </table>
            </td>
            <td style='border-left:1px solid #cccccc;'>
               <table class='tablestyle_inner '>
                  <tr class='tableheader'>
                     <td colspan=2 class='tableheader'>Accounts Details</td>
                  </tr>
                  <tr>
                     <td style='text-align:center; margin:0 auto' colspan='2'><input type="hidden" name="id" value="0">
                        <button id='useShipAddress' name='useShipAddress' class='ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only'>Use shipping details</button>
                  <tr>
                     <td class='label'>Accounts Contact:</td>
                     <td><input type="text" name="acc_contact_name" size="40" maxlength="40" value=""></td>
                  </tr>
                  </td></tr>
                  <tr>
                     <td class='label'>Phone Number:</td>
                     <td><input type="text" name="acc_phone" size="40" maxlength="30" value=""></td>
                  </tr>
                  <tr>
                     <td class='label'>Secondary Phone Number:</td>
                     <td><input type="text" name="acc_phone2" size="40" maxlength="30" value=""></td>
                  </tr>
                  <tr>
                     <td class='label'>Fax Number:</td>
                     <td><input type="text" name="acc_fax" size="40" maxlength="30" value=""></td>
                  </tr>
                  <tr>
                     <td class='label'>E-mail:</td>
                     <td><input type="text" name="acc_email" size="35" maxlength="40" value=""></td>
                  </tr>
                  <tr>
                     <td class='label'>Street:</td>
                     <td><textarea name='acc_br_address' cols='35' rows='2'></textarea></td>
                  </tr>
                  <tr>
                     <td class='label'><label for='acc_city'>City: </label></td>
                     <td><input id='acc_city' size='35' name='acc_city'/></td>
                  </tr>
                  <tr>
                     <td class='label'><label for='acc_state'>State: </label></td>
                     <td><input id='acc_state' maxlength='35' size='35' name='acc_state'>
                  <tr>
                     <td class='label'><label for='acc_postcode'>Postcode: </label></td>
                     <td><input id='acc_postcode' size='35' name='acc_postcode'/></td>
                  </tr>
                  <tr>
                     <td class='label'>Sales Type/Price List:</td>
                     <td><span id='_sales_type_sel'><select id='sales_type' autocomplete='off' name='sales_type' class='combo' title=''>
                        <option selected value='1'>Retail</option>
                        <option value='2'>Wholesale</option>
                     </select>
</span>
                     </td>
                  </tr>
                  <tr>
                     <td class='label'>Customer status:</td>
                     <td><span id='_inactive_sel'><select name='inactive' class='combo' title=''>
                        <option value='0'>Active</option>
                        <option value='1'>Inactive</option>
                     </select>
</span>
                     </td>
                  </tr>
               </table>
            </td>
         </tr>
         /table>
   </center>
   <br></div>
<div id="tabs0-2">
   <center>
      <table class='tablestyle2' cellpadding=5 cellspacing=0>
         <tr valign=top>
            <td>
               <table class='tablestyle_inner '>
                  <input type="hidden" name="accounts_id" value="0">
                  <tr class='tableheader'>
                     <td colspan=2 class='tableheader'>Accounts Details:</td>
                  </tr>
                  <tr>
                     <td class='label'>Accounts Contact:</td>
                     <td><input type="text" name="acc_contact_name" size="40" maxlength="40" value=""></td>
                  </tr>
                  <tr>
                     <td class='label'>E-mail:</td>
                     <td><input type="text" name="acc_email" size="40" maxlength="40" value=""></td>
                  </tr>
                  <tr>
                     <td class='label'>Phone Number:</td>
                     <td><input type="text" name="acc_phone" size="40" maxlength="30" value=""></td>
                  </tr>
                  <tr>
                     <td class='label'>2nd Phone Number:</td>
                     <td><input type="text" name="acc_phone2" size="40" maxlength="30" value=""></td>
                  </tr>
                  <tr>
                     <td class='label'>Fax Number:</td>
                     <td><input type="text" name="acc_fax" size="40" maxlength="30" value=""></td>
                  </tr>
                  <tr>
                     <td class='label'>Street:</td>
                     <td><textarea name='acc_br_address' cols='35' rows='2'></textarea></td>
                  </tr>
                  <tr>
                     <td class='label'>City</td>
                     <td><input type="text" name="acc_city" size="35" maxlength="40" value=""></td>
                  </tr>
                  <tr>
                     <td class='label'>postcode</td>
                     <td><input type="text" name="acc_postcode" size="35" maxlength="40" value=""></td>
                  </tr>
                  <tr>
                     <td class='label'>State:</td>
                     <td><input type="text" name="acc_state" size="35" maxlength="40" value=""></td>
                  </tr>
                  <tr>
                     <td class='label'>Postal Address:</td>
                     <td><textarea name='acc_br_post_address' cols='35' rows='2'></textarea></td>
                  </tr>
                  <tr>
                     <td class='label'>Discount Percent:</td>
                     <td><input class='amount' type="text" name="discount" " size="7" maxlength="12" dec="1" value="0"><span id='_discount" _label'> %</span></td>
                  </tr>
                  <tr>
                     <td class='label'>Prompt Payment Discount Percent:</td>
                     <td><input class='amount' type="text" name="pymt_discount" " size="7" maxlength="12" dec="1" value="0"><span id='_pymt_discount" _label'> %</span></td>
                  </tr>
                  <tr>
                     <td class='label'>Credit Limit:</td>
                     <td><input class='amount' type="text" name="credit_limit" size="15" maxlength="15" dec="2" value="0"></td>
                  </tr>
                  <tr>
                     <td class='label'>GSTNo:</td>
                     <td><input type="text" name="tax_id" size="35" maxlength="40" value=""></td>
                  </tr>
                  <tr>
                     <td class='label'>Customer's Currency:</td>
                     <td><span id='_curr_code_sel'><select id='curr_code' autocomplete='off' name='curr_code' class='combo' title=''>
                        <option selected value='AUD'>AU Dollars</option>
                        <option value='GBP'>British Pounds</option>
                        <option value='USD'>US Dollar</option>
                     </select>
</span>
                     </td>
                  </tr>
                  <input type="hidden" name="dimension_id" value="0">
                  <input type="hidden" name="dimension2_id" value="0">
               </table>
            </td>
            <td style='border-left:1px solid #cccccc;'>
               <table class='tablestyle_inner '>
                  <tr class='tableheader'>
                     <td colspan=2 class='tableheader'>Contact log:</td>
                  </tr>
                  <tr>
                     <td class='ui-widget-content center-content' colspan='2'>
                        <button id='addLog' name='addLog' class='ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only'>Add log entry</button>
                     </td>
                  </tr>
                  <tr>
                     <td colspan='2'><textarea id='messageLog' cols='50' rows='25'></textarea></td>
                     </tr>
                  <tr>
                     <td class='label'>Pament Terms:</td>
                     <td><span id='_payment_terms_sel'><select id='payment_terms' autocomplete='off' name='payment_terms' class='combo' title=''>
                        <option value='0'>Payment Up Front</option>
                        <option value='1'>Due 15th Of the Following Month</option>
                        <option value='2'>Due By End Of The Following Month</option>
                        <option value='5'>50% deposit, 50% 14 days job end</option>
                        <option value='6'>14 days</option>
                        <option value='7'>Old</option>
                        <option value='8'>30 Days</option>
                        <option value='9'>7 Days</option>
                     </select>
</span>
                     </td>
                  </tr>
                  <tr>
                     <td class='label'>Credit Status:</td>
                     <td><span id='_credit_status_sel'><select id='credit_status' autocomplete='off' name='credit_status' class='combo' title=''>
                        <option selected value='1'>Good History</option>
                        <option value='4'>In liquidation</option>
                        <option value='3'>No more work until payment received</option>
                        <option value='5'>Permanently Black-listed - Bad Customer!</option>
                     </select>
</span>
                     </td>
                  </tr>
               </table>
            </td>
         </tr>
      </table>
   </center>
   <br></div>
<div id="tabs0-3">
   <div style='text-align:center'>
      <div id='Contacts' style='min-height:200px;'>

      </div>
    </div>
         </div>

      <div id="tabs0-4">
<center>
<table class='tablestyle2' cellpadding=5 cellspacing=0>
<tr valign=top>
<td>
   <table class='tablestyle_inner '>
      <input type="hidden" name="branch_code" value="0">
      <tr class='tableheader'>
         <td colspan=2 class='tableheader'>Name and Contact</td>
      </tr>
      <tr>
         <td class='label'>Address Name:</td>
         <td><input type="text" name="br_br_name" size="35" maxlength="40" value="New Address"></td>
      </tr>
      <tr>
         <td class='label'>Contact:</td>
         <td><input type="text" name="br_contact_name" size="35" maxlength="40" value=""></td>
      </tr>
      <tr>
         <td class='label'>General Notes:</td>
         <td><textarea name='br_notes' cols='35' rows='4'></textarea></td>
      </tr>
      <tr class='tableheader'>
         <td colspan=2 class='tableheader'>Sales</td>
      </tr>
      <tr>
         <td class='label'>Sales Person:</td>
         <td>
<span id='_br_salesman_sel'><select id='br_salesman' autocomplete='off' name='br_salesman' class='combo' title=''>
   <option value='10'>Greg Miller</option>
   <option value='11'>Julian</option>
   <option value='3'>Mike Smith</option>
   <option value='9'>Peter Carcour</option>
   <option value='2'>Sam Jennings</option>
   <option selected value='5'>Sol Jennings</option>
   <option value='8'>Syed Mehdi</option>
</select>
</span>
         </td>
      </tr>
      <tr>
         <td class='label'>Sales Area:</td>
         <td><span id='_br_area_sel'><select id='br_area' autocomplete='off' name='br_area' class='combo' title=''>
            <option selected value='1'>Australia</option>
            <option value='2'>UK</option>
         </select>
</span>
         </td>
      </tr>
      <tr>
         <td class='label'>Sales Group:</td>
         <td><span id='_br_group_no_sel'><select id='br_group_no' autocomplete='off' name='br_group_no' class='combo' title=''>
            <option value='3'>Large</option>
            <option value='2'>Medium</option>
            <option selected value='1'>Small</option>
         </select>
</span>
         </td>
      </tr>
      <tr>
         <td class='label'>Default Inventory Location:</td>
         <td><span id='_br_default_location_sel'><select id='br_default_location' autocomplete='off' name='br_default_location' class='combo' title=''>
            <option selected value='MEL'>Camberwell</option>
            <option value='DRP'>Drop Ship</option>
            <option value='NFY'>NOT FAXED YET</option>
            <option value='TEST'>test</option>
         </select>
</span>
         </td>
      </tr>
      <tr>
         <td class='label'>Default Shipping Company:</td>
         <td><span id='_br_default_ship_via_sel'><select id='br_default_ship_via' autocomplete='off' name='br_default_ship_via' class='combo' title=''>
            <option value='11'>Delivery</option>
            <option selected class='inactive' value='1'>Hunter Express</option>
            <option value='9'>Installation</option>
            <option value='8'>Linemarking</option>
            <option value='2'>Pickup</option>
         </select>
</span>
         </td>
      </tr>
      <tr>
         <td class='label'>Tax Group:</td>
         <td><span id='_br_tax_group_id_sel'><select id='br_tax_group_id' autocomplete='off' name='br_tax_group_id' class='combo' title=''>
            <option selected value='1'>GST</option>
            <option value='2'>Tax free FRE</option>
            <option value='4'>Tax free EXP</option>
            <option value='5'>Taxed CAP</option>
            <option value='6'>Tax free N-T</option>
            <option value='7'>Taxed INP</option>
         </select>
</span>
         </td>
      </tr>
      <tr>
         <td class='label'>Disable this Branch:</td>
         <td><span id='_br_disable_trans_sel'><select name='br_disable_trans' class='combo' title=''>
            <option value='0'>No</option>
            <option value='1'>Yes</option>
         </select>
</span>
         </td>
      </tr>
   </table>
</td>
<td style='border-left:1px solid #cccccc;'>
<table class='tablestyle_inner '>
<tr class='tableheader'>
   <td colspan=2 class='tableheader'>GL Accounts</td>
</tr>
<tr>
   <td class='label'>Sales Account:</td>
   <td><span id='_br_sales_account_sel'><select id='br_sales_account' autocomplete='off' name='br_sales_account' class='combo2' title=''>
      <option selected value=''>Use Item Sales Accounts</option>
      <optgroup label='Cash'>
         <option value='1700'>1700&nbsp;&nbsp;&nbsp;&nbsp;Adv NAB Cheque Account</option>
         <option value='1701'>1701&nbsp;&nbsp;&nbsp;&nbsp;MSH NAB Cheque Account</option>
         <option value='1702'>1702&nbsp;&nbsp;&nbsp;&nbsp;Westpac Cheque Account</option>
         <option value='1703'>1703&nbsp;&nbsp;&nbsp;&nbsp;Cash on Hand</option>
         <option value='1705'>1705&nbsp;&nbsp;&nbsp;&nbsp;Petty Cash</option>
         <option value='1706'>1706&nbsp;&nbsp;&nbsp;&nbsp;Mike&#039;s Wallet</option>
         <option value='1707'>1707&nbsp;&nbsp;&nbsp;&nbsp;Paypal Account</option>
         <option value='1708'>1708&nbsp;&nbsp;&nbsp;&nbsp;Undeposited Funds</option>
         <optgroup label='Receivables'>
            <option value='1510'>1510&nbsp;&nbsp;&nbsp;&nbsp;Employee Advances</option>
            <option value='1800'>1800&nbsp;&nbsp;&nbsp;&nbsp;Accounts receivable</option>
            <optgroup label='Prepayments'>
               <option value='1900'>1900&nbsp;&nbsp;&nbsp;&nbsp;Deposits Paid</option>
               <option value='1910'>1910&nbsp;&nbsp;&nbsp;&nbsp;Prepaid Insurance</option>
               <option value='1920'>1920&nbsp;&nbsp;&nbsp;&nbsp;Prepaid Workcover</option>
               <optgroup label='Inventory'>
                  <option value='1200'>1200&nbsp;&nbsp;&nbsp;&nbsp;Inventory</option>
                  <option value='1205'>1205&nbsp;&nbsp;&nbsp;&nbsp;Inventory adjustment</option>
                  <optgroup label='Fixed Assets'>
                     <option value='1010'>1010&nbsp;&nbsp;&nbsp;&nbsp;Office Equipment</option>
                     <option value='1011'>1011&nbsp;&nbsp;&nbsp;&nbsp;Office Equipment Accumulated Depreciation</option>
                     <option value='1020'>1020&nbsp;&nbsp;&nbsp;&nbsp;Computer Equipment</option>
                     <option value='1021'>1021&nbsp;&nbsp;&nbsp;&nbsp;Computer Equipment Accumulated Depreciation</option>
                     <option value='1030'>1030&nbsp;&nbsp;&nbsp;&nbsp;Motor Vehicles</option>
                     <option value='1031'>1031&nbsp;&nbsp;&nbsp;&nbsp;Motor Vehicles Accumulated Depreciation</option>
                     <option value='1040'>1040&nbsp;&nbsp;&nbsp;&nbsp;Property, plant, equip.</option>
                     <option value='1041'>1041&nbsp;&nbsp;&nbsp;&nbsp;Plant &amp; Equip Accumulated Depreciation</option>
                     <option value='1050'>1050&nbsp;&nbsp;&nbsp;&nbsp;Goodwill</option>
                     <optgroup label='Credit Cards'>
                        <option value='2100'>2100&nbsp;&nbsp;&nbsp;&nbsp;NAB Mastercard</option>
                        <option value='2200'>2200&nbsp;&nbsp;&nbsp;&nbsp;NAB Visa</option>
                        <optgroup label='Accounts payable'>
                           <option value='20000'>20000&nbsp;&nbsp;&nbsp;&nbsp;Accounts Payable</option>
                           <option value='2300'>2300&nbsp;&nbsp;&nbsp;&nbsp;Sundry Creditors</option>
                           <optgroup label='Tax liabilities'>
                              <option value='2410'>2410&nbsp;&nbsp;&nbsp;&nbsp;PAYG Withheld</option>
                              <option value='2421'>2421&nbsp;&nbsp;&nbsp;&nbsp;GST Collected</option>
                              <option value='2422'>2422&nbsp;&nbsp;&nbsp;&nbsp;Other GST Collected</option>
                              <option value='2430'>2430&nbsp;&nbsp;&nbsp;&nbsp;GST Paid</option>
                              <option value='2431'>2431&nbsp;&nbsp;&nbsp;&nbsp;Other GST Paid</option>
                              <option value='2440'>2440&nbsp;&nbsp;&nbsp;&nbsp;BAS Clearing</option>
                              <optgroup label='Other current liabilities'>
                                 <option value='2510'>2510&nbsp;&nbsp;&nbsp;&nbsp;Accrued Superannuation</option>
                                 <option value='2520'>2520&nbsp;&nbsp;&nbsp;&nbsp;Provision for Annual Leave</option>
                                 <option value='2530'>2530&nbsp;&nbsp;&nbsp;&nbsp;Customer Deposits</option>
                                 <option value='2540'>2540&nbsp;&nbsp;&nbsp;&nbsp;Wages Clearing Account</option>
                                 <optgroup label='Loans'>
                                    <option value='2610'>2610&nbsp;&nbsp;&nbsp;&nbsp;Director&#039;s Loan Account</option>
                                    <option value='2621'>2621&nbsp;&nbsp;&nbsp;&nbsp;Katie&#039;s HSBC Visa</option>
                                    <option value='2622'>2622&nbsp;&nbsp;&nbsp;&nbsp;Katie&#039;s Line of Credit</option>
                                    <option value='2623'>2623&nbsp;&nbsp;&nbsp;&nbsp;Katie&#039;s ANZ Mastercard</option>
                                    <option value='2624'>2624&nbsp;&nbsp;&nbsp;&nbsp;Katie&#039;s CBA Mastercard</option>
                                    <option value='2625'>2625&nbsp;&nbsp;&nbsp;&nbsp;Katie&#039;s Personal Loan</option>
                                    <option value='2700'>2700&nbsp;&nbsp;&nbsp;&nbsp;Van Loan</option>
                                    <option value='2800'>2800&nbsp;&nbsp;&nbsp;&nbsp;Establishment Loan</option>
                                    <option value='2900'>2900&nbsp;&nbsp;&nbsp;&nbsp;Advanced Super Investments Pty Ltd</option>
                                    <optgroup label='Equity'>
                                       <option value='3100'>3100&nbsp;&nbsp;&nbsp;&nbsp;Owners Capital</option>
                                       <option value='3200'>3200&nbsp;&nbsp;&nbsp;&nbsp;Director&#039;s Drawings</option>
                                       <option value='3300'>3300&nbsp;&nbsp;&nbsp;&nbsp;Current Year Profit/Loss</option>
                                       <option value='3400'>3400&nbsp;&nbsp;&nbsp;&nbsp;Retained Earnings</option>
                                       <option value='3500'>3500&nbsp;&nbsp;&nbsp;&nbsp;Historical Balancing</option>
                                       <optgroup label='Sales Income'>
                                          <option value='4010'>4010&nbsp;&nbsp;&nbsp;&nbsp;Retail Sales</option>
                                          <option value='4050'>4050&nbsp;&nbsp;&nbsp;&nbsp;Web Sales</option>
                                          <option value='4110'>4110&nbsp;&nbsp;&nbsp;&nbsp;Export Sales</option>
                                          <option value='4111'>4111&nbsp;&nbsp;&nbsp;&nbsp;Custom Sign Sales</option>
                                          <option value='4112'>4112&nbsp;&nbsp;&nbsp;&nbsp;Installation Income</option>
                                          <option value='4150'>4150&nbsp;&nbsp;&nbsp;&nbsp;Freight Collected</option>
                                          <option value='4151'>4151&nbsp;&nbsp;&nbsp;&nbsp;Equipment Rental</option>
                                          <option value='4152'>4152&nbsp;&nbsp;&nbsp;&nbsp;Line Marking</option>
                                          <option value='4160'>4160&nbsp;&nbsp;&nbsp;&nbsp;Restocking Fees</option>
                                          <option value='4170'>4170&nbsp;&nbsp;&nbsp;&nbsp;Sales - Motor Sprot Heaven</option>
                                          <option value='4180'>4180&nbsp;&nbsp;&nbsp;&nbsp;Miscellaneous Income</option>
                                          <option value='4190'>4190&nbsp;&nbsp;&nbsp;&nbsp;AMEX Fees Collected</option>
                                          <optgroup label='Other income'>
                                             <option value='7010'>7010&nbsp;&nbsp;&nbsp;&nbsp;Interest Income</option>
                                             <option value='7020'>7020&nbsp;&nbsp;&nbsp;&nbsp;Rental Income</option>
                                             <option value='7030'>7030&nbsp;&nbsp;&nbsp;&nbsp;Other Income</option>
                                             <option value='7040'>7040&nbsp;&nbsp;&nbsp;&nbsp;Government Payments</option>
                                             <optgroup label='Cost of Goods Sold'>
                                                <option value='5040'>5040&nbsp;&nbsp;&nbsp;&nbsp;Materials &amp; Supplies</option>
                                                <option value='5050'>5050&nbsp;&nbsp;&nbsp;&nbsp;Cost of Goods Sold</option>
                                                <option value='5060'>5060&nbsp;&nbsp;&nbsp;&nbsp;Linemarking Subcontractors</option>
                                                <option value='5070'>5070&nbsp;&nbsp;&nbsp;&nbsp;Installation Subcontractors</option>
                                                <option value='5080'>5080&nbsp;&nbsp;&nbsp;&nbsp;Shipping</option>
                                                <option value='5090'>5090&nbsp;&nbsp;&nbsp;&nbsp;Sales Discounts</option>
                                                <option value='5110'>5110&nbsp;&nbsp;&nbsp;&nbsp;Assembly Costs</option>
                                                <option value='6095'>6095&nbsp;&nbsp;&nbsp;&nbsp;Early payment discounts (sales)</option>
                                                <optgroup label='Expenses'>
                                                   <option value='6050'>6050&nbsp;&nbsp;&nbsp;&nbsp;Exchange variance</option>
                                                   <option value='6060'>6060&nbsp;&nbsp;&nbsp;&nbsp;Accounting Fees</option>
                                                   <option value='6070'>6070&nbsp;&nbsp;&nbsp;&nbsp;Advertising</option>
                                                   <option value='6080'>6080&nbsp;&nbsp;&nbsp;&nbsp;Bad Debts</option>
                                                   <option value='6120'>6120&nbsp;&nbsp;&nbsp;&nbsp;Bank Fees</option>
                                                   <option value='6125'>6125&nbsp;&nbsp;&nbsp;&nbsp;Books and Publications</option>
                                                   <option value='6130'>6130&nbsp;&nbsp;&nbsp;&nbsp;Bookkeeping</option>
                                                   <option value='6131'>6131&nbsp;&nbsp;&nbsp;&nbsp;Cleaning &amp; Waste Removal</option>
                                                   <option value='6132'>6132&nbsp;&nbsp;&nbsp;&nbsp;Computers - Maintenance and Supplies</option>
                                                   <option value='6133'>6133&nbsp;&nbsp;&nbsp;&nbsp;Debt Collection Costs</option>
                                                   <option value='6134'>6134&nbsp;&nbsp;&nbsp;&nbsp;Depreciation</option>
                                                   <option value='6140'>6140&nbsp;&nbsp;&nbsp;&nbsp;Donations</option>
                                                   <option value='6150'>6150&nbsp;&nbsp;&nbsp;&nbsp;Discounts Received</option>
                                                   <option value='6160'>6160&nbsp;&nbsp;&nbsp;&nbsp;Dues and Subscriptions</option>
                                                   <option value='6170'>6170&nbsp;&nbsp;&nbsp;&nbsp;Entertainment/Client Gifts</option>
                                                   <option value='6180'>6180&nbsp;&nbsp;&nbsp;&nbsp;Equipment Hire</option>
                                                   <option value='6190'>6190&nbsp;&nbsp;&nbsp;&nbsp;Filing Fees</option>
                                                   <option value='6200'>6200&nbsp;&nbsp;&nbsp;&nbsp;Fines &amp; Penalties</option>
                                                   <option value='6205'>6205&nbsp;&nbsp;&nbsp;&nbsp;Fines - not tax deductable</option>
                                                   <option value='6207'>6207&nbsp;&nbsp;&nbsp;&nbsp;Freight paid on Purchases</option>
                                                   <option value='6210'>6210&nbsp;&nbsp;&nbsp;&nbsp;Insurance (non-vehicle)</option>
                                                   <option value='6220'>6220&nbsp;&nbsp;&nbsp;&nbsp;Insurance - WorkCover</option>
                                                   <option value='6250'>6250&nbsp;&nbsp;&nbsp;&nbsp;Leases - Equipment</option>
                                                   <option value='6260'>6260&nbsp;&nbsp;&nbsp;&nbsp;Legal Fees</option>
                                                   <option value='6270'>6270&nbsp;&nbsp;&nbsp;&nbsp;Merchant Fees - General</option>
                                                   <option value='6280'>6280&nbsp;&nbsp;&nbsp;&nbsp;Merchant Fees - Ebay</option>
                                                   <option value='6550'>6550&nbsp;&nbsp;&nbsp;&nbsp;Office Supplies/Stationery</option>
                                                   <option value='6560'>6560&nbsp;&nbsp;&nbsp;&nbsp;Permits etc</option>
                                                   <option value='6570'>6570&nbsp;&nbsp;&nbsp;&nbsp;Postage</option>
                                                   <option value='6580'>6580&nbsp;&nbsp;&nbsp;&nbsp;Printing</option>
                                                   <option value='6590'>6590&nbsp;&nbsp;&nbsp;&nbsp;Professional Fees</option>
                                                   <option value='6600'>6600&nbsp;&nbsp;&nbsp;&nbsp;Recruitment</option>
                                                   <option value='6610'>6610&nbsp;&nbsp;&nbsp;&nbsp;Samples/Product Development</option>
                                                   <option value='6620'>6620&nbsp;&nbsp;&nbsp;&nbsp;Shop Set-Up</option>
                                                   <option value='6630'>6630&nbsp;&nbsp;&nbsp;&nbsp;Small Capital Items</option>
                                                   <option value='6640'>6640&nbsp;&nbsp;&nbsp;&nbsp;Staff Amenities/Medical</option>
                                                   <option value='6650'>6650&nbsp;&nbsp;&nbsp;&nbsp;Tool Replacements</option>
                                                   <option value='6660'>6660&nbsp;&nbsp;&nbsp;&nbsp;Website/Online Shop Expenses</option>
                                                   <optgroup label='Occupancy Costs'>
                                                      <option value='6400'>6400&nbsp;&nbsp;&nbsp;&nbsp;Gas</option>
                                                      <option value='6401'>6401&nbsp;&nbsp;&nbsp;&nbsp;Cleaning &amp; Waste Removal</option>
                                                      <option value='6405'>6405&nbsp;&nbsp;&nbsp;&nbsp;Electricity</option>
                                                      <option value='6406'>6406&nbsp;&nbsp;&nbsp;&nbsp;Home Office Rent</option>
                                                      <option value='6407'>6407&nbsp;&nbsp;&nbsp;&nbsp;Rates &amp; Taxes</option>
                                                      <option value='6408'>6408&nbsp;&nbsp;&nbsp;&nbsp;Rent</option>
                                                      <option value='6409'>6409&nbsp;&nbsp;&nbsp;&nbsp;Repairs &amp; Maintenance</option>
                                                      <option value='6410'>6410&nbsp;&nbsp;&nbsp;&nbsp;Water</option>
                                                      <optgroup label='Motor Vehicle'>
                                                         <option value='6710'>6710&nbsp;&nbsp;&nbsp;&nbsp;Gas/Petrol/Oil</option>
                                                         <option value='6720'>6720&nbsp;&nbsp;&nbsp;&nbsp;Registration &amp; Insurance</option>
                                                         <option value='6730'>6730&nbsp;&nbsp;&nbsp;&nbsp;Vehicle Maintenance/Servicing</option>
                                                         <optgroup label='Telephone/Internet'>
                                                            <option value='6340'>6340&nbsp;&nbsp;&nbsp;&nbsp;Fax</option>
                                                            <option value='6341'>6341&nbsp;&nbsp;&nbsp;&nbsp;Telephone: Mobile</option>
                                                            <option value='6342'>6342&nbsp;&nbsp;&nbsp;&nbsp;Telephone: Office</option>
                                                            <option value='6343'>6343&nbsp;&nbsp;&nbsp;&nbsp;Telephone: VOIP</option>
                                                            <option value='6355'>6355&nbsp;&nbsp;&nbsp;&nbsp;Internet</option>
                                                            <optgroup label='Rental Flat Costs'>
                                                               <option value='6810'>6810&nbsp;&nbsp;&nbsp;&nbsp;Rental Agent Commission</option>
                                                               <option value='6820'>6820&nbsp;&nbsp;&nbsp;&nbsp;Repairs &amp; Maint to Flat</option>
                                                               <optgroup label='Travel Expenses'>
                                                                  <option value='6830'>6830&nbsp;&nbsp;&nbsp;&nbsp;Parking</option>
                                                                  <option value='6840'>6840&nbsp;&nbsp;&nbsp;&nbsp;Tolls</option>
                                                                  <option value='6850'>6850&nbsp;&nbsp;&nbsp;&nbsp;Travel Allowances</option>
                                                                  <optgroup label='Other Expenses'>
                                                                     <option value='8100'>8100&nbsp;&nbsp;&nbsp;&nbsp;Income Tax Expense</option>
                                                                     <option value='8210'>8210&nbsp;&nbsp;&nbsp;&nbsp;Interest - Bank</option>
                                                                     <option value='8220'>8220&nbsp;&nbsp;&nbsp;&nbsp;Interest - Credit Card</option>
                                                                     <option value='8230'>8230&nbsp;&nbsp;&nbsp;&nbsp;Interest - ATO</option>
                                                                     <option value='8300'>8300&nbsp;&nbsp;&nbsp;&nbsp;Suspense</option>
                                                                     <optgroup label='Employee Expenses'>
                                                                        <option value='6910'>6910&nbsp;&nbsp;&nbsp;&nbsp;Annual Leave</option>
                                                                        <option value='6920'>6920&nbsp;&nbsp;&nbsp;&nbsp;Protective Clothing/Uniforms</option>
                                                                        <option value='6930'>6930&nbsp;&nbsp;&nbsp;&nbsp;Salaries &amp; Wages</option>
                                                                        <option value='6940'>6940&nbsp;&nbsp;&nbsp;&nbsp;Superannuation</option>
                                                                        <option value='6950'>6950&nbsp;&nbsp;&nbsp;&nbsp;Training</option>
                                                                        <option value='6960'>6960&nbsp;&nbsp;&nbsp;&nbsp;WorkCover</option>
   </select>
</span>
   </td>
</tr>
<tr>
   <td class='label'>Sales Discount Account:</td>
   <td><span id='_br_sales_discount_account_sel'><select id='br_sales_discount_account' autocomplete='off' name='br_sales_discount_account' class='combo2' title=''>
      <optgroup label='Cash'>
         <option value='1700'>1700&nbsp;&nbsp;&nbsp;&nbsp;Adv NAB Cheque Account</option>
         <option value='1701'>1701&nbsp;&nbsp;&nbsp;&nbsp;MSH NAB Cheque Account</option>
         <option value='1702'>1702&nbsp;&nbsp;&nbsp;&nbsp;Westpac Cheque Account</option>
         <option value='1703'>1703&nbsp;&nbsp;&nbsp;&nbsp;Cash on Hand</option>
         <option value='1705'>1705&nbsp;&nbsp;&nbsp;&nbsp;Petty Cash</option>
         <option value='1706'>1706&nbsp;&nbsp;&nbsp;&nbsp;Mike&#039;s Wallet</option>
         <option value='1707'>1707&nbsp;&nbsp;&nbsp;&nbsp;Paypal Account</option>
         <option value='1708'>1708&nbsp;&nbsp;&nbsp;&nbsp;Undeposited Funds</option>
         <optgroup label='Receivables'>
            <option value='1510'>1510&nbsp;&nbsp;&nbsp;&nbsp;Employee Advances</option>
            <option value='1800'>1800&nbsp;&nbsp;&nbsp;&nbsp;Accounts receivable</option>
            <optgroup label='Prepayments'>
               <option value='1900'>1900&nbsp;&nbsp;&nbsp;&nbsp;Deposits Paid</option>
               <option value='1910'>1910&nbsp;&nbsp;&nbsp;&nbsp;Prepaid Insurance</option>
               <option value='1920'>1920&nbsp;&nbsp;&nbsp;&nbsp;Prepaid Workcover</option>
               <optgroup label='Inventory'>
                  <option value='1200'>1200&nbsp;&nbsp;&nbsp;&nbsp;Inventory</option>
                  <option value='1205'>1205&nbsp;&nbsp;&nbsp;&nbsp;Inventory adjustment</option>
                  <optgroup label='Fixed Assets'>
                     <option value='1010'>1010&nbsp;&nbsp;&nbsp;&nbsp;Office Equipment</option>
                     <option value='1011'>1011&nbsp;&nbsp;&nbsp;&nbsp;Office Equipment Accumulated Depreciation</option>
                     <option value='1020'>1020&nbsp;&nbsp;&nbsp;&nbsp;Computer Equipment</option>
                     <option value='1021'>1021&nbsp;&nbsp;&nbsp;&nbsp;Computer Equipment Accumulated Depreciation</option>
                     <option value='1030'>1030&nbsp;&nbsp;&nbsp;&nbsp;Motor Vehicles</option>
                     <option value='1031'>1031&nbsp;&nbsp;&nbsp;&nbsp;Motor Vehicles Accumulated Depreciation</option>
                     <option value='1040'>1040&nbsp;&nbsp;&nbsp;&nbsp;Property, plant, equip.</option>
                     <option value='1041'>1041&nbsp;&nbsp;&nbsp;&nbsp;Plant &amp; Equip Accumulated Depreciation</option>
                     <option value='1050'>1050&nbsp;&nbsp;&nbsp;&nbsp;Goodwill</option>
                     <optgroup label='Credit Cards'>
                        <option value='2100'>2100&nbsp;&nbsp;&nbsp;&nbsp;NAB Mastercard</option>
                        <option value='2200'>2200&nbsp;&nbsp;&nbsp;&nbsp;NAB Visa</option>
                        <optgroup label='Accounts payable'>
                           <option value='20000'>20000&nbsp;&nbsp;&nbsp;&nbsp;Accounts Payable</option>
                           <option value='2300'>2300&nbsp;&nbsp;&nbsp;&nbsp;Sundry Creditors</option>
                           <optgroup label='Tax liabilities'>
                              <option value='2410'>2410&nbsp;&nbsp;&nbsp;&nbsp;PAYG Withheld</option>
                              <option value='2421'>2421&nbsp;&nbsp;&nbsp;&nbsp;GST Collected</option>
                              <option value='2422'>2422&nbsp;&nbsp;&nbsp;&nbsp;Other GST Collected</option>
                              <option value='2430'>2430&nbsp;&nbsp;&nbsp;&nbsp;GST Paid</option>
                              <option value='2431'>2431&nbsp;&nbsp;&nbsp;&nbsp;Other GST Paid</option>
                              <option value='2440'>2440&nbsp;&nbsp;&nbsp;&nbsp;BAS Clearing</option>
                              <optgroup label='Other current liabilities'>
                                 <option value='2510'>2510&nbsp;&nbsp;&nbsp;&nbsp;Accrued Superannuation</option>
                                 <option value='2520'>2520&nbsp;&nbsp;&nbsp;&nbsp;Provision for Annual Leave</option>
                                 <option value='2530'>2530&nbsp;&nbsp;&nbsp;&nbsp;Customer Deposits</option>
                                 <option value='2540'>2540&nbsp;&nbsp;&nbsp;&nbsp;Wages Clearing Account</option>
                                 <optgroup label='Loans'>
                                    <option value='2610'>2610&nbsp;&nbsp;&nbsp;&nbsp;Director&#039;s Loan Account</option>
                                    <option value='2621'>2621&nbsp;&nbsp;&nbsp;&nbsp;Katie&#039;s HSBC Visa</option>
                                    <option value='2622'>2622&nbsp;&nbsp;&nbsp;&nbsp;Katie&#039;s Line of Credit</option>
                                    <option value='2623'>2623&nbsp;&nbsp;&nbsp;&nbsp;Katie&#039;s ANZ Mastercard</option>
                                    <option value='2624'>2624&nbsp;&nbsp;&nbsp;&nbsp;Katie&#039;s CBA Mastercard</option>
                                    <option value='2625'>2625&nbsp;&nbsp;&nbsp;&nbsp;Katie&#039;s Personal Loan</option>
                                    <option value='2700'>2700&nbsp;&nbsp;&nbsp;&nbsp;Van Loan</option>
                                    <option value='2800'>2800&nbsp;&nbsp;&nbsp;&nbsp;Establishment Loan</option>
                                    <option value='2900'>2900&nbsp;&nbsp;&nbsp;&nbsp;Advanced Super Investments Pty Ltd</option>
                                    <optgroup label='Equity'>
                                       <option value='3100'>3100&nbsp;&nbsp;&nbsp;&nbsp;Owners Capital</option>
                                       <option value='3200'>3200&nbsp;&nbsp;&nbsp;&nbsp;Director&#039;s Drawings</option>
                                       <option value='3300'>3300&nbsp;&nbsp;&nbsp;&nbsp;Current Year Profit/Loss</option>
                                       <option value='3400'>3400&nbsp;&nbsp;&nbsp;&nbsp;Retained Earnings</option>
                                       <option value='3500'>3500&nbsp;&nbsp;&nbsp;&nbsp;Historical Balancing</option>
                                       <optgroup label='Sales Income'>
                                          <option value='4010'>4010&nbsp;&nbsp;&nbsp;&nbsp;Retail Sales</option>
                                          <option value='4050'>4050&nbsp;&nbsp;&nbsp;&nbsp;Web Sales</option>
                                          <option value='4110'>4110&nbsp;&nbsp;&nbsp;&nbsp;Export Sales</option>
                                          <option value='4111'>4111&nbsp;&nbsp;&nbsp;&nbsp;Custom Sign Sales</option>
                                          <option value='4112'>4112&nbsp;&nbsp;&nbsp;&nbsp;Installation Income</option>
                                          <option value='4150'>4150&nbsp;&nbsp;&nbsp;&nbsp;Freight Collected</option>
                                          <option value='4151'>4151&nbsp;&nbsp;&nbsp;&nbsp;Equipment Rental</option>
                                          <option value='4152'>4152&nbsp;&nbsp;&nbsp;&nbsp;Line Marking</option>
                                          <option value='4160'>4160&nbsp;&nbsp;&nbsp;&nbsp;Restocking Fees</option>
                                          <option value='4170'>4170&nbsp;&nbsp;&nbsp;&nbsp;Sales - Motor Sprot Heaven</option>
                                          <option value='4180'>4180&nbsp;&nbsp;&nbsp;&nbsp;Miscellaneous Income</option>
                                          <option value='4190'>4190&nbsp;&nbsp;&nbsp;&nbsp;AMEX Fees Collected</option>
                                          <optgroup label='Other income'>
                                             <option value='7010'>7010&nbsp;&nbsp;&nbsp;&nbsp;Interest Income</option>
                                             <option value='7020'>7020&nbsp;&nbsp;&nbsp;&nbsp;Rental Income</option>
                                             <option value='7030'>7030&nbsp;&nbsp;&nbsp;&nbsp;Other Income</option>
                                             <option value='7040'>7040&nbsp;&nbsp;&nbsp;&nbsp;Government Payments</option>
                                             <optgroup label='Cost of Goods Sold'>
                                                <option value='5040'>5040&nbsp;&nbsp;&nbsp;&nbsp;Materials &amp; Supplies</option>
                                                <option value='5050'>5050&nbsp;&nbsp;&nbsp;&nbsp;Cost of Goods Sold</option>
                                                <option value='5060'>5060&nbsp;&nbsp;&nbsp;&nbsp;Linemarking Subcontractors</option>
                                                <option value='5070'>5070&nbsp;&nbsp;&nbsp;&nbsp;Installation Subcontractors</option>
                                                <option value='5080'>5080&nbsp;&nbsp;&nbsp;&nbsp;Shipping</option>
                                                <option selected value='5090'>5090&nbsp;&nbsp;&nbsp;&nbsp;Sales Discounts</option>
                                                <option value='5110'>5110&nbsp;&nbsp;&nbsp;&nbsp;Assembly Costs</option>
                                                <option value='6095'>6095&nbsp;&nbsp;&nbsp;&nbsp;Early payment discounts (sales)</option>
                                                <optgroup label='Expenses'>
                                                   <option value='6050'>6050&nbsp;&nbsp;&nbsp;&nbsp;Exchange variance</option>
                                                   <option value='6060'>6060&nbsp;&nbsp;&nbsp;&nbsp;Accounting Fees</option>
                                                   <option value='6070'>6070&nbsp;&nbsp;&nbsp;&nbsp;Advertising</option>
                                                   <option value='6080'>6080&nbsp;&nbsp;&nbsp;&nbsp;Bad Debts</option>
                                                   <option value='6120'>6120&nbsp;&nbsp;&nbsp;&nbsp;Bank Fees</option>
                                                   <option value='6125'>6125&nbsp;&nbsp;&nbsp;&nbsp;Books and Publications</option>
                                                   <option value='6130'>6130&nbsp;&nbsp;&nbsp;&nbsp;Bookkeeping</option>
                                                   <option value='6131'>6131&nbsp;&nbsp;&nbsp;&nbsp;Cleaning &amp; Waste Removal</option>
                                                   <option value='6132'>6132&nbsp;&nbsp;&nbsp;&nbsp;Computers - Maintenance and Supplies</option>
                                                   <option value='6133'>6133&nbsp;&nbsp;&nbsp;&nbsp;Debt Collection Costs</option>
                                                   <option value='6134'>6134&nbsp;&nbsp;&nbsp;&nbsp;Depreciation</option>
                                                   <option value='6140'>6140&nbsp;&nbsp;&nbsp;&nbsp;Donations</option>
                                                   <option value='6150'>6150&nbsp;&nbsp;&nbsp;&nbsp;Discounts Received</option>
                                                   <option value='6160'>6160&nbsp;&nbsp;&nbsp;&nbsp;Dues and Subscriptions</option>
                                                   <option value='6170'>6170&nbsp;&nbsp;&nbsp;&nbsp;Entertainment/Client Gifts</option>
                                                   <option value='6180'>6180&nbsp;&nbsp;&nbsp;&nbsp;Equipment Hire</option>
                                                   <option value='6190'>6190&nbsp;&nbsp;&nbsp;&nbsp;Filing Fees</option>
                                                   <option value='6200'>6200&nbsp;&nbsp;&nbsp;&nbsp;Fines &amp; Penalties</option>
                                                   <option value='6205'>6205&nbsp;&nbsp;&nbsp;&nbsp;Fines - not tax deductable</option>
                                                   <option value='6207'>6207&nbsp;&nbsp;&nbsp;&nbsp;Freight paid on Purchases</option>
                                                   <option value='6210'>6210&nbsp;&nbsp;&nbsp;&nbsp;Insurance (non-vehicle)</option>
                                                   <option value='6220'>6220&nbsp;&nbsp;&nbsp;&nbsp;Insurance - WorkCover</option>
                                                   <option value='6250'>6250&nbsp;&nbsp;&nbsp;&nbsp;Leases - Equipment</option>
                                                   <option value='6260'>6260&nbsp;&nbsp;&nbsp;&nbsp;Legal Fees</option>
                                                   <option value='6270'>6270&nbsp;&nbsp;&nbsp;&nbsp;Merchant Fees - General</option>
                                                   <option value='6280'>6280&nbsp;&nbsp;&nbsp;&nbsp;Merchant Fees - Ebay</option>
                                                   <option value='6550'>6550&nbsp;&nbsp;&nbsp;&nbsp;Office Supplies/Stationery</option>
                                                   <option value='6560'>6560&nbsp;&nbsp;&nbsp;&nbsp;Permits etc</option>
                                                   <option value='6570'>6570&nbsp;&nbsp;&nbsp;&nbsp;Postage</option>
                                                   <option value='6580'>6580&nbsp;&nbsp;&nbsp;&nbsp;Printing</option>
                                                   <option value='6590'>6590&nbsp;&nbsp;&nbsp;&nbsp;Professional Fees</option>
                                                   <option value='6600'>6600&nbsp;&nbsp;&nbsp;&nbsp;Recruitment</option>
                                                   <option value='6610'>6610&nbsp;&nbsp;&nbsp;&nbsp;Samples/Product Development</option>
                                                   <option value='6620'>6620&nbsp;&nbsp;&nbsp;&nbsp;Shop Set-Up</option>
                                                   <option value='6630'>6630&nbsp;&nbsp;&nbsp;&nbsp;Small Capital Items</option>
                                                   <option value='6640'>6640&nbsp;&nbsp;&nbsp;&nbsp;Staff Amenities/Medical</option>
                                                   <option value='6650'>6650&nbsp;&nbsp;&nbsp;&nbsp;Tool Replacements</option>
                                                   <option value='6660'>6660&nbsp;&nbsp;&nbsp;&nbsp;Website/Online Shop Expenses</option>
                                                   <optgroup label='Occupancy Costs'>
                                                      <option value='6400'>6400&nbsp;&nbsp;&nbsp;&nbsp;Gas</option>
                                                      <option value='6401'>6401&nbsp;&nbsp;&nbsp;&nbsp;Cleaning &amp; Waste Removal</option>
                                                      <option value='6405'>6405&nbsp;&nbsp;&nbsp;&nbsp;Electricity</option>
                                                      <option value='6406'>6406&nbsp;&nbsp;&nbsp;&nbsp;Home Office Rent</option>
                                                      <option value='6407'>6407&nbsp;&nbsp;&nbsp;&nbsp;Rates &amp; Taxes</option>
                                                      <option value='6408'>6408&nbsp;&nbsp;&nbsp;&nbsp;Rent</option>
                                                      <option value='6409'>6409&nbsp;&nbsp;&nbsp;&nbsp;Repairs &amp; Maintenance</option>
                                                      <option value='6410'>6410&nbsp;&nbsp;&nbsp;&nbsp;Water</option>
                                                      <optgroup label='Motor Vehicle'>
                                                         <option value='6710'>6710&nbsp;&nbsp;&nbsp;&nbsp;Gas/Petrol/Oil</option>
                                                         <option value='6720'>6720&nbsp;&nbsp;&nbsp;&nbsp;Registration &amp; Insurance</option>
                                                         <option value='6730'>6730&nbsp;&nbsp;&nbsp;&nbsp;Vehicle Maintenance/Servicing</option>
                                                         <optgroup label='Telephone/Internet'>
                                                            <option value='6340'>6340&nbsp;&nbsp;&nbsp;&nbsp;Fax</option>
                                                            <option value='6341'>6341&nbsp;&nbsp;&nbsp;&nbsp;Telephone: Mobile</option>
                                                            <option value='6342'>6342&nbsp;&nbsp;&nbsp;&nbsp;Telephone: Office</option>
                                                            <option value='6343'>6343&nbsp;&nbsp;&nbsp;&nbsp;Telephone: VOIP</option>
                                                            <option value='6355'>6355&nbsp;&nbsp;&nbsp;&nbsp;Internet</option>
                                                            <optgroup label='Rental Flat Costs'>
                                                               <option value='6810'>6810&nbsp;&nbsp;&nbsp;&nbsp;Rental Agent Commission</option>
                                                               <option value='6820'>6820&nbsp;&nbsp;&nbsp;&nbsp;Repairs &amp; Maint to Flat</option>
                                                               <optgroup label='Travel Expenses'>
                                                                  <option value='6830'>6830&nbsp;&nbsp;&nbsp;&nbsp;Parking</option>
                                                                  <option value='6840'>6840&nbsp;&nbsp;&nbsp;&nbsp;Tolls</option>
                                                                  <option value='6850'>6850&nbsp;&nbsp;&nbsp;&nbsp;Travel Allowances</option>
                                                                  <optgroup label='Other Expenses'>
                                                                     <option value='8100'>8100&nbsp;&nbsp;&nbsp;&nbsp;Income Tax Expense</option>
                                                                     <option value='8210'>8210&nbsp;&nbsp;&nbsp;&nbsp;Interest - Bank</option>
                                                                     <option value='8220'>8220&nbsp;&nbsp;&nbsp;&nbsp;Interest - Credit Card</option>
                                                                     <option value='8230'>8230&nbsp;&nbsp;&nbsp;&nbsp;Interest - ATO</option>
                                                                     <option value='8300'>8300&nbsp;&nbsp;&nbsp;&nbsp;Suspense</option>
                                                                     <optgroup label='Employee Expenses'>
                                                                        <option value='6910'>6910&nbsp;&nbsp;&nbsp;&nbsp;Annual Leave</option>
                                                                        <option value='6920'>6920&nbsp;&nbsp;&nbsp;&nbsp;Protective Clothing/Uniforms</option>
                                                                        <option value='6930'>6930&nbsp;&nbsp;&nbsp;&nbsp;Salaries &amp; Wages</option>
                                                                        <option value='6940'>6940&nbsp;&nbsp;&nbsp;&nbsp;Superannuation</option>
                                                                        <option value='6950'>6950&nbsp;&nbsp;&nbsp;&nbsp;Training</option>
                                                                        <option value='6960'>6960&nbsp;&nbsp;&nbsp;&nbsp;WorkCover</option>
   </select>
</span>
   </td>
</tr>
<tr>
   <td class='label'>Accounts Receivable Account:</td>
   <td><span id='_br_receivables_account_sel'><select id='br_receivables_account' autocomplete='off' name='br_receivables_account' class='combo2' title=''>
      <optgroup label='Cash'>
         <option value='1700'>1700&nbsp;&nbsp;&nbsp;&nbsp;Adv NAB Cheque Account</option>
         <option value='1701'>1701&nbsp;&nbsp;&nbsp;&nbsp;MSH NAB Cheque Account</option>
         <option value='1702'>1702&nbsp;&nbsp;&nbsp;&nbsp;Westpac Cheque Account</option>
         <option value='1703'>1703&nbsp;&nbsp;&nbsp;&nbsp;Cash on Hand</option>
         <option value='1705'>1705&nbsp;&nbsp;&nbsp;&nbsp;Petty Cash</option>
         <option value='1706'>1706&nbsp;&nbsp;&nbsp;&nbsp;Mike&#039;s Wallet</option>
         <option value='1707'>1707&nbsp;&nbsp;&nbsp;&nbsp;Paypal Account</option>
         <option value='1708'>1708&nbsp;&nbsp;&nbsp;&nbsp;Undeposited Funds</option>
         <optgroup label='Receivables'>
            <option value='1510'>1510&nbsp;&nbsp;&nbsp;&nbsp;Employee Advances</option>
            <option selected value='1800'>1800&nbsp;&nbsp;&nbsp;&nbsp;Accounts receivable</option>
            <optgroup label='Prepayments'>
               <option value='1900'>1900&nbsp;&nbsp;&nbsp;&nbsp;Deposits Paid</option>
               <option value='1910'>1910&nbsp;&nbsp;&nbsp;&nbsp;Prepaid Insurance</option>
               <option value='1920'>1920&nbsp;&nbsp;&nbsp;&nbsp;Prepaid Workcover</option>
               <optgroup label='Inventory'>
                  <option value='1200'>1200&nbsp;&nbsp;&nbsp;&nbsp;Inventory</option>
                  <option value='1205'>1205&nbsp;&nbsp;&nbsp;&nbsp;Inventory adjustment</option>
                  <optgroup label='Fixed Assets'>
                     <option value='1010'>1010&nbsp;&nbsp;&nbsp;&nbsp;Office Equipment</option>
                     <option value='1011'>1011&nbsp;&nbsp;&nbsp;&nbsp;Office Equipment Accumulated Depreciation</option>
                     <option value='1020'>1020&nbsp;&nbsp;&nbsp;&nbsp;Computer Equipment</option>
                     <option value='1021'>1021&nbsp;&nbsp;&nbsp;&nbsp;Computer Equipment Accumulated Depreciation</option>
                     <option value='1030'>1030&nbsp;&nbsp;&nbsp;&nbsp;Motor Vehicles</option>
                     <option value='1031'>1031&nbsp;&nbsp;&nbsp;&nbsp;Motor Vehicles Accumulated Depreciation</option>
                     <option value='1040'>1040&nbsp;&nbsp;&nbsp;&nbsp;Property, plant, equip.</option>
                     <option value='1041'>1041&nbsp;&nbsp;&nbsp;&nbsp;Plant &amp; Equip Accumulated Depreciation</option>
                     <option value='1050'>1050&nbsp;&nbsp;&nbsp;&nbsp;Goodwill</option>
                     <optgroup label='Credit Cards'>
                        <option value='2100'>2100&nbsp;&nbsp;&nbsp;&nbsp;NAB Mastercard</option>
                        <option value='2200'>2200&nbsp;&nbsp;&nbsp;&nbsp;NAB Visa</option>
                        <optgroup label='Accounts payable'>
                           <option value='20000'>20000&nbsp;&nbsp;&nbsp;&nbsp;Accounts Payable</option>
                           <option value='2300'>2300&nbsp;&nbsp;&nbsp;&nbsp;Sundry Creditors</option>
                           <optgroup label='Tax liabilities'>
                              <option value='2410'>2410&nbsp;&nbsp;&nbsp;&nbsp;PAYG Withheld</option>
                              <option value='2421'>2421&nbsp;&nbsp;&nbsp;&nbsp;GST Collected</option>
                              <option value='2422'>2422&nbsp;&nbsp;&nbsp;&nbsp;Other GST Collected</option>
                              <option value='2430'>2430&nbsp;&nbsp;&nbsp;&nbsp;GST Paid</option>
                              <option value='2431'>2431&nbsp;&nbsp;&nbsp;&nbsp;Other GST Paid</option>
                              <option value='2440'>2440&nbsp;&nbsp;&nbsp;&nbsp;BAS Clearing</option>
                              <optgroup label='Other current liabilities'>
                                 <option value='2510'>2510&nbsp;&nbsp;&nbsp;&nbsp;Accrued Superannuation</option>
                                 <option value='2520'>2520&nbsp;&nbsp;&nbsp;&nbsp;Provision for Annual Leave</option>
                                 <option value='2530'>2530&nbsp;&nbsp;&nbsp;&nbsp;Customer Deposits</option>
                                 <option value='2540'>2540&nbsp;&nbsp;&nbsp;&nbsp;Wages Clearing Account</option>
                                 <optgroup label='Loans'>
                                    <option value='2610'>2610&nbsp;&nbsp;&nbsp;&nbsp;Director&#039;s Loan Account</option>
                                    <option value='2621'>2621&nbsp;&nbsp;&nbsp;&nbsp;Katie&#039;s HSBC Visa</option>
                                    <option value='2622'>2622&nbsp;&nbsp;&nbsp;&nbsp;Katie&#039;s Line of Credit</option>
                                    <option value='2623'>2623&nbsp;&nbsp;&nbsp;&nbsp;Katie&#039;s ANZ Mastercard</option>
                                    <option value='2624'>2624&nbsp;&nbsp;&nbsp;&nbsp;Katie&#039;s CBA Mastercard</option>
                                    <option value='2625'>2625&nbsp;&nbsp;&nbsp;&nbsp;Katie&#039;s Personal Loan</option>
                                    <option value='2700'>2700&nbsp;&nbsp;&nbsp;&nbsp;Van Loan</option>
                                    <option value='2800'>2800&nbsp;&nbsp;&nbsp;&nbsp;Establishment Loan</option>
                                    <option value='2900'>2900&nbsp;&nbsp;&nbsp;&nbsp;Advanced Super Investments Pty Ltd</option>
                                    <optgroup label='Equity'>
                                       <option value='3100'>3100&nbsp;&nbsp;&nbsp;&nbsp;Owners Capital</option>
                                       <option value='3200'>3200&nbsp;&nbsp;&nbsp;&nbsp;Director&#039;s Drawings</option>
                                       <option value='3300'>3300&nbsp;&nbsp;&nbsp;&nbsp;Current Year Profit/Loss</option>
                                       <option value='3400'>3400&nbsp;&nbsp;&nbsp;&nbsp;Retained Earnings</option>
                                       <option value='3500'>3500&nbsp;&nbsp;&nbsp;&nbsp;Historical Balancing</option>
                                       <optgroup label='Sales Income'>
                                          <option value='4010'>4010&nbsp;&nbsp;&nbsp;&nbsp;Retail Sales</option>
                                          <option value='4050'>4050&nbsp;&nbsp;&nbsp;&nbsp;Web Sales</option>
                                          <option value='4110'>4110&nbsp;&nbsp;&nbsp;&nbsp;Export Sales</option>
                                          <option value='4111'>4111&nbsp;&nbsp;&nbsp;&nbsp;Custom Sign Sales</option>
                                          <option value='4112'>4112&nbsp;&nbsp;&nbsp;&nbsp;Installation Income</option>
                                          <option value='4150'>4150&nbsp;&nbsp;&nbsp;&nbsp;Freight Collected</option>
                                          <option value='4151'>4151&nbsp;&nbsp;&nbsp;&nbsp;Equipment Rental</option>
                                          <option value='4152'>4152&nbsp;&nbsp;&nbsp;&nbsp;Line Marking</option>
                                          <option value='4160'>4160&nbsp;&nbsp;&nbsp;&nbsp;Restocking Fees</option>
                                          <option value='4170'>4170&nbsp;&nbsp;&nbsp;&nbsp;Sales - Motor Sprot Heaven</option>
                                          <option value='4180'>4180&nbsp;&nbsp;&nbsp;&nbsp;Miscellaneous Income</option>
                                          <option value='4190'>4190&nbsp;&nbsp;&nbsp;&nbsp;AMEX Fees Collected</option>
                                          <optgroup label='Other income'>
                                             <option value='7010'>7010&nbsp;&nbsp;&nbsp;&nbsp;Interest Income</option>
                                             <option value='7020'>7020&nbsp;&nbsp;&nbsp;&nbsp;Rental Income</option>
                                             <option value='7030'>7030&nbsp;&nbsp;&nbsp;&nbsp;Other Income</option>
                                             <option value='7040'>7040&nbsp;&nbsp;&nbsp;&nbsp;Government Payments</option>
                                             <optgroup label='Cost of Goods Sold'>
                                                <option value='5040'>5040&nbsp;&nbsp;&nbsp;&nbsp;Materials &amp; Supplies</option>
                                                <option value='5050'>5050&nbsp;&nbsp;&nbsp;&nbsp;Cost of Goods Sold</option>
                                                <option value='5060'>5060&nbsp;&nbsp;&nbsp;&nbsp;Linemarking Subcontractors</option>
                                                <option value='5070'>5070&nbsp;&nbsp;&nbsp;&nbsp;Installation Subcontractors</option>
                                                <option value='5080'>5080&nbsp;&nbsp;&nbsp;&nbsp;Shipping</option>
                                                <option value='5090'>5090&nbsp;&nbsp;&nbsp;&nbsp;Sales Discounts</option>
                                                <option value='5110'>5110&nbsp;&nbsp;&nbsp;&nbsp;Assembly Costs</option>
                                                <option value='6095'>6095&nbsp;&nbsp;&nbsp;&nbsp;Early payment discounts (sales)</option>
                                                <optgroup label='Expenses'>
                                                   <option value='6050'>6050&nbsp;&nbsp;&nbsp;&nbsp;Exchange variance</option>
                                                   <option value='6060'>6060&nbsp;&nbsp;&nbsp;&nbsp;Accounting Fees</option>
                                                   <option value='6070'>6070&nbsp;&nbsp;&nbsp;&nbsp;Advertising</option>
                                                   <option value='6080'>6080&nbsp;&nbsp;&nbsp;&nbsp;Bad Debts</option>
                                                   <option value='6120'>6120&nbsp;&nbsp;&nbsp;&nbsp;Bank Fees</option>
                                                   <option value='6125'>6125&nbsp;&nbsp;&nbsp;&nbsp;Books and Publications</option>
                                                   <option value='6130'>6130&nbsp;&nbsp;&nbsp;&nbsp;Bookkeeping</option>
                                                   <option value='6131'>6131&nbsp;&nbsp;&nbsp;&nbsp;Cleaning &amp; Waste Removal</option>
                                                   <option value='6132'>6132&nbsp;&nbsp;&nbsp;&nbsp;Computers - Maintenance and Supplies</option>
                                                   <option value='6133'>6133&nbsp;&nbsp;&nbsp;&nbsp;Debt Collection Costs</option>
                                                   <option value='6134'>6134&nbsp;&nbsp;&nbsp;&nbsp;Depreciation</option>
                                                   <option value='6140'>6140&nbsp;&nbsp;&nbsp;&nbsp;Donations</option>
                                                   <option value='6150'>6150&nbsp;&nbsp;&nbsp;&nbsp;Discounts Received</option>
                                                   <option value='6160'>6160&nbsp;&nbsp;&nbsp;&nbsp;Dues and Subscriptions</option>
                                                   <option value='6170'>6170&nbsp;&nbsp;&nbsp;&nbsp;Entertainment/Client Gifts</option>
                                                   <option value='6180'>6180&nbsp;&nbsp;&nbsp;&nbsp;Equipment Hire</option>
                                                   <option value='6190'>6190&nbsp;&nbsp;&nbsp;&nbsp;Filing Fees</option>
                                                   <option value='6200'>6200&nbsp;&nbsp;&nbsp;&nbsp;Fines &amp; Penalties</option>
                                                   <option value='6205'>6205&nbsp;&nbsp;&nbsp;&nbsp;Fines - not tax deductable</option>
                                                   <option value='6207'>6207&nbsp;&nbsp;&nbsp;&nbsp;Freight paid on Purchases</option>
                                                   <option value='6210'>6210&nbsp;&nbsp;&nbsp;&nbsp;Insurance (non-vehicle)</option>
                                                   <option value='6220'>6220&nbsp;&nbsp;&nbsp;&nbsp;Insurance - WorkCover</option>
                                                   <option value='6250'>6250&nbsp;&nbsp;&nbsp;&nbsp;Leases - Equipment</option>
                                                   <option value='6260'>6260&nbsp;&nbsp;&nbsp;&nbsp;Legal Fees</option>
                                                   <option value='6270'>6270&nbsp;&nbsp;&nbsp;&nbsp;Merchant Fees - General</option>
                                                   <option value='6280'>6280&nbsp;&nbsp;&nbsp;&nbsp;Merchant Fees - Ebay</option>
                                                   <option value='6550'>6550&nbsp;&nbsp;&nbsp;&nbsp;Office Supplies/Stationery</option>
                                                   <option value='6560'>6560&nbsp;&nbsp;&nbsp;&nbsp;Permits etc</option>
                                                   <option value='6570'>6570&nbsp;&nbsp;&nbsp;&nbsp;Postage</option>
                                                   <option value='6580'>6580&nbsp;&nbsp;&nbsp;&nbsp;Printing</option>
                                                   <option value='6590'>6590&nbsp;&nbsp;&nbsp;&nbsp;Professional Fees</option>
                                                   <option value='6600'>6600&nbsp;&nbsp;&nbsp;&nbsp;Recruitment</option>
                                                   <option value='6610'>6610&nbsp;&nbsp;&nbsp;&nbsp;Samples/Product Development</option>
                                                   <option value='6620'>6620&nbsp;&nbsp;&nbsp;&nbsp;Shop Set-Up</option>
                                                   <option value='6630'>6630&nbsp;&nbsp;&nbsp;&nbsp;Small Capital Items</option>
                                                   <option value='6640'>6640&nbsp;&nbsp;&nbsp;&nbsp;Staff Amenities/Medical</option>
                                                   <option value='6650'>6650&nbsp;&nbsp;&nbsp;&nbsp;Tool Replacements</option>
                                                   <option value='6660'>6660&nbsp;&nbsp;&nbsp;&nbsp;Website/Online Shop Expenses</option>
                                                   <optgroup label='Occupancy Costs'>
                                                      <option value='6400'>6400&nbsp;&nbsp;&nbsp;&nbsp;Gas</option>
                                                      <option value='6401'>6401&nbsp;&nbsp;&nbsp;&nbsp;Cleaning &amp; Waste Removal</option>
                                                      <option value='6405'>6405&nbsp;&nbsp;&nbsp;&nbsp;Electricity</option>
                                                      <option value='6406'>6406&nbsp;&nbsp;&nbsp;&nbsp;Home Office Rent</option>
                                                      <option value='6407'>6407&nbsp;&nbsp;&nbsp;&nbsp;Rates &amp; Taxes</option>
                                                      <option value='6408'>6408&nbsp;&nbsp;&nbsp;&nbsp;Rent</option>
                                                      <option value='6409'>6409&nbsp;&nbsp;&nbsp;&nbsp;Repairs &amp; Maintenance</option>
                                                      <option value='6410'>6410&nbsp;&nbsp;&nbsp;&nbsp;Water</option>
                                                      <optgroup label='Motor Vehicle'>
                                                         <option value='6710'>6710&nbsp;&nbsp;&nbsp;&nbsp;Gas/Petrol/Oil</option>
                                                         <option value='6720'>6720&nbsp;&nbsp;&nbsp;&nbsp;Registration &amp; Insurance</option>
                                                         <option value='6730'>6730&nbsp;&nbsp;&nbsp;&nbsp;Vehicle Maintenance/Servicing</option>
                                                         <optgroup label='Telephone/Internet'>
                                                            <option value='6340'>6340&nbsp;&nbsp;&nbsp;&nbsp;Fax</option>
                                                            <option value='6341'>6341&nbsp;&nbsp;&nbsp;&nbsp;Telephone: Mobile</option>
                                                            <option value='6342'>6342&nbsp;&nbsp;&nbsp;&nbsp;Telephone: Office</option>
                                                            <option value='6343'>6343&nbsp;&nbsp;&nbsp;&nbsp;Telephone: VOIP</option>
                                                            <option value='6355'>6355&nbsp;&nbsp;&nbsp;&nbsp;Internet</option>
                                                            <optgroup label='Rental Flat Costs'>
                                                               <option value='6810'>6810&nbsp;&nbsp;&nbsp;&nbsp;Rental Agent Commission</option>
                                                               <option value='6820'>6820&nbsp;&nbsp;&nbsp;&nbsp;Repairs &amp; Maint to Flat</option>
                                                               <optgroup label='Travel Expenses'>
                                                                  <option value='6830'>6830&nbsp;&nbsp;&nbsp;&nbsp;Parking</option>
                                                                  <option value='6840'>6840&nbsp;&nbsp;&nbsp;&nbsp;Tolls</option>
                                                                  <option value='6850'>6850&nbsp;&nbsp;&nbsp;&nbsp;Travel Allowances</option>
                                                                  <optgroup label='Other Expenses'>
                                                                     <option value='8100'>8100&nbsp;&nbsp;&nbsp;&nbsp;Income Tax Expense</option>
                                                                     <option value='8210'>8210&nbsp;&nbsp;&nbsp;&nbsp;Interest - Bank</option>
                                                                     <option value='8220'>8220&nbsp;&nbsp;&nbsp;&nbsp;Interest - Credit Card</option>
                                                                     <option value='8230'>8230&nbsp;&nbsp;&nbsp;&nbsp;Interest - ATO</option>
                                                                     <option value='8300'>8300&nbsp;&nbsp;&nbsp;&nbsp;Suspense</option>
                                                                     <optgroup label='Employee Expenses'>
                                                                        <option value='6910'>6910&nbsp;&nbsp;&nbsp;&nbsp;Annual Leave</option>
                                                                        <option value='6920'>6920&nbsp;&nbsp;&nbsp;&nbsp;Protective Clothing/Uniforms</option>
                                                                        <option value='6930'>6930&nbsp;&nbsp;&nbsp;&nbsp;Salaries &amp; Wages</option>
                                                                        <option value='6940'>6940&nbsp;&nbsp;&nbsp;&nbsp;Superannuation</option>
                                                                        <option value='6950'>6950&nbsp;&nbsp;&nbsp;&nbsp;Training</option>
                                                                        <option value='6960'>6960&nbsp;&nbsp;&nbsp;&nbsp;WorkCover</option>
   </select>
</span>
   </td>
</tr>
<tr>
   <td class='label'>Prompt Payment Discount Account:</td>
   <td><span id='_br_payment_discount_account_sel'><select id='br_payment_discount_account' autocomplete='off' name='br_payment_discount_account' class='combo2' title=''>
      <optgroup label='Cash'>
         <option value='1700'>1700&nbsp;&nbsp;&nbsp;&nbsp;Adv NAB Cheque Account</option>
         <option value='1701'>1701&nbsp;&nbsp;&nbsp;&nbsp;MSH NAB Cheque Account</option>
         <option value='1702'>1702&nbsp;&nbsp;&nbsp;&nbsp;Westpac Cheque Account</option>
         <option value='1703'>1703&nbsp;&nbsp;&nbsp;&nbsp;Cash on Hand</option>
         <option value='1705'>1705&nbsp;&nbsp;&nbsp;&nbsp;Petty Cash</option>
         <option value='1706'>1706&nbsp;&nbsp;&nbsp;&nbsp;Mike&#039;s Wallet</option>
         <option value='1707'>1707&nbsp;&nbsp;&nbsp;&nbsp;Paypal Account</option>
         <option value='1708'>1708&nbsp;&nbsp;&nbsp;&nbsp;Undeposited Funds</option>
         <optgroup label='Receivables'>
            <option value='1510'>1510&nbsp;&nbsp;&nbsp;&nbsp;Employee Advances</option>
            <option value='1800'>1800&nbsp;&nbsp;&nbsp;&nbsp;Accounts receivable</option>
            <optgroup label='Prepayments'>
               <option value='1900'>1900&nbsp;&nbsp;&nbsp;&nbsp;Deposits Paid</option>
               <option value='1910'>1910&nbsp;&nbsp;&nbsp;&nbsp;Prepaid Insurance</option>
               <option value='1920'>1920&nbsp;&nbsp;&nbsp;&nbsp;Prepaid Workcover</option>
               <optgroup label='Inventory'>
                  <option value='1200'>1200&nbsp;&nbsp;&nbsp;&nbsp;Inventory</option>
                  <option value='1205'>1205&nbsp;&nbsp;&nbsp;&nbsp;Inventory adjustment</option>
                  <optgroup label='Fixed Assets'>
                     <option value='1010'>1010&nbsp;&nbsp;&nbsp;&nbsp;Office Equipment</option>
                     <option value='1011'>1011&nbsp;&nbsp;&nbsp;&nbsp;Office Equipment Accumulated Depreciation</option>
                     <option value='1020'>1020&nbsp;&nbsp;&nbsp;&nbsp;Computer Equipment</option>
                     <option value='1021'>1021&nbsp;&nbsp;&nbsp;&nbsp;Computer Equipment Accumulated Depreciation</option>
                     <option value='1030'>1030&nbsp;&nbsp;&nbsp;&nbsp;Motor Vehicles</option>
                     <option value='1031'>1031&nbsp;&nbsp;&nbsp;&nbsp;Motor Vehicles Accumulated Depreciation</option>
                     <option value='1040'>1040&nbsp;&nbsp;&nbsp;&nbsp;Property, plant, equip.</option>
                     <option value='1041'>1041&nbsp;&nbsp;&nbsp;&nbsp;Plant &amp; Equip Accumulated Depreciation</option>
                     <option value='1050'>1050&nbsp;&nbsp;&nbsp;&nbsp;Goodwill</option>
                     <optgroup label='Credit Cards'>
                        <option value='2100'>2100&nbsp;&nbsp;&nbsp;&nbsp;NAB Mastercard</option>
                        <option value='2200'>2200&nbsp;&nbsp;&nbsp;&nbsp;NAB Visa</option>
                        <optgroup label='Accounts payable'>
                           <option value='20000'>20000&nbsp;&nbsp;&nbsp;&nbsp;Accounts Payable</option>
                           <option value='2300'>2300&nbsp;&nbsp;&nbsp;&nbsp;Sundry Creditors</option>
                           <optgroup label='Tax liabilities'>
                              <option value='2410'>2410&nbsp;&nbsp;&nbsp;&nbsp;PAYG Withheld</option>
                              <option value='2421'>2421&nbsp;&nbsp;&nbsp;&nbsp;GST Collected</option>
                              <option value='2422'>2422&nbsp;&nbsp;&nbsp;&nbsp;Other GST Collected</option>
                              <option value='2430'>2430&nbsp;&nbsp;&nbsp;&nbsp;GST Paid</option>
                              <option value='2431'>2431&nbsp;&nbsp;&nbsp;&nbsp;Other GST Paid</option>
                              <option value='2440'>2440&nbsp;&nbsp;&nbsp;&nbsp;BAS Clearing</option>
                              <optgroup label='Other current liabilities'>
                                 <option value='2510'>2510&nbsp;&nbsp;&nbsp;&nbsp;Accrued Superannuation</option>
                                 <option value='2520'>2520&nbsp;&nbsp;&nbsp;&nbsp;Provision for Annual Leave</option>
                                 <option value='2530'>2530&nbsp;&nbsp;&nbsp;&nbsp;Customer Deposits</option>
                                 <option value='2540'>2540&nbsp;&nbsp;&nbsp;&nbsp;Wages Clearing Account</option>
                                 <optgroup label='Loans'>
                                    <option value='2610'>2610&nbsp;&nbsp;&nbsp;&nbsp;Director&#039;s Loan Account</option>
                                    <option value='2621'>2621&nbsp;&nbsp;&nbsp;&nbsp;Katie&#039;s HSBC Visa</option>
                                    <option value='2622'>2622&nbsp;&nbsp;&nbsp;&nbsp;Katie&#039;s Line of Credit</option>
                                    <option value='2623'>2623&nbsp;&nbsp;&nbsp;&nbsp;Katie&#039;s ANZ Mastercard</option>
                                    <option value='2624'>2624&nbsp;&nbsp;&nbsp;&nbsp;Katie&#039;s CBA Mastercard</option>
                                    <option value='2625'>2625&nbsp;&nbsp;&nbsp;&nbsp;Katie&#039;s Personal Loan</option>
                                    <option value='2700'>2700&nbsp;&nbsp;&nbsp;&nbsp;Van Loan</option>
                                    <option value='2800'>2800&nbsp;&nbsp;&nbsp;&nbsp;Establishment Loan</option>
                                    <option value='2900'>2900&nbsp;&nbsp;&nbsp;&nbsp;Advanced Super Investments Pty Ltd</option>
                                    <optgroup label='Equity'>
                                       <option value='3100'>3100&nbsp;&nbsp;&nbsp;&nbsp;Owners Capital</option>
                                       <option value='3200'>3200&nbsp;&nbsp;&nbsp;&nbsp;Director&#039;s Drawings</option>
                                       <option value='3300'>3300&nbsp;&nbsp;&nbsp;&nbsp;Current Year Profit/Loss</option>
                                       <option value='3400'>3400&nbsp;&nbsp;&nbsp;&nbsp;Retained Earnings</option>
                                       <option value='3500'>3500&nbsp;&nbsp;&nbsp;&nbsp;Historical Balancing</option>
                                       <optgroup label='Sales Income'>
                                          <option value='4010'>4010&nbsp;&nbsp;&nbsp;&nbsp;Retail Sales</option>
                                          <option value='4050'>4050&nbsp;&nbsp;&nbsp;&nbsp;Web Sales</option>
                                          <option value='4110'>4110&nbsp;&nbsp;&nbsp;&nbsp;Export Sales</option>
                                          <option value='4111'>4111&nbsp;&nbsp;&nbsp;&nbsp;Custom Sign Sales</option>
                                          <option value='4112'>4112&nbsp;&nbsp;&nbsp;&nbsp;Installation Income</option>
                                          <option value='4150'>4150&nbsp;&nbsp;&nbsp;&nbsp;Freight Collected</option>
                                          <option value='4151'>4151&nbsp;&nbsp;&nbsp;&nbsp;Equipment Rental</option>
                                          <option value='4152'>4152&nbsp;&nbsp;&nbsp;&nbsp;Line Marking</option>
                                          <option value='4160'>4160&nbsp;&nbsp;&nbsp;&nbsp;Restocking Fees</option>
                                          <option value='4170'>4170&nbsp;&nbsp;&nbsp;&nbsp;Sales - Motor Sprot Heaven</option>
                                          <option value='4180'>4180&nbsp;&nbsp;&nbsp;&nbsp;Miscellaneous Income</option>
                                          <option value='4190'>4190&nbsp;&nbsp;&nbsp;&nbsp;AMEX Fees Collected</option>
                                          <optgroup label='Other income'>
                                             <option value='7010'>7010&nbsp;&nbsp;&nbsp;&nbsp;Interest Income</option>
                                             <option value='7020'>7020&nbsp;&nbsp;&nbsp;&nbsp;Rental Income</option>
                                             <option value='7030'>7030&nbsp;&nbsp;&nbsp;&nbsp;Other Income</option>
                                             <option value='7040'>7040&nbsp;&nbsp;&nbsp;&nbsp;Government Payments</option>
                                             <optgroup label='Cost of Goods Sold'>
                                                <option value='5040'>5040&nbsp;&nbsp;&nbsp;&nbsp;Materials &amp; Supplies</option>
                                                <option value='5050'>5050&nbsp;&nbsp;&nbsp;&nbsp;Cost of Goods Sold</option>
                                                <option value='5060'>5060&nbsp;&nbsp;&nbsp;&nbsp;Linemarking Subcontractors</option>
                                                <option value='5070'>5070&nbsp;&nbsp;&nbsp;&nbsp;Installation Subcontractors</option>
                                                <option value='5080'>5080&nbsp;&nbsp;&nbsp;&nbsp;Shipping</option>
                                                <option value='5090'>5090&nbsp;&nbsp;&nbsp;&nbsp;Sales Discounts</option>
                                                <option value='5110'>5110&nbsp;&nbsp;&nbsp;&nbsp;Assembly Costs</option>
                                                <option selected value='6095'>6095&nbsp;&nbsp;&nbsp;&nbsp;Early payment discounts (sales)</option>
                                                <optgroup label='Expenses'>
                                                   <option value='6050'>6050&nbsp;&nbsp;&nbsp;&nbsp;Exchange variance</option>
                                                   <option value='6060'>6060&nbsp;&nbsp;&nbsp;&nbsp;Accounting Fees</option>
                                                   <option value='6070'>6070&nbsp;&nbsp;&nbsp;&nbsp;Advertising</option>
                                                   <option value='6080'>6080&nbsp;&nbsp;&nbsp;&nbsp;Bad Debts</option>
                                                   <option value='6120'>6120&nbsp;&nbsp;&nbsp;&nbsp;Bank Fees</option>
                                                   <option value='6125'>6125&nbsp;&nbsp;&nbsp;&nbsp;Books and Publications</option>
                                                   <option value='6130'>6130&nbsp;&nbsp;&nbsp;&nbsp;Bookkeeping</option>
                                                   <option value='6131'>6131&nbsp;&nbsp;&nbsp;&nbsp;Cleaning &amp; Waste Removal</option>
                                                   <option value='6132'>6132&nbsp;&nbsp;&nbsp;&nbsp;Computers - Maintenance and Supplies</option>
                                                   <option value='6133'>6133&nbsp;&nbsp;&nbsp;&nbsp;Debt Collection Costs</option>
                                                   <option value='6134'>6134&nbsp;&nbsp;&nbsp;&nbsp;Depreciation</option>
                                                   <option value='6140'>6140&nbsp;&nbsp;&nbsp;&nbsp;Donations</option>
                                                   <option value='6150'>6150&nbsp;&nbsp;&nbsp;&nbsp;Discounts Received</option>
                                                   <option value='6160'>6160&nbsp;&nbsp;&nbsp;&nbsp;Dues and Subscriptions</option>
                                                   <option value='6170'>6170&nbsp;&nbsp;&nbsp;&nbsp;Entertainment/Client Gifts</option>
                                                   <option value='6180'>6180&nbsp;&nbsp;&nbsp;&nbsp;Equipment Hire</option>
                                                   <option value='6190'>6190&nbsp;&nbsp;&nbsp;&nbsp;Filing Fees</option>
                                                   <option value='6200'>6200&nbsp;&nbsp;&nbsp;&nbsp;Fines &amp; Penalties</option>
                                                   <option value='6205'>6205&nbsp;&nbsp;&nbsp;&nbsp;Fines - not tax deductable</option>
                                                   <option value='6207'>6207&nbsp;&nbsp;&nbsp;&nbsp;Freight paid on Purchases</option>
                                                   <option value='6210'>6210&nbsp;&nbsp;&nbsp;&nbsp;Insurance (non-vehicle)</option>
                                                   <option value='6220'>6220&nbsp;&nbsp;&nbsp;&nbsp;Insurance - WorkCover</option>
                                                   <option value='6250'>6250&nbsp;&nbsp;&nbsp;&nbsp;Leases - Equipment</option>
                                                   <option value='6260'>6260&nbsp;&nbsp;&nbsp;&nbsp;Legal Fees</option>
                                                   <option value='6270'>6270&nbsp;&nbsp;&nbsp;&nbsp;Merchant Fees - General</option>
                                                   <option value='6280'>6280&nbsp;&nbsp;&nbsp;&nbsp;Merchant Fees - Ebay</option>
                                                   <option value='6550'>6550&nbsp;&nbsp;&nbsp;&nbsp;Office Supplies/Stationery</option>
                                                   <option value='6560'>6560&nbsp;&nbsp;&nbsp;&nbsp;Permits etc</option>
                                                   <option value='6570'>6570&nbsp;&nbsp;&nbsp;&nbsp;Postage</option>
                                                   <option value='6580'>6580&nbsp;&nbsp;&nbsp;&nbsp;Printing</option>
                                                   <option value='6590'>6590&nbsp;&nbsp;&nbsp;&nbsp;Professional Fees</option>
                                                   <option value='6600'>6600&nbsp;&nbsp;&nbsp;&nbsp;Recruitment</option>
                                                   <option value='6610'>6610&nbsp;&nbsp;&nbsp;&nbsp;Samples/Product Development</option>
                                                   <option value='6620'>6620&nbsp;&nbsp;&nbsp;&nbsp;Shop Set-Up</option>
                                                   <option value='6630'>6630&nbsp;&nbsp;&nbsp;&nbsp;Small Capital Items</option>
                                                   <option value='6640'>6640&nbsp;&nbsp;&nbsp;&nbsp;Staff Amenities/Medical</option>
                                                   <option value='6650'>6650&nbsp;&nbsp;&nbsp;&nbsp;Tool Replacements</option>
                                                   <option value='6660'>6660&nbsp;&nbsp;&nbsp;&nbsp;Website/Online Shop Expenses</option>
                                                   <optgroup label='Occupancy Costs'>
                                                      <option value='6400'>6400&nbsp;&nbsp;&nbsp;&nbsp;Gas</option>
                                                      <option value='6401'>6401&nbsp;&nbsp;&nbsp;&nbsp;Cleaning &amp; Waste Removal</option>
                                                      <option value='6405'>6405&nbsp;&nbsp;&nbsp;&nbsp;Electricity</option>
                                                      <option value='6406'>6406&nbsp;&nbsp;&nbsp;&nbsp;Home Office Rent</option>
                                                      <option value='6407'>6407&nbsp;&nbsp;&nbsp;&nbsp;Rates &amp; Taxes</option>
                                                      <option value='6408'>6408&nbsp;&nbsp;&nbsp;&nbsp;Rent</option>
                                                      <option value='6409'>6409&nbsp;&nbsp;&nbsp;&nbsp;Repairs &amp; Maintenance</option>
                                                      <option value='6410'>6410&nbsp;&nbsp;&nbsp;&nbsp;Water</option>
                                                      <optgroup label='Motor Vehicle'>
                                                         <option value='6710'>6710&nbsp;&nbsp;&nbsp;&nbsp;Gas/Petrol/Oil</option>
                                                         <option value='6720'>6720&nbsp;&nbsp;&nbsp;&nbsp;Registration &amp; Insurance</option>
                                                         <option value='6730'>6730&nbsp;&nbsp;&nbsp;&nbsp;Vehicle Maintenance/Servicing</option>
                                                         <optgroup label='Telephone/Internet'>
                                                            <option value='6340'>6340&nbsp;&nbsp;&nbsp;&nbsp;Fax</option>
                                                            <option value='6341'>6341&nbsp;&nbsp;&nbsp;&nbsp;Telephone: Mobile</option>
                                                            <option value='6342'>6342&nbsp;&nbsp;&nbsp;&nbsp;Telephone: Office</option>
                                                            <option value='6343'>6343&nbsp;&nbsp;&nbsp;&nbsp;Telephone: VOIP</option>
                                                            <option value='6355'>6355&nbsp;&nbsp;&nbsp;&nbsp;Internet</option>
                                                            <optgroup label='Rental Flat Costs'>
                                                               <option value='6810'>6810&nbsp;&nbsp;&nbsp;&nbsp;Rental Agent Commission</option>
                                                               <option value='6820'>6820&nbsp;&nbsp;&nbsp;&nbsp;Repairs &amp; Maint to Flat</option>
                                                               <optgroup label='Travel Expenses'>
                                                                  <option value='6830'>6830&nbsp;&nbsp;&nbsp;&nbsp;Parking</option>
                                                                  <option value='6840'>6840&nbsp;&nbsp;&nbsp;&nbsp;Tolls</option>
                                                                  <option value='6850'>6850&nbsp;&nbsp;&nbsp;&nbsp;Travel Allowances</option>
                                                                  <optgroup label='Other Expenses'>
                                                                     <option value='8100'>8100&nbsp;&nbsp;&nbsp;&nbsp;Income Tax Expense</option>
                                                                     <option value='8210'>8210&nbsp;&nbsp;&nbsp;&nbsp;Interest - Bank</option>
                                                                     <option value='8220'>8220&nbsp;&nbsp;&nbsp;&nbsp;Interest - Credit Card</option>
                                                                     <option value='8230'>8230&nbsp;&nbsp;&nbsp;&nbsp;Interest - ATO</option>
                                                                     <option value='8300'>8300&nbsp;&nbsp;&nbsp;&nbsp;Suspense</option>
                                                                     <optgroup label='Employee Expenses'>
                                                                        <option value='6910'>6910&nbsp;&nbsp;&nbsp;&nbsp;Annual Leave</option>
                                                                        <option value='6920'>6920&nbsp;&nbsp;&nbsp;&nbsp;Protective Clothing/Uniforms</option>
                                                                        <option value='6930'>6930&nbsp;&nbsp;&nbsp;&nbsp;Salaries &amp; Wages</option>
                                                                        <option value='6940'>6940&nbsp;&nbsp;&nbsp;&nbsp;Superannuation</option>
                                                                        <option value='6950'>6950&nbsp;&nbsp;&nbsp;&nbsp;Training</option>
                                                                        <option value='6960'>6960&nbsp;&nbsp;&nbsp;&nbsp;WorkCover</option>
   </select>
</span>
   </td>
</tr>
<tr class='tableheader'>
   <td colspan=2 class='tableheader'>Addresses</td>
</tr>
<tr>
   <td class='label'>Address:</td>
   <td><textarea name='br_br_address' cols='35' rows='2'></textarea></td>
</tr>
<tr>
   <td class='label'>Branch Mailing Address:</td>
   <td><textarea name='br_br_post_address' cols='35' rows='4'></textarea></td>
</tr>
</table>
</td>
</tr>
</table>
</center>
<br></div>
<div id="tabs0-5">
   <div id='transactions'></div>
</div>
<input type="hidden" name="popup" value="">
<input type="hidden" name="_focus" value="br_contact_name">
</form>
<div id='contactLog' title='New contact log entry' class='ui-widget-overlay' style='display:none;'><p id='New log entry:' class='validateTips'>
   <center>
      <table cellpadding=2 cellspacing=0>
         <tr>
            <td class='label'>Date:</td>
            <td>2011-07-30 05:27:01</td>
         </tr>
         <input type="hidden" name="type" value="C">
         <tr>
            <td class='label'>Contact:</td>
            <td><input type="text" name="contact_name" size="40" maxlength="40" value=""></td>
         </tr>
         <tr>
            <td class='label'>Entry:</td>
            <td><textarea name='message' cols='100' rows='10'></textarea></td>
         </tr>
      </table>
   </center>
   </p></div>
<div style='text-align:center;width:50%;display:block;margin:0 auto;'>
   <button id='btnCustomer' name='submit' type='submit' class=' ui-helper-hidden' style='margin:10px;'>New Customer</button>
   <button id='btnCancel' name='cancel' type='submit' class='ui-helper-hidden' style='margin:10px;'>Cancel</button>
</div>
</div>
<div id='shortcuts' style='width:50%;display:block;margin:0 auto;'>
   <div id='tabs1'>
      <ul>
         <li><a title='Create Order' href="/sales/sales_order_entry.php?NewOrder=Yes&customer_id="><span>Create Order</span></a></li>
         <li><a title='Create Quote' href="/sales/sales_order_entry.php?NewQuotation=Yes&customer_id="><span>Create Quote</span></a></li>
      </ul>
      <div id="tabs1-1"></div>
      <div id="tabs1-2"></div>
   </div>
</div>
</div>
</div>
</div>
<script src='/js/js2/advanced.js,shortmenu.js,jquery.calculator.pack.js,calc.js,jquery-tmpl.min.js'></script>
<script src='includes/js/customers.js'></script>
<script>
   var user = {
      theme: '../themes/default/',
      loadtxt: 'Requesting data...',
      date: '30/07/2011',
      datesys: 0,
      datefmt: 1,
      datesep: '/',
      ts: ',',
      ds: '.',
      pdec : 2}
      ;
   _focus = 'br_contact_name';
   _validate = [  ];
   var editors = [  ];
   ;
   Adv.extend({
      postcode1: (function() {
         var postcode = $('#acc_postcode'),state = $('#acc_state'), city = $('#acc_city');
         return {
            fetch: function(data) {
               data = data.value.split('|');
               postcode.val(data[1]).trigger('change');
               state.val(data[2]).trigger('change');
               city.val(data[0]).trigger('change');
               return false;
            }
         }
      }())
   });
   Adv.extend({
      postcode0: (function() {
         var postcode = $('#br_postcode'),state = $('#br_state'), city = $('#br_city');
         return {
            fetch: function(data) {
               data = data.value.split('|');
               postcode.val(data[1]).trigger('change');
               state.val(data[2]).trigger('change');
               city.val(data[0]).trigger('change');
               return false;
            }
         }
      }())
   });
   $(function() {
      $('#tabs1').tabs();
      ;

      $('#tabs0').tabs();
   });</script>
</body>
</html>
