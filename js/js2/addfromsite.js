/**
 * User: Eli Sklar
 * Date: 30/05/11 - 9:50 AM
 */

$ = jQuery;

remote_order = (function () {
	var overlay,remote,message,button;
	return  {
		domain: "http://advaccounts/",
		init: function() {
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
					                         'padding-top':'50px',
					                         'z-index':'1000'
				                         }).attr('id', 'remotetocart');
			}
			if (message === undefined) {
				message = $('<p/>').css({'font-size':'18px','font-weight':'bold'}).prependTo(remote);
			}
			if (button === undefined) {
				button = $('<button/>').css({
					                            position:'relative',
					                            display:'block',
					                            width:180,
					                            'font-size':'14px','font-weight':'bold',
					                            padding:'5px',
					                            margin:'40px auto'
				                            }).text('Create current order').click(
						function() {
							window.location.href = remote_order.domain + "sales/sales_order_entry.php?NewRemoteToSalesOrder=Yes"
						}).appendTo(remote);
				button.clone().text('Add items to current order').click(
						function() {
							window.location.href = remote_order.domain + "sales/sales_order_entry.php?remotecombine=Yes"
						}).appendTo(remote);
			}
		},
		show: function() {
			overlay.appendTo('body'),remote.appendTo('body')
		},
		getDetails: function() {
			return {      item: $('.product_code').text(),
				qty: $("[name^='QTY']").val(),
				desc: $("font.productnamecolorLARGE").text(),
				"new":true
			}
		},
		send: function() {
			$.getJSON(remote_order.domain + 'sales/sales_order_remote.php?jsoncallback=?', remote_order.getDetails(), function(data) {
				var reply;
				remote_order.show();
				if (data.message) reply = data.message;
				if (data['added']) reply = "Added " + data['added'];
				message.html(reply);
				console.log(message.text());
			});
		}
	}
})();
if (window.location.hostname != "www.advancedroadsigns.com.au") {
	var go;
	go = confirm("Not currently at www.advancedroadsigns.com.au, open in new tab?");
	if (go) {
		window.open("http://www.advancedroadsigns.com.au");
	}
} else {
	remote_order.domain = "http://advaccounts/";
	remote_order.init();
	remote_order.send();
}

//javascript:var s=document.createElement('script');s.setAttribute('src', 'https://advanced.sorijen.net.au:2223/js/js2/addfromsite.js');document.getElementsByTagName('body')[0].appendChild(s);;void(0);