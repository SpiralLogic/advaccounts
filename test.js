Adv.Status.show();
$emailBox = $('<div id="emailBox" title="Select Email Address:"></div>)');
var user = {theme: '/themes/default/', loadtxt: 'Requesting data...', date: '15/09/2012', datefmt: 1, datesep: '1', ts: ',', ds: '.', pdec: 2};
$(function () {
  Adv.Events.onload(function () {
    Adv.Forms.autocomplete('customer', '/contacts/customers', Company.fetch);
    Adv.Forms.autocomplete('branch[city]', '/contacts/postcode.php?city=1', Adv.postcode.fetch);
    Adv.Forms.autocomplete('branch[postcode]', '/contacts/postcode.php?postcode=1', Adv.postcode.fetch);
    Adv.Forms.autocomplete('accounts[city]', '/contacts/postcode.php?city=1', Adv.postcode.fetch);
    Adv.Forms.autocomplete('accounts[postcode]', '/contacts/postcode.php?postcode=1', Adv.postcode.fetch);
    $emailBox.dialog({"autoOpen": false, "modal": true, "width": 500, "resizable": true, "closeOnEscape": true, "height": 350, "resizeable": false}).dialog("option", "buttons", {"Close": function () { $(this).dialog("close");}, });
    $emailBox.parent().find("button").each(function () {
      var $this = $(this);
      $this.text($this.attr("text"));
    });
  }, function () {
    Adv.o.autocomplete['customer'].autocomplete('destroy');
    Adv.o.autocomplete['branch[city]'].autocomplete('destroy');
    Adv.o.autocomplete['branch[postcode]'].autocomplete('destroy');
    Adv.o.autocomplete['accounts[city]'].autocomplete('destroy');
    Adv.o.autocomplete['accounts[postcode]'].autocomplete('destroy');
  });
  Adv.hoverWindow.init(900, 500);
  Adv.tabmenu.init('0', false, false, 0);
  Adv.postcode.add('1', 'branch[city]', 'branch[state]', 'branch[postcode]');
  Adv.postcode.add('3', 'accounts[city]', 'accounts[state]', 'accounts[postcode]');
  Adv.o.wrapper.delegate('.email-button', 'click', function (e) {
    var emailID = $(this).data('emailid');
    $.post('/contacts/emails.php', {type: 1, id: emailID}, function (data) {
      $emailBox.html(data).dialog('open');
    }, 'html');
    return false;
  });
  Company.setValues({"company": {"id": 0, "name": null, "sales_type": "1", "debtor_ref": false, "credit_status": "1", "payment_discount": "0", "defaultBranch": "0", "defaultContact": 0, "branches":
    [
      {"post_address": null, "branch_id": "0", "br_name": "New Address", "br_address": null, "city": null, "state": null, "postcode": null, "area": "1", "br_post_address": null, "debtor_id": 0, "branch_ref": "New Address", "contact_name": null, "default_location": "KEYS", "default_ship_via": "1", "disable_trans": "0", "phone": null, "phone2": null, "fax": null, "website": null, "email": null, "inactive": "0", "notes": null, "group_no": "1", "payment_discount_account": "6095", "receivables_account": "1800", "sales_account": null, "sales_discount_account": "5090", "salesman": "5", "tax_group_id": "1", "id": "0", "name": "New Address", "address": null}
    ], "contacts": [
    {"id": 0, "contact_id": 0, "parent_id": "0", "parent_type": 1, "name": "New Contact", "phone1": "", "phone2": "", "email": "", "department": ""}
  ], "accounts": {"accounts_id": "0", "br_name": "Accounts Department", "branch_ref": "accounts", "post_address": null, "branch_id": "0", "br_address": null, "city": null, "state": null, "postcode": null, "area": "1", "br_post_address": null, "debtor_id": 0, "contact_name": null, "default_location": "KEYS", "default_ship_via": "1", "disable_trans": "0", "phone": null, "phone2": null, "fax": null, "website": null, "email": null, "inactive": "0", "notes": null, "group_no": "1", "payment_discount_account": "6095", "receivables_account": "1800", "sales_account": null, "sales_discount_account": "5090", "salesman": "5", "tax_group_id": "1", "id": "0", "name": "Accounts Department", "address": null}, "transactions": null, "webid": null, "email": null, "inactive": "0", "debtor_id": 0, "notes": null, "discount": "0", "address": null, "city": null, "state": null, "postcode": null, "post_address": null, "tax_id": null, "contact_name": null, "credit_limit": "0.00", "dimension_id": "0", "dimension2_id": "0", "payment_terms": "1", "curr_code": "AUD", "emailAddresses":
    []}});
  Adv.Forms.setFocus("customer");
});
