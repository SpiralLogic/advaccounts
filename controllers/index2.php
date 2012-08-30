<?php
  ini_set('apc.serializer', 'igbinary');
  echo ini_get('apc.serializer');
  phpinfo();
