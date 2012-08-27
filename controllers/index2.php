<?php

  Page::start();
  $form = new Form();
  $form->text('test', 'wawa');
  $form->text('test2', 'sdgfswawa');
  $fields = $form->getFields();
  foreach ($fields as $field) {
    echo $field;
  }

  Page::end();
