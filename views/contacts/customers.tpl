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
<br>
