<?php
    use ADV\Core\Errors;
    use ADV\Core\JS;

    echo "<pre>";
    class test
    {
        function tes2t() {
            trigger_error('test', E_USER_ERROR);
        }
    }

    $test = new test();
    $test->tes2t();
    JS::_renderJSON();
