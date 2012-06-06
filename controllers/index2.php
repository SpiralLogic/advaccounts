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
