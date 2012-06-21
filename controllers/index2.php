<?php
  Page::start('test');
  $test         = new View('test');
  $test['test'] = 'this was assigned first';
  $store        = $test->render(true);
  $test['test'] = 'wewerwewe';
  $test->render();
  echo $store;
  Page::end();
