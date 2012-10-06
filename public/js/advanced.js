var Adv = {};
(function (window, $, undefined) {
  var Adv = {

    fieldsChanged: 0,
    lastXhr:       '',
    o:             {$content: $("#content"), tabs: {}, wrapper: $("#wrapper"), header: $("#header"), body: $("body"), autocomplete: {}}
  };
  (function () {
    $.widget("custom.catcomplete", $.ui.autocomplete, {
      _renderMenu: function (ul, items) {
        var self = this, currentCategory = "";
        $.each(items, function (index, item) {
          if (item.category && item.category != currentCategory) {
            ul.append("<li class='ui-autocomplete-category'>" + item.category + "</li>");
            currentCategory = item.category;
          }
          self._renderItemData(ul, item);
        });
      }
    });
    var extender = $.extend;
    this.extend = function (object) {extender(Adv, object)};
    this.loader = {
      off:     function () {
        Adv.o.header.removeClass('spinner warning progress');
        console.timeEnd('ajax');
        $(document.body).removeClass('wait');
      },
      warning: function () {
        Adv.loader.off();
        Adv.o.header.addClass('warning');
        $(document.body).removeClass('wait');
        console.timeEnd('ajax');
      },
      on:      function (tout) {
        Adv.o.header.removeClass('warning');
        tout = tout > 50000 ? 'progress' : 'spinner';
        Adv.o.header.addClass(tout);
        $(document.body).addClass('wait');
        Adv.ScrollDetect.loaded = false;
        Adv.Forms.setFocus(true);
        console.time('ajax');
      }}
    this.o.header.ajaxStart(Adv.loader.on).ajaxStop(Adv.loader.off);
  }).apply(Adv);
  window.Adv = Adv;
})(window, jQuery);
Adv.extend({
             ScrollDetect: (function () {
               return {
                 loaded: false,
                 off:    function () {
                   Adv.ScrollDetect.loaded = true;
                   window.removeEventListener('scroll', Adv.ScrollDetect.off, false);
                 }
               };
             }())
           });
