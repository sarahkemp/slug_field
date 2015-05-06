/**
 * Slug Field
 * 
 * @author SarahKemp (thesarahkemp@gmail.com)
 **/

        (function($, undefined) {
            "use strict";
            var scrub = function(val) {
                    if(val){
                        val = val.trim().toLowerCase().replace(/[^\w ]+/g, '').replace(/ +/g, '-');
                    }
                    return val;
                };

	    var bindEvent = function() {
                  var field_name = $(this).attr('data-slug-field');
                  var field_name_to_mimic = $(this).attr('data-field-to-mimic');
                  var field_to_mimic = $('[name="fields[' + field_name_to_mimic + ']"]');
                  var slug_field = $(this);

                  field_to_mimic.on('keyup change', function(){
                       if (!slug_field.attr('readonly')) {
                            slug_field.val(scrub(this.value));
                       }
                  });

	    };


            var init = function() {
		   $('[data-slug-field]').each(bindEvent);
                };

            $(init);

        })(jQuery);
