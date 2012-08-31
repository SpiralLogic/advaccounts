<?php
  use ADV\App\Form\Form;

  Page::start('test');
  $test = new Form();
  $test->checkbox('test', Dates::_today());
  $test->text('test2', Dates::_today());
  $test->textarea('test3', Dates::_today());
  foreach ($fields as $field) {
  }
  $fields = $test->getFields();
  echo $fields['test'];
  Page::end();
