<?php

declare(strict_types=1);

namespace App\Simulazione\Fasi;

use App\Simulazione\ContestoTick;
use App\Simulazione\EsitoFase;
use App\Simulazione\Fase;

/**
 * Fase 07 — Conflitti in corso.
 *
 * LEGGE:      le guerre aperte, le potenze militari, i trattati
 * SCRIVE:     attrito, esiti, integrità dei garanti
 * INVARIANTE: l'attrito che ciascuno infligge è funzione della PROPRIA forza,
 *             non di quella altrui
 *
 * La fase 05 si occupa delle guerre interne, questa di quelle fra Stati. Il
 * sistema di combattimento è deliberatamente povero — come nell'originale, dove
 * la guerra si riduce a due barre, forza e morale — perché il gioco non parla
 * di battaglie: parla di conseguenze.
 *
 * Qui però accade una cosa che finora non accadeva mai: **le garanzie vengono
 * messe alla prova**. Chi ha firmato un trattato di difesa con il paese invaso
 * deve scegliere se entrare in guerra o perdere integrità, ed è il meccanismo
 * con cui Crawford rende costose le promesse.
 */
final class Fase07Conflitto implements Fase
{
    public function codice(): string { return '07'; }
    public function nome(): string   { return 'Conflitti in corso'; }

    public function esegui(ContestoTick $c): EsitoFase
    {
        $mondo = $c->mondo;
        if ($mondo === null) {
            return EsitoFase::nonImplementata();
        }

        $tickAnno = $c->calibrazione->numero('tempo.tick_per_anno', 52.0);
        $perTick  = 1.0 / $tickAnno;
        $attrito  = $c->calibrazione->numero('insurrezione.attrito_anno', 0.25) * $perTick;

        $aperte = 0;
        $chiuse = 0;
        $garanzie = 0;

        foreach ($mondo->guerre as $k => &$g) {
            $a = $mondo->nazioni[$g['aggressore']] ?? null;
            $d = $mondo->nazioni[$g['difensore']]  ?? null;
            if ($a === null || $d === null) {
                unset($mondo->guerre[$k]);
                continue;
            }
            $aperte++;

            // --- le garanzie, alla prima settimana ------------------------
            if ($c->tick === $g['inizio'] + 1) {
                $garanzie += $this->metteAllaProvaLeGaranzie($g, $mondo, $c);
            }

            // --- attrito ---------------------------------------------------
            // Il difensore combatte in casa: a parità di forza logora di più.
            // Il difensore combatte in casa, e si mobilita: la resistenza di
            // un paese invaso cresce nei primi mesi molto piu' di quanto
            // qualunque pianificatore si aspetti.
            $durataAnni = ($c->tick - $g['inizio']) / $tickAnno;
            $mobilitazione = 1.35 + 0.9 * min(1.0, $durataAnni);
            $forzaA = $a->potenzaGoverno();
            $forzaD = $d->potenzaGoverno() * $mobilitazione;

            $logoraA = $forzaD * $attrito;
            $logoraD = $forzaA * $attrito;
            $g['morti'] += ($logoraA + $logoraD) * 60.0;

            $this->logora($a, $logoraA, $forzaA);
            $this->logora($d, $logoraD, $forzaD);

            $a->netPeace = 6;
            $d->netPeace = 6;

            // --- il fronte interno -----------------------------------------
            // All'inizio la guerra compatta, poi logora: è la curva che ogni
            // governo in guerra conosce e quasi nessuno sa prevedere.
            $durata = $durataAnni;
            $effetto = $durata < 0.5 ? 6.0 : -4.0 - 3.0 * min(3.0, $durata);
            $d->legittimita = max(0.0, min(100.0, $d->legittimita + $effetto * $perTick * 2.0));
            $a->legittimita = max(0.0, min(100.0, $a->legittimita + ($effetto - 2.0) * $perTick * 2.0));

            // --- esito ------------------------------------------------------
            $rapporto = $forzaA / max(1.0, $d->potenzaGoverno() * $mobilitazione);
            $stanchezza = $durata > 3.0;

            // Nessuna conquista nel giro di una settimana: anche la piu'
            // squilibrata delle invasioni richiede mesi di terreno percorso.
            $abbastanzaLunga = $durataAnni > 1.0;

            if ($rapporto > 3.5 && $abbastanzaLunga) {
                $this->conclude($g, $mondo, $c, 'conquista');
                unset($mondo->guerre[$k]);
                $chiuse++;
            } elseif ($abbastanzaLunga && ($rapporto < 0.8 || ($stanchezza && $rapporto < 1.4))) {
                $this->conclude($g, $mondo, $c, 'ritirata');
                unset($mondo->guerre[$k]);
                $chiuse++;
            }
        }
        unset($g);
        $mondo->guerre = array_values($mondo->guerre);

        return new EsitoFase(['guerre' => $aperte, 'concluse' => $chiuse, 'garanzie' => $garanzie]);
    }

