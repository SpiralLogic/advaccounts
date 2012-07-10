<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 10/07/12
 * Time: 10:08 AM
 * To change this template use File | Settings | File Templates.
 */
?>
<form <? if ($multi):?>enctype='multipart/form-data'<?endif;?> method='post' action='<?=$action?>' <?=$name?"name='$name' id='$name'":''?>>
