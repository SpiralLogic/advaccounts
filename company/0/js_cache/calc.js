$("input[id^='ChgPriceCalc']").live('focus', function() {
    $(this).calculator({
        showOn: 'operator', isOperator: mathsOnly});

    function mathsOnly(ch, event, value, base, decimalChar) {
        return '+-*/'.indexOf(ch) > -1 && !(ch == '-' && value == '');
    }

});

/*, buttonImageOnly: true, buttonImage: '../themes/default/images/calculator.png'*/