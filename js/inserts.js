/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
var _focus;
var _hotkeys = {
	'alt': false,	// whether is the Alt key pressed
	'list': false, // list of all elements with hotkey used recently
	'focus': -1		// currently selected list element
};

function validate(e) {
	if (e.name && (typeof _validate[e.name] == 'function'))
		return _validate[e.name](e);
	return true;
}

function save_focus(e) {
  _focus = e.name||e.id;
  var h = document.getElementById('hints');
  if (h) {
	h.style.display = e.title && e.title.length ? 'inline' : 'none';
	h.innerHTML = e.title ? e.title : '';
  }
}

function _expand(tabobj) {

  var ul = tabobj.parentNode.parentNode;
  var alltabs=ul.getElementsByTagName("button");
  var frm = tabobj.form;

  if (ul.getAttribute("rel")){
	for (var i=0; i<alltabs.length; i++){
	  alltabs[i].className = "ajaxbutton"  //deselect all tabs
	}
	tabobj.className = "current";
	JsHttpRequest.request(tabobj)
  }
}

//interface for selecting a tab (plus expand corresponding content)
function expandtab(tabcontentid, tabnumber) {
  var tabs = document.getElementById(tabcontentid);
 _expand(tabs.getElementsByTagName("input")[tabnumber]);
}

function _set_combo_input(e) {
		e.setAttribute('_last', e.value);
		e.onblur=function() { 
		  var but_name = this.name.substring(0, this.name.length-4)+'button';
		  var button = document.getElementsByName(but_name)[0];
		  var select = document.getElementsByName(this.getAttribute('rel'))[0];
		  save_focus(select);
// submit request if there is submit_on_change option set and 
// search field has changed.
		
		  if (button && (this.value != this.getAttribute('_last'))) {
			JsHttpRequest.request(button);
		  } else if(this.className=='combo2') {
				this.style.display = 'none';
				select.style.display = 'inline';
				setFocus(select);
		  }
		  return false;
		};
		e.onkeyup = function(ev) {
			var select = document.getElementsByName(this.getAttribute('rel'))[0];
			if(select && select.selectedIndex>=0) {
			  var len = select.length;
			  var byid = this.className=='combo' || this.className=='combo3';
			  var ac = this.value.toUpperCase();
			  select.options[select.selectedIndex].selected = false;
			  for (i = 0; i < len; i++) {
				var txt = byid ? select.options[i].value : select.options[i].text;
				if (this.className=='combo3') {
				  if(txt.toUpperCase().indexOf(ac) == 0) {
					select.options[i].selected = true;
				  	break;
				  }
				} else {
				  if(txt.toUpperCase().indexOf(ac) >= 0) {
					select.options[i].selected = true;
				  	break;
				  }
				}
			  }
			}
		};
    	e.onkeydown = function(ev) { 
	  		ev = ev||window.event;
	  		key = ev.keyCode||ev.which;
	  		if(key == 13) {
			  this.blur();
	  		  return false;
	  		}
		}
}

function _update_box(s) {
	var byid = s.className=='combo' || s.className=='combo3';
	var rel = s.getAttribute('rel');
	var box = document.getElementsByName(rel)[0];
		if(box && s.selectedIndex>=0) {
			  var opt = s.options[s.selectedIndex];
				if(box) {
				  var old = box.value;
				  box.value = byid ? opt.value : opt.text;
				  box.setAttribute('_last', box.value);
				  return old != box.value
				}
		}
}