window.addEventListener('scroll', Adv.ScrollDetect.off, false);
Adv.extend({  headerHeight: Adv.o.header.height(),
             msgbox:        $('#msgbox').ajaxError(function (event, request, settings) {
               var status;
               if (request.statusText == "abort") {
                 return;
               }
               status = {
                 status:  256,
                 message: "Request failed: " + settings.url + "<br>"
               };
               Adv.Status.show(status);
             }).ajaxComplete(function (event, request) {
                               Behaviour.apply();
                               try {
                                 var data = $.parseJSON(request.responseText);
                                 if (data && data.status) {
                                   Adv.Status.show(data.status);
                                 }
                               }
                               catch (e) {
                                 return false;
                               }
                               return undefined;
                             }),
             Status:        {
               msgboxTimeout: null,
               show:          function (status) {
                 var text = '', type;
                 if (status === undefined) {
                   status = {status: null, message: ''};
                 }
                 if (status.status === 'redirect') {
                   window.onunload = null;
                   return window.location.href = status.message;
                 }
                 if (status.html) {
                   text = status.html;
                 }
                 else {
                   if (status.message) {
                     switch (Number(status.status)) {
                       case 1024:
                         status.class = 'info_msg';
                         break;
                       case 512:
                         status.class = 'warn_msg';
                         type = 'warning';
                         break;
                       case 256:
                       case 8:
                       case 1:
                       case -1:
                         status.class = 'err_msg';
                         type = 'error';
                         break;
                       case 61438:
                       default:
                         status.class = 'success_msg';
                         break;
                     }
                     if (status.var && type && Adv.Forms.setFocus(status.var)) {
                       Adv.Forms.error(status.var, status.message, type);
                       return;
                     }
                     text = '<div class="' + status.class + '">' + status.message + '</div>';
                   }
                 }
                 window.clearTimeout(Adv.Scroll.msgboxTimeout);
                 if (text) {
                   Adv.msgbox.css({opacity: 0}).html(text);
                   Adv.Status.open();
                 }
               }, //
               open:          function () {
                 var height = 0;
                 Adv.msgbox.children().each(function () { height += $(this).outerHeight(true)});
                 if (Adv.msgbox.height() > 0) {
                   setTimeout(function () {
                     Adv.msgbox.css({opacity: 1, height: height});
                     Adv.o.body.css('padding-top', Adv.headerHeight + height);
                   }, 200);
                 }
                 else {
                   Adv.msgbox.css({opacity: 1, height: height});
                   Adv.o.body.css('padding-top', Adv.headerHeight + height);
                 }
                 Adv.Scroll.msgboxTimeout = setTimeout(Adv.Status.close, 15000);
                 Adv.Forms.setFocus(Adv.msgbox[0]);
               }, //
               close:         function () {
                 Adv.o.body.css('padding-top', Adv.headerHeight);
                 Adv.msgbox.css({opacity: 0, height: 0});
               }//
             }, //
             openWindow:    function (url, title, width, height) {
               width = width || 900;
               height = height || 600;
               var left = (screen.width - width) / 2, top = (screen.height - height) / 2;
               return window.open(url, title, 'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',screenX=' + left + ',screenY=' + top + ',status=no,scrollbars=yes');
             }, //
             openTab:       function (url) {window.open(url, '_blank');},
             hoverWindow:   {
               _init:  false, init: function (width, height) {
                 Adv.hoverWindow.width = width || 600;
                 Adv.hoverWindow.height = height || 600;
                 if (Adv.hoverWindow._init) {
                   return;
                 }
                 Adv.hoverWindow._init = true;
                 Adv.o.$content.off('click.open mouseenter.open').on('click.open mouseenter.open mouseleave.open', 'div .openWindow,td .openWindow', function (e) {
                   if (e.type == 'click') {
                     Adv.openWindow(this.href, this.target, Adv.hoverWindow.width, Adv.hoverWindow.height);
                     return false;
                   }
                   if (e.type == 'mouseenter') {
                     if (Adv.o.popupCurrent) {
                       window.clearTimeout(Adv.o.popupCurrent);
                     }
                     Adv.o.popupEl = this;
                     Adv.o.popupParent = $(this).parent();
                     Adv.o.popupCurrent = window.setTimeout(Adv.popupWindow, 750);
                   }
                   if (e.type == 'mouseleave') {
                     window.clearTimeout(Adv.o.popupCurrent);
                   }
                 })
               },
               loaded: function () {
                 Adv.o.popupWindow.show();
                 var height = Adv.o.popupWindow[0].contentWindow.document.body.clientHeight + 10;
                 var top = ($(window).height() / 2 - (height / 2));
                 if (height > Adv.hoverWindow.height) {
                   top = 50;
                   height = Adv.hoverWindow.height
                 }
                 var left = ($(window).width() / 2 - Adv.hoverWindow.width / 2);
                 Adv.o.popupWindow.css('height', height);
                 Adv.o.popupDiv.css({width: Adv.hoverWindow.width, 'height': height, 'left': left, 'top': top});
               }},
             popupWindow:   function () {
               if (Adv.o.popupWindow) {
                 Adv.o.popupWindow.parent().remove();
               }
               Adv.o.popupWindow = $("<iframe>", {
                 src:    Adv.o.popupEl.href + '&frame=1',
                 width:  Adv.hoverWindow.width,
                 onload: 'Adv.hoverWindow.loaded()'
               }).css({background: 'white'}).hide();
               Adv.o.popupDiv = $('<div>', {
                 id:     'iframePopup',
                 width:  100,
                 height: 100}).html(Adv.o.popupWindow).on('mouseleave',function () { $(this).remove(); }).appendTo(Adv.o.wrapper).position({my: "center center", at: "center center", of: document.body});
             },
             TabMenu:       (function () {
               var deferreds = [];
               return{
                 init:  function (id, ajax, links, page) {
                   Adv.o.tabs[id] = $('#tabs' + id);
                   Adv.o.tabs[id].tabs();
                   if (page) {
                     Adv.TabMenu.page(id, page);
                   }
                   if (deferreds[id] !== undefined) {
                     deferreds[id].resolve();
                   }
                 }, //
                 page:  function (id, page) {
                   if (page) {
                     Adv.o.tabs[id].tabs('active', page);
                   }
                 },
                 defer: function (id) {
                   if (deferreds[id] === undefined) {
                     deferreds[id] = $.Deferred();
                   }
                   return deferreds[id];
                 }
               }
             }()),
             Forms:         (function () {
               var focusOff = false, tooltip, hidden = [], tooltiptimeout, focusonce, focus, menu = {
                 current:    null,
                 closetimer: null,
                 open:       function (el) {
                   menu.close();
                   menu.current = el.find('ul').stop(true, true).show('');
                 },
                 close:      function () {
                   if (menu.current !== null) {
                     menu.current.stop(true, true).hide('');
                   }
                   menu.current = null;
                 }
               }, _setFormValue = function (el, value, disabled, isdefault) {
                 var exists;
                 if (!el) {
                   return false;
                 }
                 if (typeof disabled === 'boolean') {
                   el.disabled = disabled;
                 }
                 if (el.tagName === 'SELECT') {
                   value = String(value);
                   for (var i = 0, opts = el.options; i < opts.length; ++i) {
                     if (opts[i].value === value) {
                       exists = opts[i];
                       break;
                     }
                   }
                   if (!exists || el.value === null || value.length === 0) {
                     exists = $(el).find('option:first')[0];
                   }
                   if (exists) {
                     if (isdefault) {
                       $(el).find('option').prop('defaultSelected', false);
                       exists.defaultSelected = true
                     }
                     exists.selected = true;
                   }
                   return el;
                 }
                 if (el.type === 'checkbox') {
                   value = (!(value === 'false' || !value || value == 0));
                   el.value = el.checked = value;
                   if (isdefault) {
                     el.defaultChecked = value;
                   }
                   return el;
                 }
                 if (el.tagName !== 'SELECT') {
                   if (String(value).length === 0) {
                     value = '';
                   }
                   el.value = value;
                 }
                 if (isdefault) {
                   if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                     $(el).attr('value', value);
                     el.defaultValue = value;
                   }
                 }
                 return el;
               };
               Adv.o.wrapper.on('mouseenter', '.dropdown-toggle.auto, .btn-group', function () {
                 if (menu.closetimer) {
                   window.clearTimeout(menu.closetimer);
                   menu.closetimer = null;
                 }
                 else {
                   menu.open($(this).parent());
                 }
               });
               Adv.o.wrapper.on('mouseleave', '.btn-group', function () {
                 menu.closetimer = window.setTimeout(menu.close, 300);
               });
               Adv.o.wrapper.on('click', '.btn-split', function () {
                 var $clicked = $(this).parent().find('a').eq(0) //
                   , url = $clicked.attr('href')//
                   , target = $clicked.attr('target');
                 switch (target) {
                   case '_parent':
                     window.parent.location.href = url;
                     break;
                   case '_blank':
                     Adv.openWindow(url);
                     break;
                   default:
                     window.location.href = url;
                 }
                 return false;
               });
               Adv.o.wrapper.on('focus.datepicker', ".datepicker", function () {
                 $(this).datepicker({numberOfMonths:    3,
                                      showButtonPanel:  true,
                                      showCurrentAtPos: 2,
                                      nextText:         '',
                                      prevText:         '',
                                      dateFormat:       'dd/mm/yy'}).off('focus.datepicker');
               });
               return {
                 findInputEl:     function (id) {
                   var els = document.getElementsByName(id);
                   if (!els.length) {
                     els = [document.getElementById(id)];
                   }
                   return els;
                 },
                 setFormValue:    function (id, value, disabled) {
                   var isdefault, els, values = {};
                   if (value !== null && typeof value === 'object') {
                     if (value.value === undefined) {
                       $.each(value, function (k, v) {
                         if (v !== null && typeof v === 'object') {
                           values[id + '[' + k + ']'] = v;
                         }
                         else {
                           values[k] = v;
                         }
                       });
                       return Adv.Forms.setFormValues(values);
                     }
                     value = value.value;
                   }
                   els = Adv.Forms.findInputEl(id);
                   isdefault = !!arguments[3];
                   $.each(els, function (k, el) {
                     _setFormValue(el, value, disabled, isdefault);
                   });
                   return els;
                 },
                 setFormValues:   function (data) {
                   var focused = false;
                   $.each(data, function (k, v) {
                     var el, label;
                     el = Adv.Forms.setFormValue(k, v);
                     if (!el || el.type === 'hidden') {
                       return;
                     }
                     if (!focused && v.focus !== undefined) {
                       focused = focusonce = k;
                     }
                     if (v.hidden === true) {
                       hidden[k] = $(el);
                       hidden[k].hide().closest('label').hide();
                     }
                     else {
                       if (hidden[k] !== undefined) {
                         hidden[k].show().closest('label').show();
                         delete hidden[k];
                       }
                     }
                   });
                 },
                 setFormDefault:  function (id, value, disabled) {
                   this.setFormValue(id, value, disabled, true);
                 },
                 autocomplete:    function (searchField, type, callback, data) {
                   var els = Adv.Forms.findInputEl(searchField)//
                     , $this = $(els) //
                     , blank = {id: 0, value: ''};
                   if (els[0].type === 'hidden') {
                     return;
                   }
                   if (!$.isFunction(callback)) {
                     var idField = Adv.Forms.findInputEl(callback);
                     callback = function (data) {
                       console.log($(idField));
                       if ($(idField).length) {
                         $(idField).val(data.id);
                       }
                       $this.val(data.value);
                       if (!$this.is('.nosubmit')) {
                         JsHttpRequest.request(els[0]);
                       }
                       return false;
                     }
                   }
                   Adv.o.autocomplete[searchField] = $this;
                   $this.catcomplete({
                                       minLength: 2,
                                       delay:     400,
                                       autoFocus: true,
                                       source:    function (request, response) {
                                         var $this = Adv.o.autocomplete[searchField];
                                         $this.off('change.catcomplete');
                                         $this.data('default', null);
                                         if ($this.data().catcomplete.previous == $this.val()) {
                                           return false;
                                         }
                                         request['type'] = type;
                                         request['data'] = data;
                                         Adv.lastXhr = $.getJSON('/search', request, function (data) {
                                           if (!$this.data('active')) {
                                             data = blank;
                                             return false;
                                           }
                                           $this.data('default', data[0]);
                                           response(data);
                                         });
                                       },
                                       select:    function (event, ui) {
                                         $this.data('default', null);
                                         if (callback(ui.item, event, this) === false) {
                                           return false;
                                         }
                                       },
                                       focus:     function () {return false;}});
                   $this.on({
                              blur:             function () {$(this).data('active', false); }, //
                              catcompleteclose: function (event) {
                                if (this.value.length > 1 && $this.data().catcomplete.selectedItem === null && $this.data()['default'] !== null) {
                                  if (callback($this.data()['default'], event, this) !== false) {
                                    $this.val($this.data()['default'].label);
                                  }
                                }
                                $this.data('default', null)
                              }, //
                              focus:            function () {
                                $(this).data('active', true).on('change.catcomplete', function () {
                                  $(this).catcomplete('search', $this.val());
                                })
                              }, //
                              paste:            function () {
                                var $this = $(this);
                                window.setTimeout(function () {$this.catcomplete('search', $this.val())}, 1)
                              }, //
                              change:           function (event) {
                                if (this.value === '') {
                                  callback(blank, event, this);
                                }
                              }});
                   $this.css({'z-index': '2'});
                   if (document.activeElement === $this[0]) {
                     $this.data('active', true);
                   }
                 },
                 priceFormat:     function (post, num, dec, label, color) {
                   var sign, decsize, cents, el = label ? document.getElementById(post) : document.getElementsByName(post)[0];
                   //num = num.toString().replace(/\$|\,/g,'');
                   if (isNaN(num)) {
                     num = "0";
                   }
                   sign = (num == (num = Math.abs(num)));
                   if (dec < 0) {
                     dec = 2;
                   }
                   decsize = Math.pow(10, dec);
                   num = Math.floor(num * decsize + 0.50000000001);
                   cents = num % decsize;
                   num = Math.floor(num / decsize).toString();
                   for (i = cents.toString().length; i < dec; i++) {
                     cents = "0" + cents;
                   }
                   for (var i = 0; i < Math.floor((num.length - (1 + i)) / 3); i++) {
                     num = num.substring(0, num.length - (4 * i + 3)) + user.ts + num.substring(num.length - (4 * i + 3));
                   }
                   num = ((sign) ? '' : '-') + num;
                   if (dec != 0) {
                     num = num + user.ds + cents;
                   }
                   if (label) {
                     el.innerHTML = num;
                   }
                   else {
                     el.value = num;
                   }
                   if (color) {
                     el.style.color = (sign) ? '' : '#FF0000';
                   }
                 },
                 getAmount:       function (doc, label) {
                   var val;
                   if (label) {
                     val = document.getElementById(doc).innerHTML;
                   }
                   else {
                     val = typeof(doc) === "string" ? document.getElementsByName(doc)[0].value : doc.value;
                   }
                   val = val.replace(new RegExp('\\' + user.ts, 'g'), '');
                   val = +val.replace(new RegExp('\\' + user.ds, 'g'), '.');
                   return isNaN(val) ? 0 : val;
                 },
                 setFocus:        function (name, byId) {
                   var el, pos, $el;
                   if (name === false) {
                     focusOff = true;
                     return;
                   }
                   if (name === true) {
                     focusOff = false;
                   }
                   if (focusOff === true) {
                     return;
                   }
                   if (typeof(name) == 'object') {
                     el = name;
                   }
                   else {
                     if (!name) { // page load/ajax update
                       if (focusonce) {
                         name = focusonce;
                         focusonce = null;
                       }  // last focus set in onfocus handlers
                       if (!name && focus) {
                         name = focus;
                       }  // last focus set in onfocus handlers
                       if (!name && document.forms.length) {  // no current focus (first page display) -  set it from from last form
                         var cur = document.getElementsByName('_focus')[document.forms.length - 1];
                         if (cur) {
                           name = cur.value;
                         }
                       }
                     }
                     if (byId || !(el = document.getElementsByName(name)[0])) {
                       el = document.getElementById(name);
                     }
                   }
                   if (!el) {
                     return false;
                   }
                   // The timeout is needed to prevent unpredictable behaviour on IE & Gecko.
                   // Using tmp var prevents crash on IE5
                   $el = $(el);
                   pos = $el.offset().top - 100;
                   if (tooltip) {
                     tooltip.tooltip('destroy');
                   }
                   if (!$el.is(':visible')) {
                     return false;
                   }
                   setTimeout(function () {
                     Adv.Scroll.to(pos, 300);
                     if (el.focus) {
                       el.focus();
                     }
                     if (el.select) {
                       el.select();
                     }
                     el = null;
                   }, 0);
                   return true;
                 }, saveFocus:    function (e) {
                   focusonce = e.name || e.id;
                   var h = document.getElementById('hints');
                   if (h) {
                     h.style.display = e.title && e.title.length ? 'inline' : 'none';
                     h.innerHTML = e.title ? e.title : '';
                   }
                 },
                 //returns the absolute position of some element within document
                 elementPos:      function (e) {
                   var res = {};
                   res.x = 0;
                   res.y = 0;
                   if (e !== null) {
                     res.x = e.offsetLeft;
                     res.y = e.offsetTop;
                     var offsetParent = e.offsetParent;
                     var parentNode = e.parentNode;
                     while (offsetParent !== null && offsetParent.style.display != 'none') {
                       res.x += offsetParent.offsetLeft;
                       res.y += offsetParent.offsetTop;
                       // the second case is for IE6/7 in some doctypes
                       if (offsetParent != document.body && offsetParent != document.documentElement) {
                         res.x -= offsetParent.scrollLeft;
                         res.y -= offsetParent.scrollTop;
                       }
                       //next lines are necessary to support FireFox problem with offsetParent
                       if (navigator.userAgent.match(/gecko/i)) {
                         while (offsetParent != parentNode && parentNode !== null) {
                           res.x -= parentNode.scrollLeft;
                           res.y -= parentNode.scrollTop;
                           parentNode = parentNode.parentNode;
                         }
                       }
                       parentNode = offsetParent.parentNode;
                       offsetParent = offsetParent.offsetParent;
                     }
                   }
                   // parentNode has style.display set to none
                   if (parentNode != document.documentElement) {
                     return null;
                   }
                   return res;
                 },
                 resetHighlights: function () {
                   $(".ui-state-highlight").removeClass("ui-state-highlight");
                   Adv.fieldsChanged = 0;
                   Adv.Events.onLeave();
                 },
                 stateModified:   function (field) {
                   var value, defaultValue;
                   if (field.is(':checkbox')) {
                     value = field.prop('checked');
                     field.val(value);
                     defaultValue = field[0].defaultChecked;
                   }
                   else {
                     if (field.is('select')) {
                       value = field[0].options[field[0].selectedIndex].selected;
                       defaultValue = field[0].options[field[0].selectedIndex].defaultSelected;
                     }
                     else {
                       value = field.val();
                       defaultValue = field[0].defaultValue;
                     }
                   }
                   if (defaultValue == value && field.hasClass("ui-state-highlight")) {
                     Adv.fieldsChanged--;
                     if (Adv.fieldsChanged === 0) {
                       Adv.Forms.resetHighlights();
                     }
                     else {
                       field.removeClass("ui-state-highlight");
                     }
                     return;
                   }
                   else {
                     if (defaultValue != value && !field.hasClass("ui-state-highlight")) {
                       Adv.fieldsChanged++;
                       if (field.prop('disabled')) {
                         return Adv.fieldsChanged;
                       }
                       var fieldname = field.addClass("ui-state-highlight").attr('name');
                     }
                   }
                   $("[name='" + fieldname + "']").addClass("ui-state-highlight");
                   Adv.Events.onLeave("Continue without saving changes?");
                   return Adv.fieldsChanged;
                 },
                 error:           function (field, error, type) {
                   var $error;
                   if (tooltip) {
                     tooltip.tooltip('destroy').removeClass('error');
                   }
                   window.clearTimeout(tooltiptimeout);
                   if (type === undefined) {
                     $error = $(error);
                     if ($error.is('.err_msg')) {
                       type = 'error';
                     }
                     if ($error.is('.warn_msg')) {
                       type = 'warning';
                     }
                   }
                   field = $(Adv.Forms.findInputEl(field));
                   if (type === undefined || !field.is('input:not(input[type=hidden]),textarea,select')) {
                     Adv.Status.show({html: error});
                     return;
                   }
                   error = ($error) ? $error.text() : error;
                   tooltip = field.addClass('error').tooltip({title: function () {return error;}, trigger: 'manual', placement: 'right', class: type}).tooltip('show');
                   tooltiptimeout = setTimeout(function () {
                     if (tooltip) {
                       tooltip.removeClass('error').tooltip('destroy');
                     }
                   }, 3000);
                 }
               }
             })(),
             Scroll:        (function () {
               return{
                 focus:        null,
                 elementName:  null,
                 to:           function (position, duration) {
                   if (duration === undefined) {
                     $(window).scrollTop(position);
                     return;
                   }
                   $('html,body').animate({scrollTop: position}, {queue: false, duration: duration, easing: 'easeInSine'});
                 }, set:       function (el) {
                   Adv.Scroll.focus = $(el).position().top - scrollY;
                   Adv.Scroll.elementName = $(el).attr('name');
                 },
                 loadPosition: function (force) {
                   if (Adv.ScrollDetect.loaded && force === undefined) {
                     return;
                   }
                   if (typeof(Adv.Scroll.focus) == 'number' && typeof Adv.Scroll.elementName == 'string') {
                     var pos = $(document.getElementsByName(Adv.Scroll.elementName)[0]).position().top;
                     Adv.Scroll.to(pos - Adv.Scroll.focus);
                     Adv.Scroll.focus = Adv.Scroll.elementName = Adv.ScrollDetect.loaded = true;
                     return;
                   }
                   Adv.Forms.setFocus();
                 }


               };
             })(),
             Events:        (function () {
               var events = [], onload = false, toClean = false, firstBind = function (s, t, a) {
                 $(s).bind(t, a);
               };
               return {
                 bind:    function (selector, types, action) {
                   events[events.length] = {s: selector, t: types, a: action};
                   firstBind(selector, types, action);
                 },
                 onload:  function (actions, clean) {
                   var c = !!onload;
                   onload = actions;
                   if (c) {
                     return;
                   }
                   onload();
                   if (clean !== undefined) {
                     toClean = clean;
                   }
                 },
                 rebind:  function () {
                   if (toClean) {
                     toClean();
                   }
                   if (onload) {
                     onload();
                   }
                   $.each(events, function (k, v) {
                     firstBind(v.s, v.t, v.a);
                   });
                 },
                 onLeave: function (msg) {
                   if (msg) {
                     window.onbeforeunload = function () {
                       return msg;
                     };
                   }
                   else {
                     window.onbeforeunload = function () {
                       return null;
                     };
                   }
                 }
               }
             }()),
             postcode:      (function () {
               var sets = [];
               return {
                 add:   function (set, city, state, code) {
                   sets[set] = {city: $(document.getElementsByName(city)), state: $(document.getElementsByName(state)), postcode: $(document.getElementsByName(code))}
                 },
                 fetch: function (data, item, ui) {
                   var set = $(ui).data("set");
                   data = data.value.split('|');
                   sets[set].city.val(data[0]).trigger('change');
                   sets[set].state.val(data[1]).trigger('change');
                   sets[set].postcode.val(data[2]).trigger('change');
                   return false;
                 }
               };
             }()),
             inView:        function (el) {
               var rect = el.getBoundingClientRect();
               return (
                 rect.top >= 0 && rect.left >= 0 && rect.bottom <= window.innerHeight && rect.right <= window.innerWidth
                 )
             }         });
