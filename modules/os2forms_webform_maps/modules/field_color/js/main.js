
(function ($, Drupal) {
  Drupal.behaviors.field_color = {
    attach: function (context, settings) {
      $(".input-field-color").once().spectrum({
        type: settings.type,
        showInput: settings.showInput,
        showInitial: settings.showInitial,
        allowEmpty: settings.allowEmpty,
        showButtons: settings.showButtons,
        showAlpha: settings.showAlpha,
        disabled: settings.disabled,
        localStorageKey: settings.localStorageKey,
        showPalette: settings.showPalette,
        showPaletteOnly: settings.showPaletteOnly,
        togglePaletteOnly: settings.togglePaletteOnly,
        showSelectionPalette: settings.showSelectionPalette,
        clickoutFiresChange: settings.clickoutFiresChange,
        containerClassName: settings.containerClassName,
        replacerClassName: settings.replacerClassName,
        preferredFormat: settings.preferredFormat,
        maxSelectionSize: settings.maxSelectionSize,
        locale: settings.locale,
        cancelText: settings.cancelText,
        chooseText: settings.chooseText,
        togglePaletteMoreText: settings.togglePaletteMoreText,
        togglePaletteLessText: settings.togglePaletteLessText,
        clearText: settings.clearText,
        noColorSelectedText: settings.noColorSelectedText,
        palette: settings.palette,
        selectionPalette: settings.selectionPalette,
        // color: settings.color,
      });
    }
  }
})(jQuery, Drupal);
