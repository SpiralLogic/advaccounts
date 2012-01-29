/*
 Behaviour v1.1 by Ben Nolan, June 2005. Based largely on the work
 of Simon Willison (see comments by Simon below).
 Small fixes by J.Dobrowolski for ADV Accounting May 2008
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
/**********************************************************************
 Copyright (C) Advanced Group PTY LTD
 Released under the terms of the GNU General Public License, GPL,
 as published by the Free Software Foundation, either version 3
 of the License, or (at your option) any later version.
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
 ***********************************************************************/
//
//	JsHttpRequest class extensions.
//
// Main functions for asynchronus form submitions
// 	Trigger is the source of request and can have following forms:
// 	- input object - all form values are also submited
//  - arbitrary string - POST var trigger with value 1 is added to request;
//		if form parameter exists also form values are submited, otherwise
//		request is directed to current location
//
JsHttpRequest.request = function (trigger, form, tout) {
//	if (trigger.type=='submit' && !validate(trigger)) return false;
	tout = tout | 15000;	// default timeout value
	try
		{Adv.loader.on(tout > 60000 ? 'progressbar.gif' : 'ajax-loader.gif');} catch (e)
		{}
	;
	JsHttpRequest._request(trigger, form, tout, 0);
}

JsHttpRequest._request = function (trigger, form, tout, retry) {
	if (trigger.tagName == 'A')
		{
			var content = {};
			var upload = 0;
			var url = trigger.href;
			if (trigger.id) content[trigger.id] = 1;
		} else
		{
			var submitObj = typeof(trigger) == "string" ?
											document.getElementsByName(trigger)[0] : trigger;

			form = form || (submitObj && submitObj.form);

			var upload = form && form.enctype == 'multipart/form-data';

			var url = form ? form.action :
								window.location.toString();

			var content = this.formInputs(trigger, form, upload);

			if (!form) url = url.substring(0, url.indexOf('?'));

			if (!submitObj)
				{
					content[trigger] = 1;
				}
		}
	// this is to avoid caching problems
	content['_random'] = Math.random() * 1234567;

	var tcheck = setTimeout(
	 function () {
		 for (var id in JsHttpRequest.PENDING)
			 {
				 var call = JsHttpRequest.PENDING[id];
				 if (call != false)
					 {
						 if (call._ldObj.xr) // needed for gecko
							 {
								 call._ldObj.xr.onreadystatechange = function () {
								 };
							 }
						 call.abort(); // why this doesn't kill request in firebug?
//						call._ldObj.xr.abort();
						 delete JsHttpRequest.PENDING[id];
					 }
			 }
		 Adv.loader.on(retry ? 'ajax-loader2.gif' : 'warning.png');
		 if (retry)
			 {
				 JsHttpRequest._request(trigger, form, tout, retry - 1);
			 }
	 }, tout);

	JsHttpRequest.query(
	 (upload ? "form." : "") + "POST " + url, // force form loader
	 content,
	 // Function is called when an answer arrives.
	 function (result, errors) {
		 // Write the answer.
		 var newwin = 0;
		 if (result)
			 {
				 for (var i in result)
					 {
						 atom = result[i];
						 cmd = atom['n'];
						 property = atom['p'];
						 type = atom['c'];
						 id = atom['t'];
						 data = atom['data'];
//				debug(cmd+':'+property+':'+type+':'+id);
						 // seek element by id if there is no elemnt with given name
						 objElement = document.getElementsByName(id)[0] || document.getElementById(id);
						 if (cmd == 'as')
							 {
								 eval("objElement.setAttribute('" + property + "'," + data + ");");
							 } else
							 {
								 if (cmd == 'up')
									 {
//				if(!objElement) alert('No element "'+id+'"');
										 if (objElement)
											 {
												 if (objElement.tagName == 'INPUT' || objElement.tagName == 'TEXTAREA')
													 {
														 objElement.value = data;
													 }
												 else
													 {
														 objElement.innerHTML = data;
													 } // selector, div, span etc
											 }
									 } else
									 {
										 if (cmd == 'di')
											 { // disable/enable element
												 objElement.disabled = data;
											 } else
											 {
												 if (cmd == 'fc')
													 { // set focus
														 _focus = data;
													 } else
													 {
														 if (cmd == 'js')
															 {	// evaluate js code
																 eval(data);
															 } else
															 {
																 if (cmd == 'rd')
																	 {	// client-side redirection
																		 window.location = data;
																	 } else
																	 {
																		 if (cmd == 'pu')
																			 {	// pop-up
																				 newwin = 1;
																				 window.open(data, 'REP_WINDOW', 'toolbar=no,scrollbar=no,resizable=yes,menubar=no');
																			 } else
																			 {
																				 errors = errors + '<br>Unknown ajax function: ' + cmd;
																			 }
																	 }
															 }
													 }
											 }
									 }
							 }
					 }
				 if (tcheck)
					 {
						 JsHttpRequest.clearTimeout(tcheck);
					 }
				 // Write errors to the debug div.
			if (errors)	 Adv.showStatus({html:errors});
				 Adv.loader.off();

				 Behaviour.apply();
				 if (errors.length > 0)
					 {
						 window.scrollTo(0, 0);
					 }
				 //document.getElementById('msgbox').scrollIntoView(true);
				 // Restore focus if we've just lost focus because of DOM element refresh
				 if (!newwin)
					 {
						 Adv.Forms.setFocus();
					 }
				 Adv.Events.rebind();
			 }
	 },
	 false	// do not disable caching
	);
}
// collect all form input values plus inp trigger value
JsHttpRequest.formInputs = function (inp, objForm, upload) {
	var submitObj = inp;
	var q = {};

	if (typeof(inp) == "string")
		{
			submitObj = document.getElementsByName(inp)[0] || inp;
		}

	objForm = objForm || (submitObj && submitObj.form);

	if (objForm)
		{
			var formElements = objForm.elements;
			for (var i = 0; i < formElements.length; i++)
				{
					var el = formElements[i];
					var name = el.name;
					if (!el.name) continue;
					if (upload)
						{ // for form containing file inputs collect all
							// form elements and add value of trigger submit button
							// (internally form is submitted via form.submit() not button click())
							q[name] = submitObj.type == 'submit' && el == submitObj ? el.value : el;
							continue;
						}
					if (el.type)
						{
							if (
							 ((el.type == 'radio' || el.type == 'checkbox') && el.checked == false)
								|| (el.type == 'submit' && (!submitObj || el.name != submitObj.name)))
								{
									continue;
								}
						}
					if (el.disabled && el.disabled == true)
						{
							continue;
						}
					if (name)
						{
							if (el.type == 'select-multiple')
								{
									name = name.substr(0, name.length - 2);
									q[name] = new Array;
									for (var j = 0; j < el.length; j++)
										{
											s = name.substring(0, name.length - 2);
											if (el.options[j].selected == true)
												{
													q[name].push(el.options[j].value);
												}
										}
								}
							else
								{
									q[name] = el.value;
								}
						}
				}
		}
	return q;
}
//
//	User price formatting
//
