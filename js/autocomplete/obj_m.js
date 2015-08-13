/*jslint  browser: true, white: true, plusplus: true */
/*global $, countries */

$(function () {
    'use strict';

    var countriesArray = $.map(objects, function (value, key) { return { value: value, data: key }; });

    // Initialize autocomplete with local lookup:
    $('#autocomplete_m').devbridgeAutocomplete({
        lookup: countriesArray,
        minChars: 2,
        onSelect: function (suggestion) {
            document.location = "/medicine/90/" + suggestion.data;
			//$('#selection').html('Вы выбрали: ' + suggestion.value);
			//oform.object_id.value = suggestion.data;
        },
        showNoSuggestionNotice: false
    });
    
});