<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
	echo "<pre>";
	$sql = "SELECT * FROM debtor_balances WHERE Balance>0";
	$result = DB::query($sql, "The customer details could not be retrieved");
	echo "<table id='table'>";
	echo "<tr><th>Name</th><th>Balance</th><th>Due</th><th>Overdue1</th><th>Overdue2</th></tr>";
	while ($row = DB::fetch($result)) {
		echo "<tr><td><input class='email' type='checkbox' value='" . $row['debtor_no'] . "' checked></input> </td><td>" . $row['name'] . "</td><td>" . $row['Balance'] . "</td><td>" . $row['Due'] . "</td><td>" . $row['Overdue1'] . "</td><td>" . $row['Overdue2'] . "</td></tr>";
	}
	echo "</table><button id='send'>Send Emails</button>";
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
PARAM_5:0
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
	JS::render();
