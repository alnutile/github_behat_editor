(function ($) {
    Drupal.behaviors.github_behat_editor_app = {

        attach: function (context) {
            var token = Drupal.behat_editor.get_token();

            $('a.sync-button').click( function (e) {

                e.preventDefault();
                var git_path = window.location.pathname;
                $.ajax(
                    {
                        type: 'POST',
                        url: '/admin/behat/github/sync',
                        data: {
                            current_url: git_path
                        }
                    }
                ).done(function(data){
                       alert(data);
                         window.location.reload(true);
                    });


            });
        }


    };

})(jQuery);