<?php
  $view         = new View('test');
  $view['test'] = ['test'=> 'wawa', 'wawa'=> ['wawa'=> 'ksjks']];
  $view->render();
