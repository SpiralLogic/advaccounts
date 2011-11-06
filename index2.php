<?php
include('bootstrap.php');

$result = DB::query('SELECT * FROM users WHERE phone LIKE '.DB::escape('%04222%'));
echo '<pre>';
	var_dump($result->fetch());