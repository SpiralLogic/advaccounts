<?php
  $rr
    = <<<JS
$('.grid').find('tbody').sortable({
                                    items: 'tr:not(.edit)',
                                    change:function ()
                                    {
                                      var _this = $(this).find('tr');
                                      $.each(_this, function (k, v) {console.log(arguments);})
                                    },
                                    helper:function (e, ui)
                                    {
                                      ui.children().each(function ()
                                                         {
                                                           $(this).width($(this).width());
                                                         });
                                      return ui;
                                    }});
JS;
  echo '<pre >';
class test {
  public function test1() {
    var_dump(func_get_args(),__METHOD__);
  }
  public static function __callStatic($a,$f) {
    var_dump(func_get_args(),__METHOD__);

  }
}
  echo "<b>make test object</b>\n";
$test = new test();
  echo "<b>test->test1</b>\n";
$test->test1();
  echo "<b>test::test1 :</b>\n";
test::test1();
  echo "<b>test::test2 :</b>\n";
test::test2();
