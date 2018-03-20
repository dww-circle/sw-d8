/**
 * @file
 * Attaches behaviors for the 'Recent articles' block.
 */

(function ($, Drupal) {
  Drupal.behaviors.swRecentArticles = {
    attach: function attach(context) {

      // Locate the elements we're going to need to be operating on.
      var dateLabels = $(context).find('.recent-articles-dates ul');
      var dateTabs = $(context).find('.recent-articles-tabs');

      // Function to show a specific tab and the articles from that day.
      // The argument is the id on the label link.
      var showTab = function(labelId) {
        // Split the ID into an array so we can grab just the date numbers.
        var dateArray = labelId.split('-');
        // Find the tab we want to reveal.
        var tabId = '#recent-articles-tab-' + dateArray[3];
        var activeTab = dateTabs.find(tabId);
        // Hide everything (so we definitely hide whatever was open).
        dateTabs.children().each(function () {
          $(this).hide();
          $(this).addClass('js-hide');
        });
        // Reveal the tab we want to see.
        activeTab.show();
        activeTab.removeClass('js-hide');
      }

      // When we first attach this behavior, find the first link.
      var firstDate = dateLabels.find("li").first().find("a");
      // Set it active.
      firstDate.addClass("active");
      // And reveal the tab containing its articles.
      showTab(firstDate.attr('id'));

      // Bind click() functions to all the date label links.
      dateLabels.find("li a").click(function() {
        // Remove the "active" class from all links.
        dateLabels.find("li a").removeClass("active");
        // Set ourselve active.
        $(this).addClass("active");
        // Reveal our articles.
        showTab(this.id);
        // Return false so the default browser behavior doesn't happen, the
        // page doesn't scroll, etc.
        return false;
      });

    }
  }
})(jQuery, Drupal);
