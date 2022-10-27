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
 jQuery(document).ready(function($){$('input.sc-autocomplete').each(function(){let autocomplete=$(this);let autocompleteId=autocomplete.attr('id');let autocompleteUrl=autocomplete.attr('data-url');let autocompleteCache={};let autocompleteShow=$('#'+autocompleteId+'-show');let autocompleteSelect=$('#'+autocompleteId+'-hidden');function getOptionValues(){values=[];autocompleteSelect.find('option').each(function(){values.push($(this).val())});return values}
 function checkOptionValueExists(value){let result=!1;getOptionValues().forEach(function(item){if(value==item){result=!0;return}});return result}
 function removeOption(value){autocompleteSelect.find('option').each(function(){if(($(this).val()==value)){$(this).remove()}})}
 function removeOptions(){autocompleteSelect.find('option').each(function(){$(this).remove()})}
 autocomplete.autocomplete({search:function(event,ui){},open:function(){$(this).removeClass("ui-corner-all").addClass("ui-corner-top")},close:function(){$(this).removeClass("ui-corner-top").addClass("ui-corner-all")},select:function(event,ui){if(checkOptionValueExists(ui.item.value)){this.value='';return!1}
 if((autocompleteSelect.attr('multiple')!=='multiple')){removeOptions()}
 autocompleteSelect.append($('<option></option>').attr('value',ui.item.value).attr('text',ui.item.text).attr('selected','selected'));span=$('<span class="autocomplete-selection-item"></span>');span.append('<span class="text"></span>');span.append('<span class="remove-button"><i class="fa fa-times remove-icon"></i></span>');span.find('span.text').html(ui.item.label);span.find('span.remove-button i').attr('data-hidden-value',ui.item.value);autocompleteShow.append(span);span.find('span.remove-button i').each(function(){$(this).on('click',function(){let toRemove=$(this).closest('span.autocomplete-selection-item');let toRemoveHiddenValue=$(toRemove).find('span.remove-button i').attr('data-hidden-value');removeOption(toRemoveHiddenValue);toRemove.remove()})});this.value='';return!1},source:function(request,response){if(request.term in autocompleteCache){response(autocompleteCache[request.term]);return}
 requestOptions={}
 requestOptions.url=autocompleteUrl;requestOptions.type='GET';requestOptions.data=request;requestOptions.context=null;requestOptions.beforeSend=function(xhr){autocomplete.addClass('autocomplete-loading-input')};$.ajax(requestOptions).done(function(data){data=((typeof data!=='string'))?(JSON.stringify(data)):(data);data=$.parseJSON(data);autocompleteCache[request.term]=data;autocomplete.removeClass('autocomplete-loading-input');response($.map(data,function(item){return item}))})},minLength:2}).data("ui-autocomplete")._renderItem=function(ul,item){let searchedTerm=String(item.label).replace(new RegExp(this.term,"gi"),"<span class='bold'>$&</span>");return $("<li></li>").data("ui-autocomplete-item",item).append("<div>"+searchedTerm+"</div>").appendTo(ul)}})})