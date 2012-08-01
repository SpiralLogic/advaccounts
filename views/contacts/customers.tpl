{{#if !$frame}}
<div id='companysearch' class='center bold font13 pad20'>
  <label for='customer'>Search Customer:&nbsp;<input name='customer' id='customer' type='text' autofocus></label>
</div>
{{/if}}
<div id="companyIDs">
  <table class="marginauto width80 bold">
    <tr>
      <td><label for="name">Customer name:</label><input id="name" name="name" class="med"></td>
      <td><label for="id">Customer ID:</label></td>
      <td><input id="id" name="id" class="small" maxlength="7"></td>
    </tr>
  </table>
</div>
<div>{{$form->start()}}
  {#$menu->startTab('Details', 'Customer Details', '#', 'text-align:center')#}

  <div class='formbox width35'>
  <div class='tablehead'>
    Shipping Details
  </div>
  <div id="branchSelect" class="center">{{$branchlist}}
    <button id="addBranch" class="invis" name="addBranch">Add new address</button>
  </div>
  {{$form['branch.contact_name']}}
  {{$form['branch.phone']}}
  {{$form['branch.phone2']}}
  {{$form['branch.fax']}}
  {{$form['branch.email']}}
  {{$form['branch.br_address']}}
  {{$branch_postcode->render()}}
  </div>
  <div class='formbox width35'>
    <div class='tablehead'>
      Accounts Details
    </div>
    <div class='center'>
      <button id="useShipAddress" name="useShipAddress" class="button">Use shipping details</button>
    </div>
    {{$form['accounts.contact_name']}}
    {{$form['accounts.phone']}}
    {{$form['accounts.phone2']}}
    {{$form['accounts.fax']}}
    {{$form['accounts.email']}}
    {{$form['accounts.br_address']}}
    {{$accounts_postcode->render()}}
  </div>
  {#$menu->endTab()->startTab('Accounts', 'Accounts')#}

  <div class='formbox width35'>
  <div class='tablehead'>
    Accounts Details
    {{$form['accounts_id']}}
  </div>
  {{$form['discount']}}
  {{$form['payment_discount']}}
  {{$form['credit_limit']}}
  {{$form['tax_id']}}
  {{$form['sales_type']}}
  {{$form['inactive']}}
  {{$form['curr_code']}}
  {{$form['payment_terms']}}
  {{$form['credit_status']}}

  </div>
  <div class='formbox width35'>
    <div class='tablehead'>
      Contact Log:
    </div>
    <div class='center'>
      <button id="addLog" name="addLog" class="button">Add log entry</button>
    </div>
    {{$form['messageLog']}}
  </div>
  {#$menu->endTab()->startTab('Customer Contacts', 'Customer Contacts')#}

  <div class='center'>
  <div id="Contacts" style='min-height:200px'>
    <script id="contact_tmpl" type='text/x-jquery-tmpl'>
      <table id="contact-${_k}" style="display:inline-block">
        <tr>
          <td class="tablehead" colspan="2">${name}</td>
        </tr>
        <tr>
          <td class='label'><label for='contact[name-${_k}]'>Name:</label></td>
          <td><input type="text" name="contact[name-${_k}]" id="contact[name-${_k}]" size='35' maxlength="40" value="${name}"></td>
        </tr>
        <tr>
          <td class='label'><label for='contact[phone1-${_k}]'>Phone:</label></td>
          <td><input type="text" name="contact[phone1-${_k}]" id="contact[phone1-${_k}]" size='35' maxlength="40" value="${phone1}"></td>
        </tr>
        <tr>
          <td class='label'><label for='contact[phone2-${_k}]'>Phone2:</label></td>
          <td><input type="text" name="contact[phone2-${_k}]" id="contact[phone2-${_k}]" size='35' maxlength="40" value="${phone2}"></td>
        </tr>
        <tr>
          <td class='label'><label for='contact[email-${_k}]'>Email:</label></td>
          <td><input type="text" name="contact[email-${_k}]" id="contact[email-${_k}]" size='35' maxlength="40" value="${email}"></td>
        </tr>
        <tr>
          <td class='label'><label for='contact[department-${_k}]'>Dept:</label></td>
          <td><input type="text" name="contact[department-${_k}]" id="contact[department-${_k}]" size='35' maxlength="40" value="${department}"></td>
        </tr>
      </table>
    </script>
  </div>
  </div>
  {#$menu->endTab()->startTab('Extra Shipping Info', 'Extra Shipping Info')#}

  <div class='formbox  width35'>
  <div class='tablehead'>
    Accounts Details
    {{$form['branch_id']}}
  </div>
  {{$form['branch.salesman']}}
  {{$form['branch.area']}}
  {{$form['branch.group_no']}}
  {{$form['branch.default_location']}}
  {{$form['branch.default_ship_via']}}
  {{$form['branch.tax_group_id']}}
  {{$form['branch.disable_trans']}}
  {{$form['webid']}}

  </div>
  <div class='formbox width35'>
    <div class='tablehead'>
      GL Accounts
    </div>
    {{$form['branch.sales_account']}}
    {{$form['branch.sales_discount_account']}}
    {{$form['branch.receivables_account']}}
    {{$form['branch.payment_discount_account']}}
    {{$form['branch_notes']}}
  </div>
  {#$menu->endTab()->startTab('Invoices', 'Invoices')#}

  <div id='invoiceFrame' data-src='/sales/inquiry/customer_allocation_inquiry.php?debtor_id={{$debtor_id}}'></div>
  {{$form['frame']}}
  {{$form['_focus']}}
  {{$form->end()}}
  {#$menu->endTab()->render()#}
</div>
<div class='center clearleft'>
  <button id="btnConfirm" name="submit" type="submit" class="button ui-helper-hidden">New Customer</button>
  <button id="btnCancel" name="new" type="submit" class="button">New</button>
  {{$shortcuts->render()}}
</div>