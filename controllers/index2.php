<?php
  use ADV\App\Form\Form;

  Page::start('test');
  $test = new Form();
  $test->checkbox('test', Dates::_today());
  $test->text('test2', Dates::_today());
  $test->textarea('test3', Dates::_today());
  foreach ($test as $field) {
    echo $field;
  }
  Page::end();