$(function () {
  var tabs = $("#tabs")//
    , topmenu = $('#topmenu,#tabs>ul') //
    , prevFocus = false//
    , changing = 0//
    , closeTimer//
    , current = topmenu.find('.active')//
    , topLevel = topmenu.children('li')//
    , closeMenu = function () {
      topLevel.removeClass('hover').find('li').removeClass('hide');
      if (prevFocus) {
        prevFocus.focus();
        prevFocus = false;
      }
      changing = 0;
    }//
    , checkMenu = function () {
      var $this = $(this), next = $this.next();
      if (!next.length) {
        return;
      }
      if (( !Adv.inView(next[0]))) {
        current.find('li').not('.hide').first().addClass('hide');
        if (next.hasClass('title')) {
          checkMenu.apply(next[0]);
        }
      }
      else if ($this.not('.hide')) {
        next = $this.prev();
        next.removeClass('hide').prev().filter('.title').removeClass('hide');
        if (next.hasClass('title')) {
          checkMenu.apply(next[0]);
        }
      }
    }//
    , currentChanged = function (next, skip) {
      var links;
      if (next === undefined) {
        next = current;
      }
      else {
        if (!next.length) {
          return
        }
        else {
          current.removeClass('hover').find('li').removeClass('hide');
        }
      }
      next.addClass('hover');
      links = next.find('a');
      if (!skip) {
        links.eq(0).focus();
      }
      links.eq(1).focus();
      next.trigger('mouseenter');
    } //
    , keyNav = function (event) {
      changing = 2;
      switch (event.which) {
        case 37:
          currentChanged(current.prev());
          return false;
        case 39:
          currentChanged(current.next());
          return false;
        case 38:
          checkMenu.apply(this.parentElement);
          $(this).parent().prevUntil('', ':has(a)').eq(0).find('a').focus();
          return false;
        case 40:
          checkMenu.apply(this.parentElement);
          $(this).parent().nextUntil('', ':has(a)').eq(0).find('a').focus();
          return false;
        case 9:
          currentChanged((event.shiftKey === true ? current.prev() : current.next()));
          break;
        case 27:
          closeMenu();
          break;
        default:
      }
    };
  topLevel.on('mouseenter', function () {
    var $this = $(this);
    topLevel.removeClass('hover');
    current = $this.addClass('hover');
    if ($this.find('a').length > 1) {
      $this.find('a').eq(1).focus();
    }
  });
  topLevel.delegate('li', 'mouseenter', checkMenu);
  topLevel.children('a').on({
                              focus:    function () {
                                currentChanged($(this).parent(), true);
                              }, //
                              focusout: function () {$(this).parent().removeClass('hover').find('li').removeClass('hide')}, //
                              keydown:  keyNav});
  topmenu.find('ul').find('a').on({
                                    keydown:    keyNav,
                                    mouseenter: function () {
                                      if (changing < 2) {
                                        this.focus();
                                      }
                                    }
                                  });
  tabs.on({ //
            mouseleave: function () {
              if (changing < 2) {
                closeTimer = setTimeout(closeMenu, 400);
              }
              tabs.off('mousemove.tabs');
            }, //
            mouseenter: function () {
              clearTimeout(closeTimer);
              tabs.on('mousemove.tabs', function () {changing = 1;});
            }
          });
  Adv.Status.open();
  $(document).on('focusout', ':input',function () {
    prevFocus = $(this);
  }).on('keydown', function (event) {
          if (event.which === 83 && event.altKey === true) {
            if (prevFocus) {
              closeMenu();
            }
            else {
              prevFocus = document.activeElement;
              currentChanged(current);
            }
          }
        });
});
