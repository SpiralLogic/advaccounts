{{#if !$frame}}
<div id='companysearch' class='center bold font13 pad20'>
  <label for='supplier'>Search Supplier:&nbsp;<input name='supplier' id='supplier' type='text' autofocus></label>
</div>
{{/if}}
<div>{{$form->start()}}
  {#$menu->startTab('Details', 'Supplier Details', '#', 'text-align:center')#}
  <div id="companyIDs">
    <label for="name">Supplier name:</label><input id="name" name="name" class="big">
    <label for="id">Supplier ID:</label><input id="id" name="id" class="small" maxlength="7">
  </div>
  <div class='formbox '>
    <div class='tablehead'>
      Shipping Details
    </div>
  {{$form.contact}}
  {{$form.phone}}
  {{$form.fax}}
  {{$form.email}}
  {{$form.address}}
  {{#$postcode}}
  {{.}}
    {{/$postcode}}

  </div>
  <div class='formbox '>
    <div class='tablehead'>
      Accounts Details
    </div>
    <div class='center'>
      <button id="useShipAddress" name="useShipAddress" class="button">Use shipping details</button>
    </div>
  {{$form.supp_phone}}
  {{$form.supp_address}}
  {{#$supp_postcode}}
  {{.}}
  {{/$supp_postcode}}
  </div>
  {#$menu->endTab()->startTab('Accounts', 'Accounts')#}
  <div class='formbox '>
    <div class='tablehead'>
      Accounts Details
    </div>
  {{$form.payment_discount}}
  {{$form.credit_limit}}
  {{$form.tax_id}}
  {{$form.tax_group_id}}
  {{$form.inactive}}
  {{$form.curr_code}}
  {{$form.payment_terms}}
  {{$form.payable_account}}
  {{$form.payment_discount_account}}
  </div>
  <div class='formbox width35'>
    <div class='tablehead'>
      Contact Log:
    </div>
    <div class='center'>
      <button id="addLog" name="addLog" class="button">Add log entry</button>
      <br> {{$form.messageLog}}
    </div>
  </div>
  {#$menu->endTab()->startTab('Supplier Contacts', 'Supplier Contacts')#}
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
  {#$menu->endTab()->startTab('Invoices', 'Invoices')#}
  <div id='invoiceFrame' data-src='/purchases/inquiry/supplier_allocation_inquiry.php?creditor_id={{$creditor_id}}'></div>
{{$form.frame}}
  {#$menu->endTab()->render()#}
{{$form->end()}}
</div>
<div class='center clearleft pad20'>
  <button id="btnNew" name="new" type="submit" class="btn btn-primary">New</button>
  <button id="btnCancel" name="cancel" type="submit" class="btn btn-danger ui-helper-hidden">Cancel</button>
  <button id="btnConfirm" name="save" type="submit" class="btn btn-success ui-helper-hidden">New Customer</button>
</div>  {{$shortcuts->render()}}
