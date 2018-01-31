jQuery(document).ready(function ($) {
    $.imagga = {
        'init' : function () {
            this.checkAdminForm();
        },
        'checkAdminForm' : function () {
            $('#imagga-admin-form').submit(function(e) {

                var formData = {
                    'authKey'              : $('textarea[name=imagga-auth]').val(),
                    'confidence'             : $('input[name=imagga-conf]').val(),
                    'postTypes'             : JSON.stringify($('select[name=post-types]').val()),
                };

                $.ajax(
                    {
                        url: requestpost.ajaxurl,
                        type: 'post',
                        data: {
                            action: 'imagga_ping_server',
                            formData: formData
                        },
                        success: function (result) {
                            data = JSON.parse(result);
                            if( data !== '' && typeof data !== 'undefined'){
                                /**
                                 * Handle errors.
                                 */
                                if( typeof data.err !== 'undefined'){
                                    $('.imagga-err').html(
                                        '<div class="response_box imagga_error">' + data.err + '</div>'
                                    );
                                    $('.usage').html(
                                        '<strong>0</strong> /0'
                                    );
                                } else {
                                    $('.imagga-err').html(
                                        '<div class="response_box">' + data.ok + '</div>'
                                    );
                                    var remaining = data.limit-data.remaining;
                                    $('.usage').html(
                                        '<strong>'+ remaining + '</strong> /' + data.limit
                                    );
                                }
                            }
                        }
                    }
                );

               e.preventDefault();
            });
        }
    };

    $.imagga.init();
});