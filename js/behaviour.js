/*
 Behaviour v1.1 by Ben Nolan, June 2005. Based largely on the work
 of Simon Willison (see comments by Simon below).
 Small fixes by J.Dobrowolski for Front Accounting May 2008
 Description:

 Uses css selectors to apply javascript behaviours to enable
 unobtrusive javascript in html documents.

 Usage:

 var myrules = {
 'b.someclass' : function(element){
 element.onclick = function(){
 alert(this.innerHTML);
 }
 },
 '#someid u' : function(element){
 element.onmouseover = function(){
 this.innerHTML = "BLAH!";
 }
 }
 };

 Behaviour.register(myrules);

 // Call Behaviour.apply() to re-apply the rules (if you
 // update the dom, etc).

 License:

 This file is entirely BSD licensed.

 More information:

 http://ripcord.co.nz/behaviour/

 */

var Behaviour = {
	list:[],

	register:function(sheet) {
		Behaviour.list.push(sheet);
	},

	start:function() {
		Behaviour.addLoadEvent(function() {
			Behaviour.apply();
		});
	},

	apply:function() {
		for (h = 0; sheet = Behaviour.list[h]; h++) {
			for (selector in sheet) {
				var sels = selector.split(',');
				for (var n = 0; n < sels.length; n++) {
					list = document.getElementsBySelector(sels[n]);

					if (!list) {
						continue;
					}

					for (i = 0; element = list[i]; i++) {
						sheet[selector](element);
					}
				}
			}
		}
	},

	addLoadEvent:function(func) {
		var oldonload = window.onload;

		if (typeof window.onload != 'function') {
			window.onload = func;
		} else {
			window.onload = function() {
				oldonload();
				func();
			}
		}
	}
}

Behaviour.start();
document.getElementsBySelector = jQuery;
