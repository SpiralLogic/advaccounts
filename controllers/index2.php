<?php
  use ADV\App\Validation;
$_POST['freight_cost']='2,000.00';
  echo Validation::is_num('freight_cost');
