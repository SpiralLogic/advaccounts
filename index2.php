<?php
include('bootstrap.php');


$results = DB::select('*')->from('debtors_master')->where('name LIKE %sol%')->fetch()->all();
var_dump($results);