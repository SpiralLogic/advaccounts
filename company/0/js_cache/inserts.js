

var _focus;var _hotkeys={'alt':false,
'focus':-1
};function save_focus(e){_focus=e.name||e.id;var h=document.getElementById('hints');if(h){h.style.display=e.title&&e.title.length ? 'inline':'none';h.innerHTML=e.title ? e.title:'';}
}
function _expand(tabobj){var ul=tabobj.parentNode.parentNode;var alltabs=ul.getElementsByTagName("input");var frm=tabobj.form;if(ul.getAttribute("rel")){for(var i=0;i<alltabs.length;i++){alltabs[i].className="ajaxbutton"
}
tabobj.className="current";JsHttpRequest.request(tabobj)
}
}
function expandtab(tabcontentid,tabnumber){var tabs=document.getElementById(tabcontentid);_expand(tabs.getElementsByTagName("input")[tabnumber]);}
function _set_combo_input(e){e.setAttribute('_last',e.value);e.onblur=function(){var but_name=this.name.substring(0,this.name.length-4)+'button';var button=document.getElementsByName(but_name)[0];var select=document.getElementsByName(this.getAttribute('rel'))[0];save_focus(select);if(button&&(this.value!=this.getAttribute('_last'))){JsHttpRequest.request(button);}else if(this.className=='combo2'){this.style.display='none';select.style.display='inline';setFocus(select);}
return false;};e.onkeyup=function(ev){var select=document.getElementsByName(this.getAttribute('rel'))[0];if(select&&select.selectedIndex>=0){var len=select.length;var byid=this.className=='combo';var ac=this.value.toUpperCase();select.options[select.selectedIndex].selected=false;for(i=0;i < len;i++){var txt=byid ? select.options[i].value:select.options[i].text;if(txt.toUpperCase().indexOf(ac)>=0){select.options[i].selected=true;break;}
}
}
};e.onkeydown=function(ev){ev=ev||window.event;key=ev.keyCode||ev.which;if(key==13){this.blur();return false;}
}
}
function _update_box(s){var byid=s.className=='combo';var rel=s.getAttribute('rel');var box=document.getElementsByName(rel)[0];if(box&&s.selectedIndex>=0){var opt=s.options[s.selectedIndex];if(box){box.value=byid ? opt.value:opt.text;box.setAttribute('_last',box.value);}
}
}
function _set_combo_select(e){e.setAttribute('_last',e.selectedIndex);e.onblur=function(){if(this.className=='combo')
_update_box(this);if(this.selectedIndex!=this.getAttribute('_last'))
this.onchange();}
e.onchange=function(){var s=this;this.setAttribute('_last',this.selectedIndex);if(s.className=='combo')
_update_box(s);if(s.selectedIndex>=0){var sname='_'+s.name+'_update';var update=document.getElementsByName(sname)[0];if(update){JsHttpRequest.request(update);}
}
return true;}
e.onkeydown=function(event){event=event||window.event;key=event.keyCode||event.which;var box=document.getElementsByName(this.getAttribute('rel'))[0];if(box&&key==32&&this.className=='combo2'){this.style.display='none';box.style.display='inline';box.value='';setFocus(box);return false;}
}
}
var _w;function callEditor(key){var el=document.getElementsByName(editors[key][1])[0];if(_w)_w.close();_w=open(editors[key][0]+el.value+'&popup=1',
"edit","Scrollbars=0,resizable=0,width=800,height=600");if(_w.opener==null)
_w.opener=self;editors._call=key;_w.focus();}
function passBack(value){var o=opener;if(value!=false){var back=o.editors[o.editors._call];var to=o.document.getElementsByName(back[1])[0];if(to){if(to[0]!=undefined)
to[0].value=value;to.value=value;o.JsHttpRequest.request('_'+to.name+'_update',to.form);o.setFocus(to.name);}
}
close();}

