/**
 * Created with JetBrains PhpStorm.
 * User: advanced
 * Date: 27/08/12
 * Time: 10:01 PM
 * To change this template use File | Settings | File Templates.
 */
Adv.extend({ Help: (function () {
  var $current //
    , indicatortimer //
    , $help_modal = $('#help_text_edit').modal({show: false}).appendTo('body')//
    , showHelp = function () {
      var content//
        , data = {page: location.pathname + location.search, element: $current.attr('id')} //
        , page = data.page //
        , element = data.element//
        , editTextarea = $('#newhelp')//
        , url = '/modules/help_texts'//
        , showEditor = function () {
          $current.popover('hide');
          $help_modal.modal('show').on('click', '.save',function () {
            var text = $('<div>').html(editTextarea.val())[0].innerHTML;
            $.post(url, {text: text, element: element, page: page, save: true}, makePopover, 'json');
            $help_modal.modal('hide');
          }).on('shown',function () {
                  editTextarea[0].focus();
                  editTextarea[0].select();
                }).on('hidden', function () {
                        $current[0].focus();
                        $current[0].select();
                      });
          editTextarea.empty().text(content);
        }, makePopover = function (data) {
          content = data.text;
          $(':input').popover('destroy');
          indicator.hide();
          $current.popover({title: 'Help' + "<i class='floatright help-edit font13 icon-edit'>&nbsp;</i>", html: true, content: data.text }).popover('show');
          $('.popover-title').on('click', '.help-edit', showEditor);
        };
      if (!$current.attr('id')) {
        return;
      }
      $.post(url, data, makePopover, 'json');
    } //
    , indicator = $('#help_indicator').on('click', showHelp)
    , startHide = function () {
      clearTimeout(indicatortimer);
      indicatortimer = setTimeout(function () {indicator.animate({opacity: 0}, 300, function () {$(this).hide();})}, 500);
      $current.off('mouseleave.indicator');
    } //
    , showIndicator = function () {
      $current = $(this);
      $(':input').popover('destroy');
      $current.on('mouseleave.indicator', startHide);
      clearTimeout(indicatortimer);
      indicator.show().css('opacity', '1').position({my: "left top", at: "right top", of: $current})
    }; //
  Adv.o.wrapper.on('mouseenter', 'button,textarea,input,select', showIndicator);
  indicator.on('mouseenter',function () {
    clearTimeout(indicatortimer);
  }).on('mouseleave', startHide);
}())});
