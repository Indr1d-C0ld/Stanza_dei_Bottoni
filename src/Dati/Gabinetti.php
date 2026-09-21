<?php

declare(strict_types=1);

namespace App\Dati;

use App\Nucleo\Caso;

/** Fabbrica di gabinetti, personaggi e fazioni. */
final class Gabinetti
{
    /** Bacino onomastico per le potenze giocabili; per le altre vale la regione. */
    private const BACINO = [
        'USA' => 'anglosassone', 'GBR' => 'anglosassone',
        'FRA' => 'latino', 'DEU' => 'germanico', 'RUS' => 'slavo',
        'IND' => 'indiano', 'CHN' => 'sinitico', 'JPN' => 'giapponese',
        'TUR' => 'turco', 'IRN' => 'persiano', 'ISR' => 'ebraico',
        'SAU' => 'arabo', 'IDN' => 'sudestasiatico', 'BRA' => 'iberico',
    ];

    private const PER_REGIONE = [
        'EUR' => 'latino', 'NAM' => 'anglosassone', 'SAM' => 'iberico',
        'CAM' => 'iberico', 'AFR' => 'africano', 'MEO' => 'arabo',
        'ASC' => 'slavo', 'ASS' => 'indiano', 'ASE' => 'sudestasiatico',
        'OCE' => 'anglosassone',
    ];

    private const VULNERABILITA = [
        'debiti di gioco', 'un conto all\'estero mai dichiarato',
        'una relazione che non può ammettere', 'un fratello nel giro sbagliato',
        'una tesi comprata', 'un incarico ottenuto con una raccomandazione pesante',
        'un vecchio arresto insabbiato', 'un figlio con guai giudiziari',
        'una consulenza pagata da chi non doveva', 'un ricovero che non risulta',
    ];

    private const AGENDE = [
        'contenere la spesa militare', 'aprire al blocco avverso',
        'riarmare a qualunque costo', 'proteggere i propri interessi economici',
        'chiudere la stampa critica', 'liberalizzare l\'economia',
        'riprendersi il territorio perduto', 'restare fuori da ogni guerra',
        'sostituire il capo', 'conservare le cose come stanno',
    ];

    /** @param array<string,array{nomi:list<string>,cognomi:list<string>}> $bacini */
    public static function perNazione(Nazione $n, array $bacini, Caso $caso, int $tick): Gabinetto
    {
        $g = new Gabinetto($n->iso3);
        $seme = crc32($n->iso3);
        $i = 0;

        foreach (array_keys(Gabinetto::RUOLI) as $ruolo) {
            $g->poltrone[$ruolo] = new Poltrona(
                ruolo: $ruolo,
                titolare: self::personaggio($n, $bacini, $caso, $seme, $i, $tick),
                potere: 35.0 + 40.0 * $caso->frazione('gab_potere', $seme, $i),
                lealta: 45.0 + 45.0 * $caso->frazione('gab_lealta', $seme, $i),
                insediatoTick: $tick,
            );
            $i++;
        }
        // Il capo ha sempre più potere di chiunque altro, all'inizio.
        $g->poltrone['capo']->potere = 75.0 + 20.0 * $caso->frazione('gab_capo', $seme, 0);

        $g->fazioni  = self::fazioni($n, $caso, $seme);
        $g->coesione = 45.0 + 35.0 * $caso->frazione('gab_coesione', $seme, 0);

        return $g;
    }

    /** @param array<string,array{nomi:list<string>,cognomi:list<string>}> $bacini */
    public static function personaggio(Nazione $n, array $bacini, Caso $caso, int $seme, int $i, int $tick): Personaggio
    {
        $chiave = self::BACINO[$n->iso3] ?? self::PER_REGIONE[$n->regione] ?? 'latino';
        $b = $bacini[$chiave] ?? reset($bacini);

        // Si pesca senza rimpiazzo dentro lo stesso gabinetto: con dieci nomi
        // e otto poltrone, l'estrazione indipendente produceva tre ministri di
        // nome Chiara e sembrava una svista.
        $quantiNomi = count($b['nomi']);
        $quantiCognomi = count($b['cognomi']);
        $baseNome = $caso->intero('pg_nome', $seme + $tick, 0, 0, $quantiNomi - 1);
        $baseCognome = $caso->intero('pg_cognome', $seme + $tick, 0, 0, $quantiCognomi - 1);
        $nome = $b['nomi'][($baseNome + $i * 3) % $quantiNomi]
            . ' ' . $b['cognomi'][($baseCognome + $i * 7) % $quantiCognomi];

        // Un apparato solido seleziona gente più competente e meno disposta a
        // scorciatoie; uno Stato debole no. Ma la varianza individuale resta
        // grande: è il punto di avere delle persone invece che dei parametri.
        $solidita = $n->maturita / 255.0;

        $vulnerabilita = [];
        $quante = $caso->intero('pg_quante', $seme, $i, 1, 3);
        for ($k = 0; $k < $quante; $k++) {
            $vulnerabilita[] = self::VULNERABILITA[
                $caso->intero('pg_vuln', $seme + $k * 31, $i, 0, count(self::VULNERABILITA) - 1)];
        }

        return new Personaggio(
            nome: $nome,
            etica: (int) max(1, min(6, round(4.5 - 2.0 * $solidita + $caso->rumore('pg_etica', $seme, $i, 1.6)))),
            ambizione: (int) max(1, min(6, round(3.5 + $caso->rumore('pg_ambizione', $seme, $i, 2.0)))),
            competenza: max(0.1, min(1.0, 0.35 + 0.35 * $solidita + $caso->rumore('pg_comp', $seme, $i, 0.25))),
            vulnerabilita: array_values(array_unique($vulnerabilita)),
            eta: $caso->intero('pg_eta', $seme, $i, 42, 71),
        );
    }

    /** @return list<Fazione> */
    private static function fazioni(Nazione $n, Caso $caso, int $seme): array
    {
        // La stessa struttura per regimi diversissimi: cambiano i nomi e i pesi.
        $nomi = $n->statoPolizia <= 2
            ? ['il partito di maggioranza', 'i finanziatori', 'la stampa', 'l\'apparato dello Stato']
            : ['i servizi', 'i vertici militari', 'la cerchia del capo', 'gli interessi economici'];

        $f = [];
        foreach ($nomi as $k => $nome) {
            $f[] = new Fazione(
                nome: $nome,
                forza: 15.0 + 25.0 * $caso->frazione('faz_forza', $seme, $k),
                favore: 20.0 + 50.0 * $caso->frazione('faz_favore', $seme, $k),
                agenda: self::AGENDE[$caso->intero('faz_agenda', $seme, $k, 0, count(self::AGENDE) - 1)],
            );
        }
        return $f;
    }
}
