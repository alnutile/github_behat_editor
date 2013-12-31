(function ($) {
    Drupal.behaviors.github_behat_editor_repo_admin = {

        attach: function (context) {
            $('button#update-repos-users').hover(function() { $(this).tooltip('show'); });
        }


    };

})(jQuery);