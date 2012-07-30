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
{#$menu->startTab('Details', 'Customer Details', '#', 'text-align:center')#}
<div class='center'>
  <table class='tablestyle2'>
    <tr class='top'>
      <td style='width:50%'>
        <div class='formbox'>
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

        </div>
      </td>
      <td style='width:50%'>
        <div class='formbox'>
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
          {{$form['accounts.br_address']}}</div>

      </td>
    </tr>
  </table>
</div>
{#$menu->endTab()->startTab('Accounts', 'Accounts')#}
{{$form['accounts_id']}}
<div class='formbox   floatleft'>
    <div class='tablehead'>
      Accounts Details
    </div>
    {{$form['discount']}}
    {{$form['payment_discount']}}
    {{$form['credit_limit']}}
    {{$form['tax_id']}}
</div><div class='formbox   '>
    <div class='tablehead'>
      Sales Type/Price List:
    </div>
    {{$form['payment_discount']}}
    {{$form['credit_limit']}}
    {{$form['sales_type']}}
    {{$form['combodiv']}}
    {{$form['curr_code']}}
    {{$form['payment_terms']}}
    {{$form['credit_status']}}
    {{$form['messageLog']}}
    <button id="addLog" name="addLog" class="button">Add log entry</button>
  </div>
{#$menu->endTab()->startTab('Customer Contacts', 'Customer Contacts')#}
<div class='formbox center width50 '>
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
{#$menu->endTab()->startTab('Extra Shipping Info', 'Extra Shipping Info')#}
  <div class='center'>
    <table class='tablestyle2'>
      <tr class='top'>
        <td>
          <input type='hidden' id='branch_id' name='branch_id' value='0'>
          <table class='tablestyle_inner '>
            <tr class='tablehead'>
              <td colspan=2 class='tablehead'>Sales</td>
            </tr>
            <tr>
              <td class='label'>Sales Person:</td>
              <td>
                <div id='_branch[salesman]_sel' class='combodiv'><select id='branch[salesman]' name='branch[salesman]' class='combo' title=''>
                  <option value='14'>Casey Raineri</option>
                  <option value='13'>Daniel Dobson</option>
                  <option value='3'>Mike Smith</option>
                  <option value='1'>Phil Whittle</option>
                  <option value='2'>Sam Jennings</option>
                  <option value='15'>Sherry Tang</option>
                  <option selected value='5'>Sol Jennings</option>
                  <option value='8'>Syed Mehdi</option>
                </select>
                </div>
              </td>
            </tr>
            <tr>
              <td class='label'>Sales Area:</td>
              <td>
                <div id='_branch[area]_sel' class='combodiv'><select id='branch[area]' name='branch[area]' class='combo' title=''>
                  <option selected value='1'>Australia</option>
                  <option value='2'>UK</option>
                </select>
                </div>
              </td>
            </tr>
            <tr>
              <td class='label'>Sales Group:</td>
              <td>
                <div id='_branch[group_no]_sel' class='combodiv'><select id='branch[group_no]' name='branch[group_no]' class='combo' title=''>
                  <option value='3'>Large</option>
                  <option value='2'>Medium</option>
                  <option selected value='1'>Small</option>
                </select>
                </div>
              </td>
            </tr>
            <tr>
              <td class='label'>Default Inventory Location:</td>
              <td>
                <div id='_branch[default_location]_sel' class='combodiv'><select id='branch[default_location]' name='branch[default_location]' class='combo' title=''>
                  <option value='MEL'>Camberwell</option>
                  <option value='DRP'>Drop Ship</option>
                  <option selected value='KEYS'>Keysborough</option>
                  <option value='NFY'>NOT FAXED YET</option>
                </select>
                </div>
              </td>
            </tr>
            <tr>
              <td class='label'>Default Shipping Company:</td>
              <td>
                <div id='_branch[default_ship_via]_sel' class='combodiv'><select id='branch[default_ship_via]' name='branch[default_ship_via]' class='combo' title=''>
                  <option value='12'>Consulting</option>
                  <option value='11'>Delivery</option>
                  <option selected class='inactive' value='1'>Hunter Express</option>
                  <option value='9'>Installation</option>
                  <option value='8'>Linemarking</option>
                  <option value='2'>Pickup</option>
                </select>
                </div>
              </td>
            </tr>
            <tr>
              <td class='label'>Tax Group:</td>
              <td>
                <div id='_branch[tax_group_id]_sel' class='combodiv'><select id='branch[tax_group_id]' name='branch[tax_group_id]' class='combo' title=''>
                  <option selected value='1'>GST</option>
                  <option value='2'>Tax free FRE</option>
                  <option value='4'>Tax free EXP</option>
                  <option value='5'>Taxed CAP</option>
                  <option value='6'>Tax free N-T</option>
                  <option value='7'>Taxed INP</option>
                </select>
                </div>
              </td>
            </tr>
            <tr>
              <td class='label'>Disable this Branch:</td>
              <td>
                <div id='_branch[disable_trans]_sel' class='combodiv'><select id='branch[disable_trans]' name='branch[disable_trans]' class='combo' title=''>
                  <option selected value='0'>No</option>
                  <option value='1'>Yes</option>
                </select>
                </div>
              </td>
            </tr>
            <tr>
              <td class="label">Website ID:</td>
              <td><input id="webid" disabled name="webid" maxlength="7"></td>
            </tr>
          </table>
        </td>
        <td style='border-left:1px solid #cccccc; '>
          <table class='tablestyle_inner '>
            <tr class='tablehead'>
              <td colspan=2 class='tablehead'>GL Accounts</td>
            </tr>
            <tr>
              <td class='label'>Sales Account:</td>
              <td>
                <div id='_branch[sales_account]_sel' class='combodiv'><select id='branch[sales_account]' name='branch[sales_account]' class='combo2' title=''>
                  <option selected value=''>Use Item Sales Accounts</option>
                </select>
                </div>
              </td>
            </tr>
            <tr class='tablehead'>
              <td colspan=2 class='tablehead'>Notes</td>
            </tr>
            <tr>
              <td class='label'><label for='branch[notes]'>General Notes:</label></td>
              <td><textarea id='branch[notes]' name='branch[notes]' cols='37' rows='4'></textarea></td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </div>
  <br></div>
<div id="tabs0-4" class="ui-tabs-hide">
  <div id='invoiceFrame' data-src='/sales/inquiry/customer_allocation_inquiry.php?debtor_id=0'></div>
</div>
<input type='hidden' id='frame' name='frame' value=''></div><input type='hidden' name='_focus' value='sales_type'></form>
<div id="contactLog" title="New contact log entry" class="ui-widget-overlay" style="display:none;"><input type='hidden' id='type' name='type' value='1'>

  <div class='center'>
    <table>
      <tr>
        <td class='label'>Date:</td>
        <td>2012-07-31 02:29:42</td>
