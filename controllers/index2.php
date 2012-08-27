<?php
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test = $_SESSION['forms'][$_POST['_form_id']];
    $test->runValidators(new valids);
  }
  $test = new \ADV\App\Form\Form();
  $test->text('test', 'rrr')->setValidator('wawa');
  echo $test->start();
  $wa = $test['test'];
  echo $wa;
  echo "<button>test</button>";
  echo $test->end();

  class valids
  {
    function wawa() {
      echo 'wawaed';
    }
  }
