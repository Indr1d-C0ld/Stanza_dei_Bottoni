<?php

declare(strict_types=1);

/** Funzioni di comodo condivise. */

if (!function_exists('mb_str_pad')) {
    /** Ripiego per PHP < 8.3: riempimento che conta i caratteri, non i byte. */
    function mb_str_pad(string $testo, int $lunghezza, string $riempimento = ' '): string
    {
        $mancanti = $lunghezza - mb_strlen($testo);
        return $mancanti > 0 ? $testo . str_repeat($riempimento, $mancanti) : $testo;
    }
}
