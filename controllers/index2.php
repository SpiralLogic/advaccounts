<?php
  use ADV\App\Form\Form;

  Page::start('test');
  global $class_types;
  $form = new Form();
  $form->arraySelect('test', null, $class_types);

  $test        = $form['test'];
  $test->value = 3;
  var_dump(json_encode($form));

  $form->useDefaults = true;
  echo $test;
  var_dump(json_encode($form));
  Page::end(true);
