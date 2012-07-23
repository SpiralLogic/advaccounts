<?php
  $test         = new View('test');
  $test['test'] = '';
  $test['wawa'] = true;
  $test['wa']   = 'ass';
  $test->render();
