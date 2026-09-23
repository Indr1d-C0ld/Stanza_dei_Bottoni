<?php

declare(strict_types=1);

namespace App\Simulazione\Fasi;

use App\Simulazione\ContestoTick;
use App\Simulazione\EsitoFase;
use App\Simulazione\Fase;

/**
 * Fase 09 — Il feed pubblico e gli scandali.
 *
 * LEGGE:      il giornale narrativo accumulato dalle fasi 02-07, la conoscenza
 * SCRIVE:     le notizie, gli scandali
 * INVARIANTE: nel feed pubblico entra solo ciò che ha attribuzione sufficiente
 *
 * È l'unico punto del codice che rende pubblico un segreto. Le altre fasi
 * accumulano annotazioni in un giornale privato; qui si decide che cosa il
 * mondo viene a sapere — e chi paga per averlo fatto.
 *
 * Perché ci sia una notizia servono due cose diverse: che il fatto sia
 * avvenuto, e che qualcuno possa dirlo. Un colpo di stato si vede da sé; una
 * campagna di disinformazione no, finché un servizio non la dimostra.
 */
final class Fase09Stampa implements Fase
{
    /** Fatti che si vedono da sé: non hanno bisogno di essere attribuiti. */
    private const PUBBLICI = [
        'rivoluzione', 'colpo_di_stato', 'cambio_governo', 'elezione',
        'conflitto', 'embargo', 'attacco', 'mediazione', 'guerra', 'pace',
        // Una talpa scoperta e' per definizione pubblica: e' il processo che la
        // rende tale. Il sospetto interno no — quello resta dentro le mura.
        'talpa_scoperta', 'reclutamento_denunciato',
        // Fatti che nessuno riesce a tenere nascosti, e che prima restavano
        // nel giornale interno del tick senza mai arrivare in cronaca: un
        // incidente armato, un test atomico (l'ordigno «provato» e' per
        // definizione rilevato), un disarmo annunciato, un alleato che entra
        // in guerra o si volta dall'altra parte, sanzioni dichiarate.
        'incidente', 'bomba_ottenuta', 'bomba_posata',
        'garanzia_onorata', 'garanzia_tradita', 'restrizioni',
    ];

    public function codice(): string { return '09'; }
    public function nome(): string   { return 'Il feed pubblico e gli scandali'; }

    public function esegui(ContestoTick $c): EsitoFase
    {
        $mondo = $c->mondo;
        if ($mondo === null) {
            return EsitoFase::nonImplementata();
        }

        $pubblicate = 0;
        $scandali = 0;

        foreach ($c->giornale() as $voce) {
            $genere = (string) $voce['genere'];

            if (in_array($genere, self::PUBBLICI, true)) {
                $mondo->notizie[] = ['tick' => $c->tick, 'genere' => $genere, 'dati' => $voce['dati']];
                $pubblicate++;
                continue;
            }

            // Un'operazione coperta diventa notizia solo quando qualcuno l'ha
            // dimostrata — ed è allora che comincia a costare davvero.
            if ($genere === 'attribuzione') {
                $mondo->notizie[] = ['tick' => $c->tick, 'genere' => 'scandalo', 'dati' => $voce['dati']];
                $pubblicate++;
                $scandali += $this->scandalo($voce['dati'], $mondo, $c) ? 1 : 0;
                continue;
            }

            // Un'operazione sventata fa notizia solo se si può dire di chi era.
            if ($genere === 'operazione_sventata' && ($voce['dati']['mandante'] ?? '') !== 'ignoti') {
                $mondo->notizie[] = ['tick' => $c->tick, 'genere' => 'operazione_sventata', 'dati' => $voce['dati']];
                $pubblicate++;
            }
        }

        if (count($mondo->notizie) > 4000) {
            $mondo->notizie = array_slice($mondo->notizie, -2000);
        }

        return new EsitoFase(['notizie' => $pubblicate, 'scandali' => $scandali]);
    }

    /**
     * Farsi cogliere con le mani nel sacco costa in casa, non solo fuori.
     *
     * È la controparte interna del contraccolpo diplomatico: la fase 02 fa
     * pagare il prezzo nei rapporti con il paese colpito, qui si paga davanti
     * alla propria opinione pubblica. Nel gioco originale è la stessa cosa che
     * accade al Presidente quando una sonda nella stanza di un consigliere
     * viene scoperta: "la notizia di una violazione della sicurezza raggiunge i
     * media, con conseguente scandalo e perdita di punti di popolarità".
     *
     * @param array<string,mixed> $dati
     */
    private function scandalo(array $dati, $mondo, ContestoTick $c): bool
    {
        $nome = (string) ($dati['mandante'] ?? '');
        foreach ($mondo->nazioni as $n) {
            if ($n->nome !== $nome) {
                continue;
            }
            // Quanto costa dipende da quanto la stampa è libera di dirlo: in uno
            // Stato chiuso lo scandalo non arriva a chi dovrebbe pagarlo.
            $libertà = max(0.15, 1.0 - ($n->statoPolizia - 1) * 0.30);
            $n->legittimita = max(0.0, $n->legittimita - 2.5 * $libertà);
            $n->clamoreSociale += 5.0 * $libertà;
            $n->scandaliSubiti++;
            // E la fama di scorrettezza cresce agli occhi di tutti.
            $n->reputazioneSporca += 1.5;
            return true;
        }
        return false;
    }
}