function _set_combo_select(e) {
		// When combo position is changed via js (eg from searchbox)
		// no onchange event is generated. To ensure proper change 
		// signaling we must track selectedIndex in onblur handler.
		e.setAttribute('_last', e.selectedIndex);
		e.onblur = function() {
		    var box = document.getElementsByName(this.getAttribute('rel'))[0];
//			if(this.className=='combo')
//			    _update_box(this);
			if ((this.selectedIndex != this.getAttribute('_last'))
				||((this.className=='combo' || this.className=='combo3') && _update_box(this))
				)
					this.onchange();
		}
		e.onchange = function() {
			var s = this;
			this.setAttribute('_last', this.selectedIndex);
			if(s.className=='combo' || this.className=='combo3')
			    _update_box(s);
			if(s.selectedIndex>=0) {
				 var sname = '_'+s.name+'_update';
				 var update = document.getElementsByName(sname)[0];
				 if(update) {
					    JsHttpRequest.request(update);
				} 
			}
			return true;
		}
		e.onkeydown = function(event) {
		    event = event||window.event;
		    key = event.keyCode||event.which;
		    var box = document.getElementsByName(this.getAttribute('rel'))[0];
		    if (box && key == 32 && this.className == 'combo2') {
			    this.style.display = 'none';
			    box.style.display = 'inline';
				box.value='';
				setFocus(box);
			    return false;
			 } else {
			 	if (key == 13 && !e.length) // prevent chrome issue (blocked cursor after CR on empty selector)
			 		return false;
			 }
		}
}

var _w;

function callEditor(key) {
  var el = document.getElementsByName(editors[key][1])[0]; 
  if(_w) _w.close(); // this is really necessary to have window on top in FF2 :/
  _w = open(editors[key][0]+el.value+'&popup=1',
	  "edit","Scrollbars=0,resizable=0,width="+editors[key][2]+",height="+editors[key][3]);
  if (_w.opener == null)
	  _w.opener = self;
  editors._call = key; // store call point for passBack 
  _w.focus();
}

function passBack(value) {
	var o = opener;
	if(value != false) {
		var back = o.editors[o.editors._call]; // form input bindings
		var to = o.document.getElementsByName(back[1])[0];
		if (to) {
			if (to[0] != undefined)	
				to[0].value = value; // ugly hack to set selector to any value
			to.value = value;
			// update page after item selection
			o.JsHttpRequest.request('_'+to.name+'_update', to.form);
			o.setFocus(to.name);
		}
	}
	close();
}

