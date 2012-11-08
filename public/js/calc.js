$(function ($) {
  Adv.o.wrapper.on('focus', ".amount", function () {
    var value = this.value//
      , mathsOnly = function (ch, event, value) {
        return '+-*/'.indexOf(ch) > -1 && !(ch == '-' && (value == '' || value == '0.00'));
      };
    value = ((value[0] == '-') ? '-' : '') + value.replace(/[^0-9\.]/g, '');
    this.value = value;
    $(this).calculator({
                         useThemeRoller: true, //
                         showOn:         'operator', //
                         isOperator:     mathsOnly, //
                         constrainInput: false, //
                         precision:      user.pdec

                       });
  })
});
