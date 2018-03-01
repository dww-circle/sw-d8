(function($) {
  Drupal.behaviors.menuExpander = {
    attach: function (context, settings) {
      
      $(".menu-trigger, .menu-expanded-close").click(function() {
        $(".region-expanded-menu").fadeToggle();
      });
      
    }
  };
})(jQuery);