/*
 Behaviour definitions
*/
var inserts = {
	'input': function(e) {
		if(e.onfocus==undefined) {
			e.onfocus = function() {
			    save_focus(this);
				if (this.className == 'combo' || this.className == 'combo3') 
					this.select();
			};
		}
		if (e.className == 'combo' || e.className == 'combo2' || e.className == 'combo3') {
				_set_combo_input(e);
		} 
		else
    		if(e.type == 'text' ) {
   	  			e.onkeydown = function(ev) { 
  					ev = ev||window.event;
  					key = ev.keyCode||ev.which;
 	  				if(key == 13) {
						if(e.className == 'searchbox') e.onblur();
						return false;
					} 
					return true;
	  			}
			}
	},
	'input.combo2,input[aspect="fallback"]': 
	function(e) {
  	    // this hides search button for js enabled browsers
	    e.style.display = 'none';
	},
	'div.js_only': 
	function(e) {
  	    // this shows divs for js enabled browsers only
	    e.style.display = 'block';
	},

	'button': function(e) {
		e.onclick = function(){ return validate(e); }
	},
//	'.ajaxsubmit,.editbutton,.navibutton': // much slower on IE7
	'button.ajaxsubmit,input.ajaxsubmit,input.editbutton,button.editbutton,button.navibutton': 
	function(e) {
			e.onclick = function() {
				if (validate(e)) {
					save_focus(e);
					var asp = e.getAttribute('aspect')
					if (asp && asp.indexOf('process') !== -1)
						JsHttpRequest.request(this, null, 600000); // ten minutes for backup
					else
						JsHttpRequest.request(this);
				}
				return false;
			}
	},
    '.amount': function(e) {
		if(e.onblur==undefined) {
  		  e.onblur = function() {
			var dec = this.getAttribute("dec");
			price_format(this.name, get_amount(this.name), dec);
		  };
		}
	},
	'.searchbox': // emulated onchange event handling for text inputs
		function(e) {
			e.setAttribute('_last_val', e.value);
			e.setAttribute('autocomplete', 'off'); //must be off when calling onblur
  		  	e.onblur = function() {
				var val = this.getAttribute('_last_val');
				if (val != this.value) {
					this.setAttribute('_last_val', this.value);
					JsHttpRequest.request('_'+this.name+'_changed', this.form);
				}
			}
	},
	'button[aspect*selector], button[aspect*abort], input[aspect*selector]': function(e) {
		e.onclick = function() {
			passBack(this.getAttribute('rel'));
			return false;
		}
	},
	'button[aspect=popup]': function(e) {
		e.onclick = function() {
			if(_w) _w.close(); // this is really necessary to have window on top in FF2 :/
			  _w = open(document.location+'popup=1',
				  "edit","Scrollbars=0,resizable=0,width=800,height=600, top=50,left=50");
			  if (_w.opener == null)
				  _w.opener = self;
			//  editors._call = key; // store call point for passBack 
//			  _w.moveTo(50, 50);
			  _w.focus();
			return false;
		}
	},
	'select': function(e) {
		if(e.onfocus==undefined) {
			e.onfocus = function() {
			    save_focus(this);
			};
  		  var c = e.className;
		  if (c == 'combo' || c == 'combo2' || c == 'combo3')
			_set_combo_select(e);
		}
	},
	'a.printlink': 	function(l) {
		l.onclick = function() {
		    save_focus(this);
			JsHttpRequest.request(this, null, 60000);
			return false;
		}
	},
	'a.repopts_link': 	function(l) {
		l.onclick = function() {
		    save_focus(this);
		    var replinks = document.getElementsBySelector('a.repopts_link');
				for(var i in replinks)
					replinks[i].style.fontWeight = replinks[i]==this ? 'bold' : 'normal';
			JsHttpRequest.request(this, null);
			return false;
		}
	},
	'a': function(e) { // traverse menu
  		e.onkeydown = function(ev) { 
			ev = ev||window.event;
			key = ev.keyCode||ev.which;
			if(key==37 || key==38 || key==39 || key==40) {
					move_focus(key, e, document.links);
					ev.returnValue = false;
					return false;
			}
		}
		// prevent unneeded transaction entry abortion
		if (e.className == 'shortcut' 
		 || e.className == 'menu_option' 
		 || e.className == 'menu_tab'
 		 || e.className == 'selected')
			e.onclick = function(ev) {
				if (_validate._processing 
				 && _validate._modified
				 && !confirm(_validate._processing)) {
					ev.returnValue = false;
					return false;
				}
				window.location = e.href;
			}
	},
	'ul.ajaxtabs':	function(ul) {
	    var ulist=ul.getElementsByTagName("li");
	    for (var x=0; x<ulist.length; x++){ //loop through each LI e
		var tab=ulist[x].getElementsByTagName("button")[0];
//		if(tab.onclick==undefined) {
// ?  var modifiedurl=ulistlink.getAttribute("href").replace(/^http:\/\/[^\/]+\//i, "http://"+window.location.hostname+"/")
		    var url = tab.form.action
		    tab.onclick=function(){
			_expand(this);
			return false;
		    }
		}
//	    }
	}

/*	'tr.editrow': function(e) {
		  	e.onkeydown = function(ev) { 
	  		ev = ev||window.event;
	  		key = ev.keyCode||ev.which;
	  		if(key == 13) {
			  // Find & click additem/update button
			  
	  		} else	if(key == 27) {
	  		  return false;
			}
		}

	},
*//*	'#msgbox': function(e) {
	// this is to avoid changing div height after ajax update in IE7
	  e.style.display = e.innerHTML.length ? 'block' : 'none';
	}
*//* TODO
	'a.date_picker':  function(e) {
	    // this un-hides data picker for js enabled browsers
	    e.href = date_picker(this.getAttribute('rel'));
	    e.style.display = '';
	    e.tabindex = -1; // skip in tabbing order
	}
*/
};

