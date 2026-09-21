<?php

declare(strict_types=1);

namespace App\Nucleo;

use DateTimeImmutable;

/**
 * Le date, scritte come si scrivono in italiano.
 *
 * Sta qui e non sparso per le viste per due ragioni. La prima e' che il
 * formato va scelto una volta: giorno, mese, anno, con le barre — e finche'
 * c'era un date('Y-m-d') qua e un date('d/m') la', l'intestazione del sito
 * mostrava «2028-11-27» mentre il banco dell'arbitro mostrava «27/11». La
 * seconda e' che questo gioco ha DUE tempi — l'orologio vero, che serve a
 * sapere quando e' stata fatta una cosa, e il calendario del mondo, che va a
 * settimane per tick — e confonderli e' facilissimo.
 *
 * L'origine del calendario di gioco e' il 5 gennaio 2026, un lunedi'. Ogni
 * tick e' una settimana.
 */
final class Calendario
{
    /** Il primo giorno del mondo: un lunedi', perche' le settimane cominchino bene. */
    public const ORIGINE = '2026-01-05';

    /**
     * Quanti giorni dura un tick.
     *
     * Non e' una manopola di taratura ed e' giusto che stia qui: e' legato a
     * `tempo.tick_per_anno = 52`, perche' cinquantadue settimane fanno un anno.
     * In calibrazione c'era una chiave `tempo.giorni_per_tick` che diceva la
     * stessa cosa e che nessuno leggeva — due numeri per un vincolo solo, con
     * la possibilita' di scordarsi di allinearli.
     */
    public const GIORNI_PER_TICK = 7;

    /** Che giorno e', nel mondo, al tick dato. */
    public static function dataDiTick(int $tick): DateTimeImmutable
    {
        return (new DateTimeImmutable(self::ORIGINE))
            ->modify('+' . ($tick * self::GIORNI_PER_TICK) . ' days');
    }

    /** La data del mondo al tick dato, in italiano. */
    public static function tick(int $tick): string
    {
        return self::dataDiTick($tick)->format('d/m/Y');
    }

    /** Come la scrive il database: ISO, perche' li' dentro si ordina. */
    public static function tickIso(int $tick): string
    {
        return self::dataDiTick($tick)->format('Y-m-d');
    }

    /**
     * Una data qualunque — quel che arriva dal database, o niente — scritta in
     * italiano. Torna il trattino quando non c'e' niente da scrivere, perche'
     * una casella vuota si legge male.
     */
    public static function data(?string $quando, string $seNiente = '—'): string
    {
        if ($quando === null || trim($quando) === '') {
            return $seNiente;
        }
        $t = strtotime($quando);
        return $t === false ? $seNiente : date('d/m/Y', $t);
    }

    /** Come sopra, ma con l'ora: per le cose fatte oggi, il giorno non basta. */
    public static function dataOra(?string $quando, string $seNiente = '—'): string
    {
        if ($quando === null || trim($quando) === '') {
            return $seNiente;
        }
        $t = strtotime($quando);
        return $t === false ? $seNiente : date('d/m/Y H:i', $t);
    }

    /** Da un momento dell'orologio vero. */
    public static function daIstante(int $istante, bool $conOra = false): string
    {
        return date($conOra ? 'd/m/Y H:i' : 'd/m/Y', $istante);
    }
}
