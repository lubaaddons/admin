import $ from "jquery";
import Selectize from 'selectize';

Selectize.define( 'clear_selection', function ( options ) {
    var self = this;
    var settings = $.extend({
        title: 'Alle'
    }, options);

    //Overriding because, ideally you wouldn't use header & clear_selection simultaneously
    self.plugins.settings.dropdown_header = {
        title: settings.title
    };
    this.require( 'dropdown_header' );

    self.setup = (function () {
        var original = self.setup;

        return function () {
            original.apply( this, arguments );
            this.$dropdown.find('.selectize-dropdown-header').addClass('clear-selection').on('mousedown', function ( e ) {
                self.setValue( '' );
                self.close();
                self.blur();

                return false;
            });
        }
    })();
});

export default Selectize;