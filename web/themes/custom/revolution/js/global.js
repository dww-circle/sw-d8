(function($) {
  Drupal.behaviors.menuExpander = {
    attach: function (context, settings) {
      
      $(".menu-trigger, .menu-expanded-close", context).click(function() {
        $(".region-expanded-menu").fadeToggle();
      });
      
    }
  };
})(jQuery);
