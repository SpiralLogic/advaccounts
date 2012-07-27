/**
 * Created by JetBrains PhpStorm.
 * User: complex
 * Date: 11/22/10
 * Time: 3:30 AM
 * To change this template use File | Settings | File Templates.
 */
;
//noinspection JSUnusedLocalSymbols
(function (window, $, undefined)
{
  var $current, Searchboxtimeout, menuTimeout, Adv = window.Adv, sidemenu = {}, searchInput = $('<input/>').attr({type:'text', value:'', size:14, maxlength:18}).data({'id':'', url:''}), $search = $("#search"), $quickCustomer = $('#quickCustomer'), $quickSupplier = $('#quickSupplier');
  (function ()
  {
    var $this = this, $wrapper = $("#wrapper"), previous;
    this.menu = $("#sidemenu").accordion({autoHeight:false, active:false, event:"mouseenter"}).draggable().show();
    this.sidemenuHide = function ()
    {
      $this.menu.clearQueue().animate({right:' -10em', opacity:'.75'}, 500).accordion({collapsible:false, active:false});
    };
    this.sidemenuOn = function ()
    {
      $this.menu.animate({right:'-10em', opacity:1}, 300).accordion("enable").hover(function ()
                                                                                    {
                                                                                      window.clearTimeout(menuTimeout);
                                                                                      $(this).stop().animate({right:'1em', opacity:'1'}, 500).accordion({collapsible:false, active:false});
                                                                                    }, function ()
      {
        menuTimeout = window.setTimeout($this.sidemenuHide, 1000);
      });
    };
    this.sidemenuOn();
    this.sidemenuOff = function ()
    {
      $this.menu.unbind('mouseenter mouseleave').accordion("disable");
      $this.menu.find("h3").one("click", function ()
      {
        $wrapper.empty().append(previous);
      })
    };
    this.doSearch = function ()
    {
      var term = searchInput.val();
      Adv.lastXhr = $.post(searchInput.data("url"), { 'q':term, limit:true }, $this.showSearch);
    };
    this.showSearch = function (data)
    {
      previous = $wrapper.contents().detach();
      window.onpopstate = function (event) { console.log(event);};
      history.pushState({}, 'Search Results', searchInput.data("url") + 'q=' + searchInput.val());
      $wrapper.html(data);
      Adv.showStatus({html:$('.msgbox').detach().html()});
    };
    $search.delegate("li", "click", function ()
    {
      searchInput.trigger('blur');
      $current = $(this).hide();
      $this.sidemenuOff();
      searchInput.data({'id':$current.data('href'), url:$current.data('href')}).insertBefore($current).focus();
      return false;
    });
    $search.delegate('input', "change blur keyup paste", function (event)
    {
      if (Adv.lastXhr && event.type == 'keyup') {
        if (event.keyCode == 13) {
          window.clearTimeout(Searchboxtimeout);
          Searchboxtimeout = window.setTimeout($this.doSearch, 1);
          return false;
        }
        Adv.lastXhr.abort();
      }
      if (event.type == 'paste') {
        Searchboxtimeout = window.setTimeout($this.doSearch, 1);
        return;
      }
      if (event.type != "blur" && searchInput.val().length > 1 && event.which < 123) {
        window.clearTimeout(Searchboxtimeout);
        Searchboxtimeout = window.setTimeout($this.doSearch, 1000);
      }
      if (event.type != 'keyup') {
        searchInput.val('').detach();
        $current.show();
        $this.sidemenuOn();
        menuTimeout = window.setTimeout($this.sidemenuHide, 1000);
      }
    });
    $quickCustomer.focus(function () { $this.sidemenuOff()}).blur(function ()
                                                                  {
                                                                    searchInput.trigger('blur');
                                                                  }).autocomplete({
                                                                                    source:   function (request, response)
                                                                                    {
                                                                                      Adv.lastXhr = $.getJSON('/contacts/customers.php', request, function (data, status, xhr)
                                                                                      {
                                                                                        if (xhr === Adv.lastXhr) {
                                                                                          response(data);
                                                                                        }
                                                                                      })
                                                                                    },
                                                                                    minLength:2,
                                                                                    select:   function (event, ui)
                                                                                    {
                                                                                      window.location.href = '/contacts/customers.php?id=' + ui.item.id;
                                                                                    }
                                                                                  });
    $quickSupplier.focus(function () { $this.sidemenuOff()}).blur(function () {searchInput.trigger('blur')}).autocomplete({
                                                                                                                            source:   function (request, response)
                                                                                                                            {
                                                                                                                              Adv.lastXhr = $.getJSON('/contacts/suppliers.php', request, function (data, status, xhr)
                                                                                                                              {
                                                                                                                                if (xhr === Adv.lastXhr) {
                                                                                                                                  response(data);
                                                                                                                                }
                                                                                                                              })
                                                                                                                            },
                                                                                                                            minLength:2,
                                                                                                                            select:   function (event, ui)
                                                                                                                            {
                                                                                                                              window.location.href = '/contacts/suppliers.php?id=' + ui.item.id;
                                                                                                                            }
                                                                                                                          });
  }).apply(sidemenu);
  Adv.sidemenu = sidemenu;
})(window, jQuery);
