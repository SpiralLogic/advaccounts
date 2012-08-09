<pre><table id='table' class='grid center pad2'>
  <tr>
    <th>
      <button id='all'>All</button>
    </th>
    <th>Name</th>
    <th>Phone</th>
    <th>Balance</th>
    <th>Due</th>
    <th>Overdue1</th>
    <th>Overdue2</th>
  </tr>
  {{#foreach $rows as $row}}
  <tr>
    <td class='aligncenter'><input class='email' type='checkbox' value='{{$row.debtor}}' checked/></td>
    <td class='left'><span class='bold'>{{$row.name}}</span>({{$row.email}})
    </td>
    <td>{{$row.phone}}</td>
    <td>{{$row.Balance}}</td>
    <td
      {{$row.Due?}}class="currentfg"{{/$row.Due?}}>{{$row.Due}}</td>
    <td
      {{$row.Overdue1?}}class="overduebg"{{/$row.Overdue1?}}>{{$row.Overdue1}}</td>
    <td
      {{$row.Overdue2?}}class="overduebg"{{/$row.Overdue2?}}>{{$row.Overdue2}}</td>
  </tr>
  {{/foreach}}
  <tfoot class='bold pad5'>
  <tr>
    <td>Totals:</td>
    <td colspan=2></td>
    <td>{{$totals.balance}}</td>
    <td>{{$totals.due}}</td>
    <td>{{$totals.overdue1}}</td>
    <td>{{$totals.overdue2}}</td>
  </tr>
  </tfoot>
</table><div class='center'>
  <button id='send'>Send Emails</button>
</div>

