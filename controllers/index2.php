<?php
$view = new View('test');

$view['test'] = ['wawa'=>'wa'];
$view->render();
