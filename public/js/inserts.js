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

function save_focus(e) {
  Adv.Scroll.focus = e.name || e.id;
  var h = document.getElementById('hints');
  if (h) {
    h.style.display = e.title && e.title.length ? 'inline' : 'none';
    h.innerHTML = e.title ? e.title : '';
  }
}
function _set_combo_input(e) {
  e.setAttribute('_last', e.value);
  e.onblur = function () {
    var but_name = this.name.substring(0, this.name.length - 4) + 'button';
    var button = document.getElementsByName(but_name)[0];
    var select = document.getElementsByName(this.getAttribute('rel'))[0];
    Adv.Forms.saveFocus(select);
// submit request if there is submit_on_change option set and
// search field has changed.
    if (button && (this.value != this.getAttribute('_last'))) {
      JsHttpRequest.request(button);
    }
    else {
      if ($(this).is('.combo2')) {
        this.style.display = 'none';
        select.style.display = 'inline';
        Adv.Forms.setFocus(select);
      }
    }
    return false;
  };
  e.onkeyup = function (ev) {
    var select = document.getElementsByName(this.getAttribute('rel'))[0];
    if (select && select.selectedIndex >= 0) {
      var len = select.length;
      var byid = $(this).is('.combo');
      var ac = this.value.toUpperCase();
      select.options[select.selectedIndex].selected = false;
      for (i = 0; i < len; i++) {
        var txt = byid ? select.options[i].value : select.options[i].text;
        if (txt.toUpperCase().indexOf(ac) >= 0) {
          select.options[i].selected = true;
          break;
        }
      }
    }
  };
  e.onkeydown = function (ev) {
    ev = ev || window.event;
    key = ev.keyCode || ev.which;
    if (key == 13) {
      this.blur();
      return false;
    }
  }
}
function _update_box(s) {
  var byid = $(s).is('.combo');
  var rel = s.getAttribute('rel');
  var box = document.getElementsByName(rel)[0];
  if (box && s.selectedIndex >= 0) {
    var opt = s.options[s.selectedIndex];
    if (box) {
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
  e.onblur = function () {
    var box = document.getElementsByName(this.getAttribute('rel'))[0];
//			if(this.className=='combo')
//			    _update_box(this);
    if ((this.selectedIndex != this.getAttribute('_last')) || ($(this).is('.combo') && _update_box(this))) {
      this.onchange();
    }
  }
  e.onchange = function () {
    var s = this;
    this.setAttribute('_last', this.selectedIndex);
    if ($(s).is('.combo')) {
      _update_box(s);
    }
    if (s.selectedIndex >= 0) {
      var sname = '_' + s.name + '_update';
      var update = document.getElementsByName(sname)[0];
      if (update) {
        JsHttpRequest.request(update);
      }
    }
    return true;
  }
  e.onkeydown = function (event) {
    event = event || window.event;
    key = event.keyCode || event.which;
    var box = document.getElementsByName(this.getAttribute('rel'))[0];
    if (box && key == 32 && $(this).is('.combo2')) {
      this.style.display = 'none';
      box.style.display = 'inline';
      box.value = '';
      Adv.Forms.setFocus(box);
      return false;
    }
  }
}
var _w;
function passBack(value) {
  var o = opener;
  if (!value) {
    var back = o.editors[o.editors._call]; // form input bindings
    var to = o.document.getElementsByName(back[1])[0];
    if (to) {
      if (to[0] != undefined) {
        to[0].value = value;
      } // ugly hack to set selector to any value
      to.value = value;
      // update page after item selection
      o.JsHttpRequest.request('_' + to.name + '_update', to.form);
      o.setFocus(to.name);
    }
  }
  close();
}
/*
 Behaviour definitions
 */
var inserts = {
  'input':                                                                                                          function (e) {
    if (e.onfocus == undefined) {
      e.onfocus = function () {
        Adv.Forms.saveFocus(this);
        if ($(this).is('.combo')) {
          this.select();
        }
      };
    }
    if ($(e).is('.combo,.combo2')) {
      _set_combo_input(e);
    }
    else {
      if (e.type == 'text') {
        e.onkeydown = function (ev) {
          ev = ev || window.event;
          key = ev.keyCode || ev.which;
          if (key == 13) {
            if ($(e).is('.searchbox')) {
              e.onblur();
            }
            return false;
          }
          return true;
        }
      }
    }
  },
  'input.combo2,input[data-aspect="fallback"]':                                                                     function (e) {
    // this hides search button for js enabled browsers
    e.style.display = 'none';
  },
  'div.js_only':                                                                                                    function (e) {
    // this shows divs for js enabled browsers only
    e.style.display = 'block';
  },
//	'.ajaxsubmit,.editbutton,.navibutton': // much slower on IE7
  'button[name="_action"],button.ajaxsubmit,input.ajaxsubmit,input.editbutton,button.editbutton,button.navibutton': function (e) {
    e.onclick = function () {
      Adv.Forms.saveFocus(e);
      var asp = e.getAttribute('data-aspect');
      if (asp && asp.indexOf('process') !== -1) {
        JsHttpRequest.request(this, null, 60000);
      }
      else {
        JsHttpRequest.request(this);
      }
      return false;
    }
  },
  'button':                                                                                                         function (e) {
    if (e.name) {
      var func = (e.name == '_action') ? _validate[e.value] : _validate[e.name];
      var old = e.onclick;
      if (func) {
        if (typeof old != 'function' || old == func) { // prevent multiply binding on ajax update
          e.onclick = func;
        }
        else {
          e.onclick = function () {
            if (func()) {
              old();
              return true;
            }
            else {
              return false;
            }
          }
        }
      }
    }
  },
  '.amount':                                                                                                        function (e) {
    if (e.onblur == undefined) {
      e.onblur = function () {
        var dec = this.getAttribute("data-dec");
        Adv.Forms.priceFormat(this.name, Adv.Forms.getAmount(this.name), dec);
      };
    }
  },
  '.freight':                                                                                                       function (e) {
    if (e.onblur == undefined) {
      e.onblur = function () {
        var dec = this.getAttribute("data-dec");
        Adv.Forms.priceFormat(this.name, Adv.Forms.getAmount(this.name), dec, '2');
      };
    }
  },
  '.searchbox': // emulated onchange event handling for text inputs
                                                                                                                    function (e) {
                                                                                                                      e.setAttribute('_last_val', e.value);
                                                                                                                      e.setAttribute('autocomplete', 'off'); //must be off when calling onblur
                                                                                                                      e.onblur = function () {
                                                                                                                        var val = this.getAttribute('_last_val');
                                                                                                                        if (val != this.value) {
                                                                                                                          this.setAttribute('_last_val', this.value);
                                                                                                                          JsHttpRequest.request('_' + this.name + '_changed', this.form);
                                                                                                                        }
                                                                                                                      }
                                                                                                                    },
  'button[data-aspect="selector"], input[data-aspect="selector"]':                                                  function (e) {
    e.onclick = function () {
      passBack(this.getAttribute('rel'));
      return false;
    }
  },
  'select':                                                                                                         function (e) {
    if (e.onfocus == undefined) {
      e.onfocus = function () {
        Adv.Forms.saveFocus(this);
      };
      if ($(e).is('.combo,.combo2')) {
        _set_combo_select(e);
      }
    }
  },
  'a.printlink,button.printlink':                                                                                   function (e) {
    e.onclick = function () {
      Adv.Forms.saveFocus(this);
      JsHttpRequest.request(this, null, 60000);
      return false;
    }
  },
  'a':                                                                                                              function (e) { // traverse menu
    e.onkeydown = function (ev) {
      ev = ev || window.event;
      key = ev.keyCode || ev.which;
      if (key == 37 || key == 38 || key == 39 || key == 40) {
        Adv.Forms.moveFocus(key, e, document.links);
        ev.returnValue = false;
        return false;
      }
    }
  }

};
Behaviour.register(inserts);
Behaviour.addLoadEvent(Adv.Scroll.loadPosition);
