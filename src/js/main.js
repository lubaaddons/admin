require("../sass/styles.scss");

import $ from "jquery";
import Selectize from "./selectize_clear.js";

function hideoverlay() {
    $('.overlay').fadeOut(200, function(){
        $(this).remove();
    });
    $('.modal').fadeOut(200, function(){
        $(this).remove();
    });
}


$(function() {
    $('.adminfilter select').selectize();

    $('body').on('click', '[data-behaviour="close"]', function(e) {
        e.preventDefault();
        hideoverlay();
    });
    $('body').on('click', 'a[data-behaviour="ajax"]', function(e){
        e.preventDefault();
        ajaxRequest($(this).attr('href'));
    });

    // $('a[data-behaviour="ajax"]').first().click();

    function ajaxRequest(url)
    {
        $.ajax({
            url: url
        }).done(function(data){
            overlay(data);
        });
    }
    function overlay(content)
    {
        $('body').append('<div class="overlay" data-behaviour="close"></div>');
        $('.overlay').hide().fadeIn(200);
        $('body').append('<div class="modal">'+
            '<a class="modal_close" href="#" data-behaviour="close"><i class="ion-close-round"></i></a>'+
            '<div class="modal_content">' + content + '</div>'+
            '</div>');
        $('.modal').hide().fadeIn(200);
        // $('.modal .html').ckeditor(function() {},{'toolbar':'basic'});
        $('.modal select').selectize();
    }

});
// $(document).ready(function(){

//     <if !Input::get()>
//     // $('.adminfilter').hide();
//     </if>

//     $('.adminfilter_link').click(function(e){
//         e.preventDefault();
//         $('.adminfilter').slideToggle();
//     })
// });
