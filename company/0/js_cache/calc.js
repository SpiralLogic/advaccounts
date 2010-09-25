$("input[id^='ChgPriceCalc']").live('focus', function() {
$(this).calculator();
});

/*, buttonImageOnly: true, buttonImage: '../themes/default/images/calculator.png'*/