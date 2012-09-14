{{#if !$frame }}
<div id='companysearch' class='center bold font13 pad20'>
  <label for='customer'>Search Customer:&nbsp;<input name='customer' placeholder='Customer' id='customer' type='text' autofocus></label>
</div>
{{/if}}
<div>
  {{$form->start()}}
  {#$menu->startTab('Details', 'Customer Details', '#', 'text-align:center')#}
  <div id="companyIDs" class='pad5'>
    <label for="name">Customer name:</label><input id="name" placeholder='Customer' name="name" class="big">
    <label for="id">Customer ID:</label><input id="id" readonly class="small" value='{{$debtor_id}}' name='id'>
  </div>
  <div class='formbox'>
    <div class='tablehead'>
      Shipping Details
    </div>
    <div id="branchSelect" class="center">{{$branchlist}}
      <button id="addBranch" class="invis" name="addBranch">Add new address</button>
    </div>
    {{#$form.shipping_details}}
    {{.}}
    {{/$form.shipping_details}}
    {{#$branch_postcode}}
    {{.}}
    {{/$branch_postcode}}
  </div>
  <div class='formbox'>
    <div class='tablehead'>
      Accounts Details
    </div>
    <div class='center'>
      <button id="useShipAddress" name="useShipAddress" class="button">Use shipping details</button>
    </div>
    {{#$form.accounts_details}}
    {{.}}
    {{/$form.accounts_details}}
    {{#$accounts_postcode}}
    {{.}}
    {{/$accounts_postcode}}
  </div>
  {#$menu->endTab()->startTab('Accounts', 'Accounts')#}
  <div class='formbox'>
    <div class='tablehead'>
      Accounts Details
      {{$form.accounts_id}}
    </div>
    {{#$form.accounts}}
    {{.}}
    {{/$form.accounts}}
  </div>
  <div class='formbox width35'>
    <div class='tablehead'>
      Contact Log:
    </div>
    <div class='center'>
      <button id="addLog" name="addLog" class="button">Add log entry</button>
    </div>
    {{$form.messageLog}}
  </div>
  {#$menu->endTab()->startTab('Customer Contacts', 'Customer Contacts')#}
  {{>contacts/contact}}
  {#$menu->endTab()->startTab('Extra Shipping Info', 'Extra Info')#}
  <div class='formbox'>
    <div class='tablehead'>
      Accounts Details
      {{$form.branch_id}}
    </div>
    {{$form.branch-salesman}}
    {{$form.branch-area}}
    {{$form.branch-group_no}}
    {{$form.branch-default_location}}
    {{$form.branch-default_ship_via}}
    {{$form.branch-tax_group_id}}
    {{$form.branch-disable_trans}}
    {{$form.webid}}
  </div>
  <div class='formbox'>
    <div class='tablehead'>
      GL Accounts
    </div>
    {{$form.branch-sales_account}}
    {{$form.branch-sales_discount_account}}
    {{$form.branch-receivables_account}}
    {{$form.branch-payment_discount_account}}
    {{$form.branch_notes}}
  </div>
  {#$menu->endTab()->startTab('Invoices', 'Invoices')#}
  {{$form.frame}}
  <div id='invoiceFrame' data-src='/sales/inquiry/customer_allocation_inquiry.php?debtor_id={{$debtor_id}}'></div>
  {{$form._focus}}
  {#$menu->endTab()->render()#}
  {{$form->end()}}
</div>
<div class='center clearleft pad20'>
  <button id="btnNew" name="new" type="submit" class="btn btn-primary">New</button>
  <button id="btnCancel" name="cancel" type="submit" class="btn btn-danger ui-helper-hidden"><i class="icon-cross"></i> Cancel</button>
  <button id="btnConfirm" name="save" type="submit" class="btn btn-success ui-helper-hidden"><i class="icon-ok"></i> Save</button>
</div>
<div id="shortcuts" class="center">{{#$shortcuts}}
  <button class="btn" data-url="{{$.data}}">{{$.caption}}</button>
  {{/$shortcuts}}</div>
{{$contact_form->start()}}
<div id="contactLog" class='ui-helper-hidden center'>
  <div class="formbox marginauto ">
    {{$contact_form.contact_name}}<br>
    {{$contact_form.message}}
    {{$contact_form.type}}
  </div>
</div>
{{$contact_form->end()}}