function stopEv(ev) {
			if(ev.preventDefault) {
				ev.preventDefault();
				ev.stopPropagation();
			} else {
				ev.returnValue = false;
				ev.cancelBubble = true;
				window.keycode = 0;
			}
			return false;
}
/*
	Modified accesskey system. While Alt key is pressed letter keys moves 
	focus to next marked link. Alt key release activates focused link.
*/
function setHotKeys() {
	document.onkeydown = function(ev) {
		ev = ev||window.event;
		key = ev.keyCode||ev.which;
		if (key == 18 && !ev.ctrlKey) {	// start selection, skip Win AltGr
			_hotkeys.alt = true;
			_hotkeys.focus = -1;
			return stopEv(ev);
		}
		else if (ev.altKey && !ev.ctrlKey && ((key>47 && key<58) || (key>64 && key<91))) {
			key = String.fromCharCode(key);
			var n = _hotkeys.focus;
			var l = document.getElementsBySelector('[accesskey='+key+']');
			var cnt = l.length;
			_hotkeys.list = l;
			for (var i=0; i<cnt; i++) { 
				n = (n+1)%cnt;
				// check also if the link is visible
				if (l[n].accessKey==key && l[n].scrollWidth) {
					_hotkeys.focus = n;
	    // The timeout is needed to prevent unpredictable behaviour on IE.
					var tmp = function() {l[_hotkeys.focus].focus();};
					setTimeout(tmp, 0);
					break;
				}
			}
			return stopEv(ev);
		}
		if((ev.ctrlKey && key == 13) || key == 27) {
			_hotkeys.alt = false; // cancel link selection
			_hotkeys.focus = -1;
			ev.cancelBubble = true;
   			if(ev.stopPropagation) ev.stopPropagation();
			// activate submit/escape form
			for(var j=0; j<this.forms.length; j++) {
				var form = this.forms[j];
				for (var i=0; i<form.elements.length; i++){
					var el = form.elements[i];
					var asp = el.getAttribute('aspect');
					

					if (el.className!='editbutton' && (asp && asp.indexOf('selector') !== -1) && (key==13 || key==27)) {
						passBack(key==13 ? el.getAttribute('rel') : false);
						ev.returnValue = false;
						return false;
					}
					if (((asp && asp.indexOf('default') !== -1) && key==13)||((asp && asp.indexOf('cancel') !== -1) && key==27)) {
						if (validate(el)) {
							if (asp.indexOf('process') !== -1)
								JsHttpRequest.request(el, null, 600000);
							else
								JsHttpRequest.request(el);
						}
						ev.returnValue = false;
						return false;
					}
				}
			}
			ev.returnValue = false;
			return false;
		}
		if (editors && editors[key]) {
			callEditor(key);
			return stopEv(ev); // prevent default binding
		} 
		return true;
	};
	document.onkeyup = function(ev) {
		ev = ev||window.event;
		key = ev.keyCode||ev.which;

		if (_hotkeys.alt==true) {
			if (key == 18) {
				_hotkeys.alt = false;
				if (_hotkeys.focus >= 0) {
					var link = _hotkeys.list[_hotkeys.focus];
					if(link.onclick) 
						link.onclick();
					else
						if (link.target=='_blank') {
							window.open(link.href,'','toolbar=no,scrollbar=no,resizable=yes,menubar=no,width=900,height=500');
							openWindow(link.href,'_blank');
						} else
							window.location = link.href;
				}
			return stopEv(ev);
			} 
		}
		return true;
	}
}

Behaviour.register(inserts);

Behaviour.addLoadEvent(setFocus);
Behaviour.addLoadEvent(setHotKeys);
