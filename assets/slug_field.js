/**
 * Slug Field
 * 
 * @author SarahKemp (thesarahkemp@gmail.com)
 **/

        (function($, undefined) {
            "use strict";
            var
                    scrub = function(val) {
                val = val.trim().toLowerCase().replace(/[^\w ]+/g, '').replace(/ +/g, '-');
                return val;
            },
                    init = function() {
                var field_name = $('[data-slug-field]').attr('data-slug-field'),
                        field_name_to_mimic = $('[data-field-to-mimic]').attr('data-field-to-mimic'),
                        field_to_mimic = $('[name="fields[' + field_name_to_mimic + ']"]'),
                        slug_field = $('[name="fields[' + field_name + ']"]');

                field_to_mimic.keyup(function() {
                    if (!slug_field.attr('readonly')) {
                        slug_field.val(scrub(this.value));
                    }
                });
            };

            $(init);

        })(jQuery);