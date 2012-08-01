{{#if !$frame}}
<div id='companysearch' class='center bold font13 pad20'>
  <label for='customer'>Search Customer:&nbsp;<input name='customer' id='customer' type='text' autofocus></label>
</div>
{{/if}}
<div>{{$form->start()}}
  {#$menu->startTab('Details', 'Customer Details', '#', 'text-align:center')#}
  <div id="companyIDs">
    <label for="name">Customer name:</label><input id="name" name="name" class="big">
    <label for="id">Customer ID:</label><input id="id" name="id" class="small" maxlength="7">
  </div>
  <div class='formbox width35'>
    <div class='tablehead'>
      Shipping Details
    </div>
  <div class='formbox width35'>
    <div class='tablehead'>
      Accounts Details
    </div>
    <div class='center'>
      <button id="useShipAddress" name="useShipAddress" class="button">Use shipping details</button>
    </div>
  </div>
  {#$menu->endTab()->startTab('Accounts', 'Accounts')#}
  <div class='formbox width35'>
    <div class='tablehead'>
      Accounts Details
    </div>
  </div>
  <div class='formbox width35'>
    <div class='tablehead'>
      Contact Log:
    </div>
    <div class='center'>
      <button id="addLog" name="addLog" class="button">Add log entry</button>
    </div>
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
    </div>
  </div>
  <div class='formbox width35'>
    <div class='tablehead'>
      GL Accounts
    </div>
  </div>
  {#$menu->endTab()->startTab('Invoices', 'Invoices')#}
  <div id='invoiceFrame' data-src='/sales/inquiry/customer_allocation_inquiry.php?debtor_id={{$creditor_id}}'></div>
  {{$form->end()}}
  {#$menu->endTab()->render()#}
</div>
<div class='center clearleft'>
  <button id="btnConfirm" name="submit" type="submit" class="button ui-helper-hidden">New Customer</button>
  <button id="btnCancel" name="new" type="submit" class="button">New</button>
  {{$shortcuts->render()}}
</div>
