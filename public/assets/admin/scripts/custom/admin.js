var Admin = function () {

    return {
        //main function to initiate the module
        init: function () {
            $('#header-logout-link, #leftnav-logout-link').click(function() {
                event.preventDefault();
                document.getElementById('logout-form').submit();
            });
        }

    };

}();
