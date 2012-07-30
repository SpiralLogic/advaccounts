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
      <td style='border-left:1px solid #cccccc; '>
        <table class='tablestyle_inner '>
          <tr class='tablehead'>
            <td colspan=2 class='tablehead'>Accounts Details</td>
          </tr>
          <tr>
            <td class="center" colspan="2">
              <button id="useShipAddress" name="useShipAddress" class="button">Use shipping details</button>
            <td></td>
          </tr>
          <tr>
            <td class='label'><label for='accounts[contact_name]'>Accounts Contact:</label></td>
            <td><input type="text" name="accounts[contact_name]" id="accounts[contact_name]" size='35' maxlength="40" value=""></td>
          </tr>
          <tr>
            <td class='label'><label for='accounts[phone]'>Phone Number:</label></td>
            <td><input type="text" name="accounts[phone]" id="accounts[phone]" size='35' maxlength="30" value=""></td>
          </tr>
          <tr>
            <td class='label'><label for='accounts[phone2]'>Secondary Phone Number:</label></td>
            <td><input type="text" name="accounts[phone2]" id="accounts[phone2]" size='35' maxlength="30" value=""></td>
          </tr>
          <tr>
            <td class='label'><label for='accounts[fax]'>Fax Number:</label></td>
            <td><input type="text" name="accounts[fax]" id="accounts[fax]" size='35' maxlength="30" value=""></td>
          </tr>
          <tr>
            <td class='label'><label for='accounts[email]'>E-mail:</label></td>
            <td><input type="text" name="accounts[email]" id="accounts[email]" size='35' maxlength="55" value=""></td>
          </tr>
          <tr>
            <td class='label'><label for='accounts[br_address]'>Street:</label></td>
            <td><textarea id='accounts[br_address]' name='accounts[br_address]' cols='37' rows='2'></textarea></td>
          </tr>
          {{$accounts_postcode->render()}}        </table>
      </td>
    </tr>
  </table>
</div>
<br>
