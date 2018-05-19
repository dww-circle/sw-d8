(function($) {
  Drupal.behaviors.menuExpander = {
    attach: function (context, settings) {
      
      $(".menu-trigger, .menu-expanded-close", context).click(function() {
      
        var headerHeight = $(".header").outerHeight();
        var expandedMenu = $(".region-expanded-menu");
  
        expandedMenu.fadeToggle();
        expandedMenu.css('top', headerHeight);
        
      });
      
    }
  };
})(jQuery);
