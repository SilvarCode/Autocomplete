/**
 * ***************************************************************************
 *                      SilvarCode/Autocomplete Plugin
 * ***************************************************************************
 * @copyright   2022 SilvarCode / SilvarCode.com
 *              All rights reserved.
 * @link        https://silvarcode.com
 * @since       1.0.0
 * @license     MIT License - see LICENSE.txt for more details.
 *              Redistributions of files must retain the above notice.
 *              https://opensource.org/licenses/mit-license.php MIT License
 *
 */
jQuery(document).ready(function($){
    $('input.sc-autocomplete').each(function(){
        let autocomplete = $(this);
        let autocompleteId = autocomplete.attr('id');
        let autocompleteUrl = autocomplete.attr('data-url');
        let autocompleteOptions = $.parseJSON(autocomplete.attr('data-options')) || '{}';
        let autocompleteCache = {};
        let autocompleteShow = $('#' + autocompleteId + '-show');
        let autocompleteSelect = $('#' + autocompleteId + '-hidden');
        let autocompleteSelectMultiple = autocompleteSelect.attr('multiple') === 'multiple';

        enforceOptionsSelected();
        autocompleteShowHandler();

        function autocompleteShowHandler()
        {
            const selectedOptionsArray = autocompleteSelect.find('option').toArray();
            const optionValues = $(selectedOptionsArray).map(function() {
                return $(this).val();
            }).get();

            autocompleteShow.find('span.autocomplete-selection-item').each(function(){
                const showItem = $(this);
                const showItemButton = showItem.find('.remove-button');
                const showItemButtonIcon = showItemButton.find('i');
                const showItemButtonValue = showItemButtonIcon.attr('data-hidden-value');
                const isValueInArray = $.inArray(showItemButtonValue, optionValues) !== -1;

                if (!isValueInArray) {
                    showItem.remove();
                }

                showItemButtonIcon.on('click', function(){
                    let toRemove = $(this).closest('span.autocomplete-selection-item');
                    let toRemoveHiddenValue = $(toRemove).find('span.remove-button i').attr('data-hidden-value');
                    removeOption(toRemoveHiddenValue);
                    toRemove.remove();
                });
            });
        }

        function getOptionValues()
        {
            let values = [];
            autocompleteSelect.find('option').each(function(){
                values.push($(this).val());
            });

            return values;
        }

        /**
         * 
         */
        function checkOptionValueExists(value)
        {
            let result = false;
            getOptionValues().forEach(function(item){
                if (value === item) {
                    result = true;
                    return;
                }
            });

            return result;
        }

        /**
         * Removes option containing given value
         */
        function removeOption(value)
        {
            autocompleteSelect.find('option').each(function(){
                if (($(this).val() === value)) {
                    $(this).remove();
                }
            });
        }

        /**
         * Remove all options
         */
        function removeOptions()
        {
            autocompleteSelect.find('option').each(function(){
                $(this).remove();
            });
        }

        /**
         *
         * @param term
         * @param searchOptions
         * @returns {*[]}
         */
        function filterDataOptions(term, searchOptions)
        {
            let items = [];
            $.map(searchOptions, function (item) {
                let currentValueExists = checkOptionValueExists(item.value);
                if ((!currentValueExists) && (item.text.search(new RegExp(term, 'gi')) > -1)) {
                    if ((typeof item.label == 'undefined')) {
                        item.label = '<div class="row"><div class="col">'+ item.text +'</div></div>';
                    }
                    
                    items.push(item);
                }
            });
            return items;
        }
        
        /**
         * @param string s1 - the original string
         * @param string s2 - what to bold in the string
         * @return string with replace operation done
         */
        function boldSpanText(s1, s2)
        {
            s1 = String(s1);
            s2 = String(s2);
            return $(s1).text().replace(new RegExp(s2,"gi"),'<strong>$&</strong>');
        }
        
        /**
         * Set selected property of selected options
         *
         * @return void
         */
        function enforceOptionsSelected()
        {
            autocompleteSelect.find('option').each(function(){
                $(this).attr('selected', 'selected');
            });
        }
        
        autocomplete.autocomplete({
            search: function(event,ui) {
                //console.log(ui);
            },
            open: function() {
                $(this).removeClass("ui-corner-all").addClass("ui-corner-top");
            },
            close: function() {
                $(this).removeClass("ui-corner-top").addClass("ui-corner-all");
            },
            select: function(event, ui) {
                if (checkOptionValueExists(ui.item.value)) {
                    this.value = '';

                    return false;
                }
                
                // Only one option when not multiple
                if (!autocompleteSelectMultiple) {
                    removeOptions();
                }

                autocompleteSelect.append(
                    $(
                        '<option></option>'
                    ).attr(
                        'value', 
                        ui.item.value
                    ).attr(
                        'text', 
                        ui.item.text
                    ).attr(
                        'selected',
                        'selected'
                    )
                );
                
                span = $('<span class="autocomplete-selection-item"></span>');
                span.append('<span class="text"></span>');
                span.append('<span class="remove-button"><i class="fa fa-times remove-icon"></i></span>');
                span.find('span.text').html(ui.item.label);
                span.find('span.remove-button i').attr('data-hidden-value', ui.item.value);
                
                autocompleteShow.append(span);
                enforceOptionsSelected();
                autocompleteShowHandler();
                this.value = '';

                return false;
            },
            source: function(request, response ) {
                if (request.term in autocompleteCache) {
                    response(autocompleteCache[request.term]);
                    return;
                }
                
                // Only one option when not multiple
                if (!autocompleteSelectMultiple) {
                    removeOptions();
                }
                
                searchCurrentItems = filterDataOptions(
                    request.term, 
                    autocompleteOptions
                );
                
                if ((searchCurrentItems.length > 0)) {
                    autocompleteCache[request.term] = searchCurrentItems;
                    return response($.map(searchCurrentItems, function (item) {
                        return item;
                    }));
                }
                
                requestOptions = {}
                requestOptions.url = autocompleteUrl;
                requestOptions.type = 'GET';
                requestOptions.data = request;
                requestOptions.context = null;
                requestOptions.beforeSend = function(xhr) {
                    autocomplete.addClass('autocomplete-loading-input');
                };
                $.ajax(requestOptions).done(function(data) {
                    data = (
                        (typeof data !== 'string')
                    ) ? (
                        JSON.stringify(data)
                    ) : (
                        data
                    );
                    
                    data = $.parseJSON(data);
                    autocompleteCache[request.term] = data;
                    autocomplete.removeClass('autocomplete-loading-input');
                    response($.map(data, function (item) {
                        return item;
                    }));
                });
            },
            minLength: 2
        })
        .data("ui-autocomplete")._renderItem = function (ul, item) {
            let content = "<div>" + boldSpanText(item.label, this.term) + "</div>";

            return $("<li></li>").data("ui-autocomplete-item", item).append(content).appendTo(ul);
        };
    });
});