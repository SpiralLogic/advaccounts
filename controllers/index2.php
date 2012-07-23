<?php
  $test         = new View('test');
  $test['test'] = 'watest';
  $test['wawa'] = true;
  $test->render();
