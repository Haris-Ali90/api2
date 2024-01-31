/*
* @ author = Adnan
* @ Author email = adnannadeem1994@gmail.com
* @ custom joeyco js
* */


/*--------------
helper functions
---------------*/

//-----select box fuction open------//

function make_option_selected(el , val="")
{
    $(el).val(val);
}

function make_multi_option_selected(el , val="")
{
    var data_array = val.split(",");
    $(el).val(data_array);
}

function make_option_selected_trigger(el , val="")
{
    $(el).val(val);
    $(el).change()
}

//-----select box fuction close------//
/*loader show hide function*/
function showLoader() {
    $('.loader-mian-wrap').addClass('show');
}

function hideLoader() {
    $('.loader-mian-wrap').removeClass('show');
}

function toggleLoader() {
    $('.loader-mian-wrap').toggleClass('show');
}
