<?php

namespace Drupal\os2forms\Utility;

/**
 * Provides functionality to compare name strings.
 */
final class NameHelper
{
    /**
     * Compare first part of names ignoring case.
     *
     * The “first part” of a name is defined as the longest prefix consisting
     * only of letters.
     */
    public function compareNames(string $a, string $b)
    {
        $normA = $this->normalizeName($a);
        $normB = $this->normalizeName($b);

        // Keep only first name (excluding dash).
        $wordA = preg_replace('/[[:space:]-].*/', '', $normA);
        $wordB = preg_replace('/[[:space:]-].*/', '', $normB);

        return strcasecmp($wordA, $wordB);
    }

    /**
     * Normalize a name
     */
    public function normalizeName(string $name): string
    {
        // Convert to ASCII.
        $normalized = iconv("utf-8", "ascii//TRANSLIT", $name);

        // Remove leading and trailing whitespace.
        $normalized = trim($normalized);
        // Normalize whitespace.
        $normalized = preg_replace('/[[:space:]]+/', ' ', $normalized);

        // Remove everything that's not a letter, a space or a dash.
        $normalized = preg_replace('/[^[:alpha:] -]+/', '', $normalized);

        return $normalized;
    }
}
