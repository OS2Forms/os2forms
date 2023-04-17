<?php

namespace Drupal\field_color\Component;

use Drupal\Component\Utility\Color;

/**
 * Validate color format.
 */
class FieldColorValidation extends Color {

  /**
   * Validate colors rgb, rgba, hex, hsl, hsla.
   */
  public static function all($color) {
    return preg_match('/^(?:#|0x)(?i:[a-f0-9]{3}|[a-f0-9]{6})\b|(?:rgb|hsl)a?\([^\)]*\)$/', $color) === 1;
  }

}
