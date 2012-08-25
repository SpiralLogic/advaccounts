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
  list:        [],
  register:    function (sheet) {
    Behaviour.list.push(sheet);
  },
  start:       function () {
    Behaviour.addLoadEvent(Behaviour.apply);
  },
  apply:       function () {
    var selector = '', sheet, element, list;
    for (var h = 0; sheet = Behaviour.list[h]; h++) {
      for (selector in sheet) {
        var sels = selector.split(',');
        for (var n = 0; n < sels.length; n++) {
          list = document.getElementsBySelector(sels[n]);
          if (!list) {
            continue;
          }
          for (var i = 0; element = list[i]; i++) {
            sheet[selector](element);
          }
        }
      }
    }
  },
  addLoadEvent:function (func) {
    var oldonload = window.onload;
    if (typeof window.onload != 'function') {
      window.onload = func;
    }
    else {
      window.onload = function () {
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
  tout = (tout) ? tout : 15000;
  try {
    Adv.loader.on(tout);
  }
  catch (e) {
  }
  Adv.Scroll.loadPosition(true);
  JsHttpRequest._request(trigger, form, tout, 0);
};
JsHttpRequest._request = function (trigger, form, tout, retry) {
  if (trigger.tagName == 'A') {
    var content = {};
    var upload = 0;
    var url = trigger.href;
    if (trigger.id) {
      content[trigger.id] = 1;
    }
  }
  else {
    var submitObj = typeof(trigger) == "string" ? document.getElementsByName(trigger)[0] : trigger;
    form = form || (submitObj && submitObj.form);
    upload = form && form.enctype == 'multipart/form-data';
    url = form ? form.action : window.location.toString();
    content = this.formInputs(trigger, form, upload);
    if (!form) {
      url = url.substring(0, url.indexOf('?'));
    }
    if (!submitObj) {
      content[trigger] = 1;
    }
  }
  // this is to avoid caching problems
  content['_random'] = Math.random() * 1234567;
  if (trigger.tagName === 'BUTTON') {
    content['_action'] = trigger.value;
  }
  content['_control'] = trigger.id;
  var tcheck = setTimeout(function () {
    for (var id in JsHttpRequest.PENDING) {
      var call = JsHttpRequest.PENDING[id];
      if (call != false) {
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
    retry ? Adv.loader.on(tout) : Adv.loader.off('warning.png');
    if (retry) {
      JsHttpRequest._request(trigger, form, tout, retry - 1);
    }
  }, tout);
  JsHttpRequest.query((upload ? "form." : "") + "POST " + url, // force form loader
                      content, // Function is called when an answer arrives.
                      function (result, errors) {
                        var tooltipclass;
                        // Write the answer.
                        var newwin = 0, repwin;
                        if (result) {
                          for (var i in result) {
                            atom = result[i];
                            cmd = atom['n'];
                            property = atom['p'];
                            type = atom['c'];
                            id = atom['t'];
                            data = atom['data'];
//				debug(cmd+':'+property+':'+type+':'+id);
                            // seek element by id if there is no elemnt with given name
                            objElement = document.getElementsByName(id)[0] || document.getElementById(id);
                            if (cmd == 'as') {
                              eval("objElement.setAttribute('" + property + "'," + data + ");");
                            }
                            else {
                              if (cmd == 'up') {
//				if(!objElement) alert('No element "'+id+'"');
                                if (objElement) {
                                  if (objElement.tagName == 'INPUT' || objElement.tagName == 'TEXTAREA') {
                                    objElement.value = data;
                                  }
                                  else {
                                    objElement.innerHTML = data;
                                  } // selector, div, span etc
                                }
                              }
                              else {
                                switch (cmd) {
                                  case 'di':
                                    objElement.disabled = data;
                                    break;
                                  case 'fc':
                                    Adv.Forms.setFocus(data);
                                    break;
                                  case 'js':
                                    eval(data);
                                    break;
                                  case 'rd':
                                    window.location = data;
                                    break;
                                  case 'pu':
                                    newwin = 1;
                                    window.open(data, undefined, 'toolbar=no,scrollbar=no,resizable=yes,menubar=no');
                                    break;
                                  default:
                                    errors = errors + '<br>Unknown ajax function: ' + cmd;
                                }
                              }
                            }
                          }
                          if (tcheck) {
                            JsHttpRequest.clearTimeout(tcheck);
                          }
                          // Write errors to the debug div.
                          if (errors) {
                            Adv.Status.show({html:errors});
                            if (cmd =='fc' && Adv.msgbox.find('div').is('.err_msg,.warn_msg')) {
                              if (Adv.msgbox.find('div').is('err_msg')) {
                                tooltipclass = 'error';
                              } else {
                                tooltipclass = 'warning';
                              }

                              var feild =$('#'+data);
                              if (feild.is('input'))
                              {
                                feild.attr('title', Adv.msgbox.text()).tooltip({placement:'right', class:tooltipclass}).tooltip('show');
                              }
                            }
                          }
                          if (Adv.loader) {
                            Adv.loader.off();
                          }
                          Behaviour.apply();
                          //document.getElementById('msgbox').scrollIntoView(true);
                          // Restore focus if we've just lost focus because of DOM element refresh
                          Adv.Events.rebind();
                          if (!errors && !newwin) {
                            Adv.Forms.setFocus();
                          }
                        }
                      }, false);
}
// collect all form input values plus inp trigger value
JsHttpRequest.formInputs = function (inp, objForm, upload) {
  var submitObj = inp;
  var q = {};
  if (typeof(inp) == "string") {
    submitObj = document.getElementsByName(inp)[0] || inp;
  }
  objForm = objForm || (submitObj && submitObj.form);
  if (objForm) {
    var formElements = objForm.elements;
    for (var i = 0; i < formElements.length; i++) {
      var el = formElements[i];
      var name = el.name;
      if (!el.name) {
        continue;
      }
      if (upload) { // for form containing file inputs collect all
        // form elements and add value of trigger submit button
        // (internally form is submitted via form.submit() not button click())
        q[name] = submitObj.type == 'submit' && el == submitObj ? el.value : el;
        continue;
      }
      if (el.type) {
        if (((el.type == 'radio' || el.type == 'checkbox') && el.checked == false) || (el.type == 'submit' && (!submitObj || el.name != submitObj.name))) {
          continue;
        }
      }
      if (el.disabled && el.disabled == true) {
        continue;
      }
      if (name) {
        if (el.type == 'select-multiple') {
          name = name.substr(0, name.length - 2);
          q[name] = new Array;
          for (var j = 0; j < el.length; j++) {
            s = name.substring(0, name.length - 2);
            if (el.options[j].selected == true) {
              q[name].push(el.options[j].value);
            }
          }
        }
        else {
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