var inserts={'input':function(e){if(e.onfocus==undefined){e.onfocus=function(){save_focus(this);if(this.className=='combo')
this.select();};}
if(e.className=='combo'||e.className=='combo2'){_set_combo_input(e);}
else
if(e.type=='text'){e.onkeydown=function(ev){ev=ev||window.event;key=ev.keyCode||ev.which;if(key==13){if(e.className=='searchbox')e.onblur();return false;}
return true;}
}
},
'input.combo2,input[aspect="fallback"]':
function(e){e.style.display='none';},
'div.js_only':
function(e){e.style.display='block';},
'button.ajaxsubmit,input.ajaxsubmit,input.editbutton,button.editbutton,button.navibutton':
function(e){e.onclick=function(){save_focus(e);var asp=e.getAttribute('aspect')
if(asp&&asp.indexOf('process')!==-1)
JsHttpRequest.request(this,null,60000);else
JsHttpRequest.request(this);return false;}
},
'button':function(e){if(e.name){var func=_validate[e.name];var old=e.onclick;if(func){if(typeof old!='function'||old==func){e.onclick=func;}else{e.onclick=function(){if(func()){old();return true;}
else
return false;}
}
}
}
},
'.amount':function(e){if(e.onblur==undefined){e.onblur=function(){var dec=this.getAttribute("dec");price_format(this.name,get_amount(this.name),dec);};}
},
'.searchbox':
function(e){e.setAttribute('_last_val',e.value);e.setAttribute('autocomplete','off');e.onblur=function(){var val=this.getAttribute('_last_val');if(val!=this.value){this.setAttribute('_last_val',this.value);JsHttpRequest.request('_'+this.name+'_changed',this.form);}
}
},
'button[aspect*selector], input[aspect*selector]':function(e){e.onclick=function(){passBack(this.getAttribute('rel'));return false;}
},
'select':function(e){if(e.onfocus==undefined){e.onfocus=function(){save_focus(this);};var c=e.className;if(c=='combo'||c=='combo2')
_set_combo_select(e);}
},
'a.printlink':function(l){l.onclick=function(){save_focus(this);JsHttpRequest.request(this,null,60000);return false;}
},
'a':function(e){e.onkeydown=function(ev){ev=ev||window.event;key=ev.keyCode||ev.which;if(key==37||key==38||key==39||key==40){move_focus(key,e,document.links);ev.returnValue=false;return false;}
}
},
'ul.ajaxtabs':function(ul){var ulist=ul.getElementsByTagName("li");for(var x=0;x<ulist.length;x++){var ulistlink=ulist[x].getElementsByTagName("input")[0];if(ulistlink.onclick==undefined){var url=ulistlink.form.action
ulistlink.onclick=function(){_expand(this);return false;}
}
}
}



};function stopEv(ev){if(ev.preventDefault){ev.preventDefault();ev.stopPropagation();}else{ev.returnValue=false;ev.cancelBubble=true;window.keycode=0;}
return false;}

function setHotKeys(){document.onkeydown=function(ev){ev=ev||window.event;key=ev.keyCode||ev.which;if(key==18&&!ev.ctrlKey){_hotkeys.alt=true;_hotkeys.focus=-1;return stopEv(ev);}
else if(ev.altKey&&!ev.ctrlKey&&((key>47&&key<58)||(key>64&&key<91))){var n=_hotkeys.focus;var l=document.links;var cnt=l.length;key=String.fromCharCode(key);for(var i=0;i<cnt;i++){n=(n+1)%cnt;if(l[n].accessKey==key&&l[n].scrollWidth){_hotkeys.focus=n;var tmp=function(){document.links[_hotkeys.focus].focus();};setTimeout(tmp,0);break;}
}
return stopEv(ev);}
if((ev.ctrlKey&&key==13)||key==27){_hotkeys.alt=false;_hotkeys.focus=-1;ev.cancelBubble=true;if(ev.stopPropagation)ev.stopPropagation();for(var j=0;j<this.forms.length;j++){var form=this.forms[j];for(var i=0;i<form.elements.length;i++){var el=form.elements[i];var asp=el.getAttribute('aspect');if(el.className!='editbutton'&&(asp&&asp.indexOf('selector')!==-1)&&(key==13||key==27)){passBack(key==13 ? el.getAttribute('rel'):false);ev.returnValue=false;return false;}
if(((asp&&asp.indexOf('default')!==-1)&&key==13)||((asp&&asp.indexOf('cancel')!==-1)&&key==27)){if(asp.indexOf('process')!==-1)
JsHttpRequest.request(el,null,60000);else
JsHttpRequest.request(el);ev.returnValue=false;return false;}
}
}
ev.returnValue=false;return false;}
if(editors&&editors[key]){callEditor(key);return stopEv(ev);}
return true;};document.onkeyup=function(ev){ev=ev||window.event;key=ev.keyCode||ev.which;if(_hotkeys.alt==true){if(key==18){_hotkeys.alt=false;if(_hotkeys.focus>=0){var link=document.links[_hotkeys.focus];if(link.onclick)
link.onclick();else
if(link.target=='_blank'){window.open(link.href,'','toolbar=no,scrollbar=no,resizable=yes,menubar=no,width=900,height=500');openWindow(link.href,'_blank');}else
window.location=link.href;}
return stopEv(ev);}
}
return true;}
}
Behaviour.register(inserts);Behaviour.addLoadEvent(setFocus);Behaviour.addLoadEvent(setHotKeys);