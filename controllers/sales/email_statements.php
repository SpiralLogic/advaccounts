<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  Page::start(_("Email Statements"), SA_OPEN);

  echo "<pre>";
  $sql = "SELECT DISTINCT db.*, b.email , b.phone FROM debtor_balances db, branches b WHERE db.debtor_no = b.debtor_no AND LENGTH(b.email)>0 AND b.branch_ref = 'Accounts' AND Balance>0";
  $result = DB::query($sql, "The customer details could not be retrieved");
  echo "<table id='table' class='grid center pad2'>";
  echo "<tr><th><button id='all'>All</button></th><th>Name</th><th>Phone</th><th>Balance</th><th>Due</th><th>Overdue1</th><th>Overdue2</th></tr>";
  $balance = $due = $overdue1 = $overdue2 = 0;
  while ($row = DB::fetch($result)) {
    echo "<tr><td class='aligncenter'><input class='email' type='checkbox' value='" . $row['debtor_no'] . "' checked></input>
		</td>"
      . "<td class='left'><span class='bold'>" . $row['name'] . "</span>(" . $row['email'] . ")</td>"
      . "<td>" . $row['phone'] . "</td>"
      . "<td>" . $row['Balance'] . "</td>"

      . "<td " . ($row['Due'] > 0 ? 'class="currentfg"' : '') . ">" . ($row['Due'] > 0 ? $row['Due'] : 0) . "</td>"
      . "<td " . ($row['Overdue1'] > 0 ? 'class="overduebg"' : '') . ">" . ($row['Overdue1'] > 0 ? $row['Overdue1'] : 0) . "</td>"
      . "<td " . ($row['Overdue2'] > 0 ? 'class="overduebg"' : '') . ">" . ($row['Overdue2'] > 0 ? $row['Overdue2'] : 0) . "</td></tr>";
    $balance += $row['Balance'];
    $due += $row['Due'];
    $overdue1 += $row['Overdue1'];
    $overdue2 += $row['Overdue2'];
  }

  echo "<tfoot class='bold pad5'><tr><td>Totals:</td><td></td><td>$balance</td><td>$due</td><td>$overdue1</td><td>$overdue2</td></tr></tfoot>";

  echo "</table><div class='center'><button id='send'>Send Emails</button></div>";
  $js = <<<JS
$(function() {
$("#send").click(sendstatements);
})
var tosend = document.getElementsByClassName('email');
tosend = toArray(tosend);

sendstatements = function() {
var v = tosend.pop(), postVars = {
REP_ID:108,
PARAM_0:0,
PARAM_1:'',
PARAM_2:1,
PARAM_3:'',
PARAM_4:0,
PARAM_5:0,
PARAM_6:0,
PARAM_7:0
};

if (!v) return;
		if (!v.checked) sendstatements();
		postVars['PARAM_0']=v.value;
$.post('/reporting/prn_redirect.php',postVars,function(data) {
console.log(data);
$('#table').after(data);
		sendstatements();
});

}
function toArray(obj) {
 var array = [];
 // iterate backwards ensuring that length is an UInt32
 for (var i = obj.length >>> 0; i--;) {
 array[i] = obj[i];
 }
 return array;
}
JS;
  JS::beforeload($js);
  Page::end();
