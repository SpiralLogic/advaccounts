<?php
	include "bootstrap.php";
	echo "<pre>";
	$sql = "SELECT * FROM debtor_balances WHERE Balance>0";
	$result = DB::query($sql, "The customer details could not be retrieved");
	echo "<table>";
	echo "<tr><th>Name</th><th>Balance</th><th>Due</th><th>Overdue1</th><th>Overdue2</th></tr>";
	while ($row = DB::fetch($result)) {
		echo "<tr><td><input type='checkbox' value='" . $row['debtor_no'] . "' checked></input> </td><td>" . $row['name'] . "</td><td>" . $row['Balance'] . "</td><td>" . $row['Due'] . "</td><td>" . $row['Overdue1'] . "</td><td>" . $row['Overdue2'] . "</td></tr>";
	}
	;
	echo "</table><button id='send'>Send Emails</button>";
	$js = <<<JS


$(function() {
$("#send").click(sendstatements);
var tosend = $("input");
})

sendstatements = function() {

	$.each(tosend,function(k,v) {
		if (v.checked) console.log(v);

	})

}
JS;
	JS::beforeload($js);
JS::render();