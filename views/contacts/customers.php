<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 10/07/12
   * Time: 9:47 AM
   * To change this template use File | Settings | File Templates.
   */
  /** @noinspection PhpUndefinedMethodInspection */
  JS::autocomplete('customer', 'Company.fetch');
?>
<? if (!$frame): ?>
<div id='companysearch' class='center bold font13 pad20'>
  <label for='customer'>Search Customer:&nbsp;<input name='customer' id='customer' type='text' autofocus>
</div>
<? endif; ?>
