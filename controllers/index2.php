<?php
  use ADV\Core\DIC;

  if (!$jobsboardDB) {
    $jobsboardDB = DIC::get('DB', 'default');
  }
  var_dump($jobsboardDB);
