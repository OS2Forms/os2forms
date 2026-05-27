(function ($, Drupal) {
    // Overrides splitValues() function found in core/misc/autocomplete.js for
    // DAWA elements. Danish addresses contain commas. Original splitValues()
    // method splits upon commas. Combined with default extractLastTerm this
    // results in unexpected behavior when using autocomplete with respect to
    // danish addresses. Inspired by
    // https://www.drupal.org/project/drupal/issues/2821181#comment-12012538.
    Drupal.autocomplete.splitValues = function (value) {
        // Check if the current autocomplete is for a focused DAWA address field
        if ($('.ui-autocomplete-input:focus').closest('.os2forms-dawa-address').length) {
            // For DAWA address fields, return the entire value as a single value
            return [ value.trim() ];
        }
        // For other fields, use the original behavior
        return Drupal.autocomplete.splitValues(value);
    };
})(jQuery, Drupal);
