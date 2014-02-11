(function ($) {
    Drupal.behaviors.github_behat_editor_clone = {

        attach: function (context) {
            var token = Drupal.behat_editor.get_token();

//            $('div.gitrepo-choose a').click(function(e){
//                e.preventDefault();
//                var filename = $('input[name=filename]').val();
//                $('#githubModal .filename').text(filename);
//                $('#githubModal').modal();
//                e.preventDefault();
//            });

            $('div.gitrepo-choose a').click(function(e){
                $('#githubModal').modal('hide');
                e.preventDefault();
                var path = $(this).attr('href');
                var filename = Math.round(new Date().getTime() / 1000) + '.feature';
                var path_with_file = path.substr(1) + '/' + filename;
                var service_path = path_with_file.split('/');
                var scenario = $('ul.scenario:eq(0) > li').not('.ignore');
                var scenario_array = Drupal.behat_editor.make_scenario_array(scenario);
                var module = 'behat_github';
                var url = $('a#edit-clone-test').attr('href');
                var parameters = {
                    "scenario": scenario_array,
                    "filename": filename,
                    "module": module,
                    "path": service_path
                };

                var data = Drupal.behat_editor.action('POST', token, parameters, url);
                console.log(data);
                if(data.error == 0) {
                    window.location.replace("/admin/behat/view/" + path_with_file);
                }
                Drupal.behat_editor.renderMessage(data);
            });
        }


    };

})(jQuery);