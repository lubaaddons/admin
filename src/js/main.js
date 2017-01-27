require("../sass/styles.scss");

import $ from "jquery";
import Selectize from "./selectize_clear.js";


$(function() {
    $('.adminfilter select').selectize();
});
        // <script>
        // function hideoverlay()
        // {
        //     $('.overlay').fadeOut(200, function(){
        //         $(this).remove();
        //     });
        //     $('.ajax_container').fadeOut(200, function(){
        //         $(this).remove();
        //     });
        // }

        // $(document).ready(function(){
        //     $('body').on('click', '[data-behaviour="close"]', function(e) {
        //         e.preventDefault();
        //         hideoverlay();
        //     });
        //     $('body').on('click', 'a[data-behaviour="ajax"]', function(e){
        //         e.preventDefault();
        //         ajaxRequest($(this).attr('href'));
        //     });

        //     function ajaxRequest(url)
        //     {
        //         $.ajax({
        //             url: url
        //         }).done(function(data){
        //             overlay(data);
        //         });
        //     }
        //     function overlay(content)
        //     {
        //         $('body').append('<div class="overlay" data-behaviour="close"></div>');
        //         $('.overlay').hide().fadeIn(200);
        //         $('body').append('<div class="ajax_container"><div class="subcontainer">' + content + '</div></div>');
        //         $('.ajax_container').hide().fadeIn(200);
        //         $('.ajax_container .html').ckeditor(function() {},{'toolbar':'basic'});
        //         $('.ajax_container select').selectize();
        //     }

        //     <if !Input::get()>
        //     // $('.adminfilter').hide();
        //     </if>

        //     $('.adminfilter_link').click(function(e){
        //         e.preventDefault();
        //         $('.adminfilter').slideToggle();
        //     })
        // });

        // </script>
