<?php
  use ADV\App\Form\Form;

  Page::start('test');
  global $class_types;
  $form = new Form();
  $form->arraySelect('test', $class_types, null);
  class test
  {
    var $test = 5;
  }
$array = new test;
  $form->setValues($array);
  $test = $form['test'];
  echo $test;
  var_dump(json_encode($form));
  $form->useDefaults = true;
  echo $test;
  var_dump(json_encode($form));
  Page::end(true);
