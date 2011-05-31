/**
 * User: Eli Sklar
 * Date: 30/05/11 - 9:50 AM
 */
$ = jQuery;
var overlay,remote,button,message;
code = $('.product_code').text();
quantity = $("[name^='QTY']").val();
description = $("font.productnamecolorLARGE").text();
$.getJSON('http://advaccounts/sales/sales_order_remote.php?jsoncallback=?', {
	          item: code,
	          qty: quantity,
	          desc:description
          }, function(data) {
	overlay.appendTo('body');
	remote.appendTo('body');
	var reply;
	if (data.message) reply = data.message;
	if (data.added) reply = "Added " + data.added;
	message.text(reply);
});
if (overlay == undefined) {
	overlay = $('<div/>').css({display:'block',
		                          position:'absolute',
		                          top:0,
		                          left:0,
		                          width: "100%",
		                          height:"100%",
		                          opacity:".7",
		                          background:"black",
		                          'z-index':'999'
	                          }).click(function() {
		                                   remote.detach();
		                                   $(this).detach()
	                                   });
}

if (message === undefined) {
	message = $('<p/>').css({'font-size':'18px','font-weight':'bold'}).prependTo(remote);
}
if (remote === undefined) {
	remote = $('<div/>').css({
		                         left: ($('body').innerWidth() - 400) / 2,
		                         width:400,
		                         top: 200,
		                         height: 200,
		                         display:'block',
		                         position:'fixed',
		                         margin: "0 auto",
		                         background: 'white',
		                         'border-radius':'17px',
		                         'text-align':'center',
		                         'padding-top':'100px',
		                         'z-index':'1000'
	                         }).attr('id', 'remotetocart');
}
if (button === undefined) {
	button = $('<button/>').css({
		                            position:'relative',
		                            display:'block',
		                            width:100,
		                            'font-size':'14px','font-weight':'bold',
		                            padding:'5px',
		                            margin:'40px auto'
	                            }).text('Go to cart').click(
			function() {
				window.location.href = "http://advaccounts/sales/sales_order_entry.php?NewRemoteToSalesOrder=Yes"
			}).appendTo(remote);
}

//javascript:var%20s=document.createElement('script');s.setAttribute('src',%20'https://advanced.sorijen.net.au:2223/js/js2/addfromsite.js');document.getElementsByTagName('body')[0].appendChild(s);;void(0);