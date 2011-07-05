var Adv;
(function(window, undefined) {
   var Adv = {
      $content: $("#content"),
      loader: $("<div/>").attr('id', 'loader'),
      fieldsChanged: 0
   };
   (function() {
      var extender = jQuery.extend, toInit = [];
      Adv.loader.hide().prependTo(Adv.$content).ajaxStart(function() {$(this).show() } ).ajaxStop(function() { $(this).hide()});
      this.extend = function(object) { extender(Adv, object); };
      jQuery('body').ajaxSuccess(function(e,xhr,settings) {
         console.log(xhr);
      })
   }).apply(Adv);
   window.Adv = Adv;
})(window);