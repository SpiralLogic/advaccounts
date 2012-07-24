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
  {{# $balance = $due = $overdue1 = $overdue2 = 0;
  foreach ($rows as $row): $balance += $row['Balance'];
  $due += $row['Due'];
  $overdue1 += $row['Overdue1'];
  $overdue2 += $row['Overdue2'];
  ?>
  <tr>
    <td class='aligncenter'><input class='email' type='checkbox' value='{{$row[' debtor_id']}}' checked/></td>
    <td class='left'><span class='bold'>{{$row['name']}}</span>({{$row['email']}})
    </td>
    <td>{{$row['phone']}}</td>
    <td>{{$row['Balance']}}</td>
    <td
    {{#if ($row['Due'] > 0 ? 'class="currentfg"' : '')}}>{{($row['Due'] > 0 ? $row['Due'] : 0)}}</td>
    <td
    {{($row['Overdue1'] > 0 ? 'class="overduebg"' : '')}}>{{($row['Overdue1'] > 0 ? $row['Overdue1'] : 0)}}</td>
    <td
    {{($row['Overdue2'] > 0 ? 'class="overduebg"' : '')}}>{{($row['Overdue2'] > 0 ? $row['Overdue2'] : 0)}}</td>
  </tr>
  {{/foreach}}
  <tfoot class='bold pad5'>
  <tr>
    <td>Totals:</td>
    <td colspan=2></td>
    <td>{{$balance}}</td>
    <td>{{$due}}</td>
    <td>{{$overdue1}}</td>
    <td>{{$overdue2}}</td>
  </tr>
  </tfoot>
</table><div class='center'>
  <button id='send'>Send Emails</button>
</div>