    /** Il danno si scarica su uomini ed equipaggiamento, in proporzione. */
    private function logora($n, float $danno, float $forza): void
    {
        $quota = $forza > 0 ? min(0.4, $danno / $forza) : 0.0;
        $n->equipaggiamento *= 1.0 - $quota * 0.9;
        $n->soldati = max(500.0, $n->soldati * (1.0 - $quota * 0.45));
    }

    /**
     * Chi aveva garantito il difensore deve decidere: entrare o perdere la
     * faccia. È il momento in cui l'integrità smette di essere un numero.
     *
     * @param array<string,mixed> $g
     */
    private function metteAllaProvaLeGaranzie(array $g, $mondo, ContestoTick $c): int
    {
        $messe = 0;
        foreach ($mondo->elenco() as $garante) {
            if ($garante->iso3 === $g['difensore'] || $garante->iso3 === $g['aggressore']) {
                continue;
            }
            $r = $mondo->relazioni->fra($garante->iso3, $g['difensore']);
            if ($r === null || $r->obbligo < 64) {
                continue;
            }
            $messe++;

            // Si onora un impegno se si è forti abbastanza e se ci si tiene
            // abbastanza. Altrimenti si guarda altrove, e si paga.
            $capace = $garante->potenzaGoverno() > $mondo->nazioni[$g['aggressore']]->potenzaGoverno() * 0.4;
            $volenteroso = $r->affinita > 75.0 || $r->obbligo >= 96;

            if ($capace && $volenteroso) {
                $mondo->nazioni[$g['difensore']]->equipaggiamento += $garante->pil * 0.02;
                $garante->netPeace = max($garante->netPeace, 4);
                $c->annota('garanzia_onorata', [
                    'garante'  => $garante->nome,
                    'protetto' => $mondo->nazioni[$g['difensore']]->nome,
                ]);
            } else {
                // La formula di Crawford: l'integrità cala in proporzione
                // all'impegno che non si è onorato.
                $garante->integrita *= 1.0 - ($r->obbligo / 128.0);
                $c->annota('garanzia_tradita', [
                    'garante'  => $garante->nome,
                    'protetto' => $mondo->nazioni[$g['difensore']]->nome,
                    'obbligo'  => $r->obbligo,
                    'residuo'  => (int) round($garante->integrita),
                ]);
            }
        }
        return $messe;
    }

    /** @param array<string,mixed> $g */
    private function conclude(array $g, $mondo, ContestoTick $c, string $esito): void
    {
        $a = $mondo->nazioni[$g['aggressore']];
        $d = $mondo->nazioni[$g['difensore']];

        $a->netPeace = 3;
        $d->netPeace = 3;

        if ($esito === 'conquista') {
            // Il vinto non sparisce: cambia padrone e resta scontento, il che
            // e' il modo piu' affidabile di procurarsi un'insurrezione.
            $d->legittimita = max(5.0, $d->legittimita - 22.0);
            $d->forzaInsorti += $d->potenzaGoverno() * 0.35;
            $d->orientamento = (int) round(($d->orientamento + $a->orientamento * 2) / 3);
            $a->legittimita = min(100.0, $a->legittimita + 8.0);
        } else {
            // Un'aggressione fallita si paga in casa.
            $a->legittimita = max(0.0, $a->legittimita - 14.0);
            $d->legittimita = min(100.0, $d->legittimita + 10.0);
            $a->clamoreSociale += 18.0;
        }

        $c->annota('pace', [
            'aggressore' => $a->nome,
            'difensore'  => $d->nome,
            'esito'      => $esito,
            'anni'       => round(($c->tick - $g['inizio']) / 52.0, 1),
        ]);
    }
}
