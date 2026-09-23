<?php

declare(strict_types=1);

/**
 * Il mondo vivo e' il mondo che si misura?
 *
 * PERCHE' ESISTE. L'audit di settembre 2026 ha scoperto che tutte le misure di
 * realismo descrivevano un mondo che non esisteva: `bin/realismo.php` fa
 * girare il motore IN MEMORIA per quindici anni, mentre il mondo vivo
 * riparte a ogni tick dal seme e ci sovrappone quel che la base dati ha
 * conservato. Tutto cio' che il Deposito non salva — o salva arrotondato —
 * torna al seme, o perde il movimento frazionario, ogni due ore. Le affinita'
 * non si muovevano, lo stato di polizia era un cricchetto, l'ancora dei
 * rapporti e l'obbligo firmato si dimenticavano a ogni giro (migrazione 0029).
 *
 * La prova di andata e ritorno del file 12 non poteva accorgersene: confronta
 * due mondi entrambi ricostruiti dal seme, e un campo che non si salva vale il
 * seme in tutti e due. Qui invece si fa girare LO STESSO mondo due volte:
 *
 *   A  in memoria, come fa bin/realismo.php;
 *   B  come fa il cron: seme, ripristino, tick, salvataggio — a ogni tick.
 *
 * e alla fine si confronta tutto. Se il Deposito e' fedele, i due mondi sono
 * identici fino all'ultima cifra: il caso e' deterministico e il motore e' lo
 * stesso. Ogni differenza e' un pezzo di stato che il mondo vivo perde.
 *
 * Tutto dentro una transazione annullata: il mondo vivo non se ne accorge.
 */

use App\Dati\Deposito;
use App\Dati\Mondo;
use App\Nucleo\Basedati;
use App\Nucleo\Calendario;
use App\Nucleo\Calibrazione;
use App\Nucleo\Configurazione;
use App\Simulazione\EsecutoreTick;

$radiceFedelta = dirname(__DIR__);
$dbFedelta     = new Basedati((array) Configurazione::leggi('db', []));
$calFedelta    = Calibrazione::carica($radiceFedelta, 'osservazione');
$semeFedelta   = $radiceFedelta . '/db/seed/nazioni.csv';
$giri          = 26;   // mezzo anno: basta perche' un movimento frazionario perso si veda

/**
 * Il mondo ridotto a un elenco piatto di numeri, chiave => valore.
 *
 * @return array<string,float>
 */
$impronta = static function (Mondo $m): array {
    $f = [];
    foreach ($m->nazioni as $iso => $n) {
        foreach (get_object_vars($n) as $campo => $v) {
            if (is_int($v) || is_float($v) || is_bool($v)) {
                $f["nazione.$campo|$iso"] = (float) $v;
            }
        }
    }
    foreach ($m->relazioni->tutte() as $k => $r) {
        $f["relazione.affinita|$k"]       = $r->affinita;
        $f["relazione.ancora|$k"]         = (float) ($r->ancora ?? $r->affinita);
        $f["relazione.obbligo|$k"]        = (float) $r->obbligo;
        $f["relazione.obbligoFirmato|$k"] = (float) $r->obbligoFirmato;
        $f["relazione.sfera|$k"]          = (float) $r->sfera;
        $f["relazione.spintaSfera|$k"]    = $r->spintaSfera;
        $f["relazione.umore|$k"]          = (float) $r->umore;
    }
    foreach ($m->gabinetti as $iso => $g) {
        $f["gabinetto.coesione|$iso"] = $g->coesione;
        foreach ($g->poltrone as $ruolo => $p) {
            $f["poltrona.potere|$iso.$ruolo"]        = $p->potere;
            $f["poltrona.lealta|$iso.$ruolo"]        = $p->lealta;
            $f["poltrona.insediatoTick|$iso.$ruolo"] = (float) $p->insediatoTick;
            $f["poltrona.competenza|$iso.$ruolo"]    = (float) $p->titolare->competenza;
        }
        foreach ($g->fazioni as $i => $z) {
            foreach (get_object_vars($z) as $campo => $v) {
                if (is_int($v) || is_float($v)) {
                    $f["fazione.$campo|$iso.$i"] = (float) $v;
                }
            }
        }
    }
    foreach ($m->intelligence->presenza as $a => $bersagli) {
        foreach ($bersagli as $b => $v) {
            $f["intelligence.presenza|$a.$b"] = $v;
        }
    }
    $f['mondo.nastiness|']       = $m->nastiness;
    $f['mondo.livelloPace|']     = (float) $m->livelloPace;
    $f['mondo.guerre|']          = (float) count($m->guerre);
    $f['mondo.strozzature|']     = (float) count($m->strozzature);
    $f['mondo.azioniRecenti|']   = (float) count($m->azioniRecenti);
    $f['mondo.prossimoIdEvento|'] = (float) $m->prossimoIdEvento;
    return $f;
};

Prove::gruppo('Il mondo vivo e il mondo misurato sono lo stesso mondo');

$pdoFedelta = $dbFedelta->pdo();
$pdoFedelta->beginTransaction();
try {
    // A: in memoria.
    $a = Mondo::daSeme($semeFedelta);
    $a->tick = 0;
    $motoreA = new EsecutoreTick($calFedelta, null, true, $a);
    for ($t = 1; $t <= $giri; $t++) {
        $a->tick = $t;
        $motoreA->esegui($t, 1, null);
    }

    // B: come il cron, col giro completo dalla base dati a ogni tick, su un
    // mondo appena azzerato come fa bin/avvia_mondo.php — altrimenti B
    // erediterebbe i gabinetti e gli eventi del mondo vivo, che il seme non ha.
    $dep = new Deposito($dbFedelta);
    $dep->azzeraMondo();
    $b = Mondo::daSeme($semeFedelta);
    $b->tick = 0;
    $dep->salva($b, 0, Calendario::tickIso(0));
    $ripristinati = 0;
    for ($t = 1; $t <= $giri; $t++) {
        $b = Mondo::daSeme($semeFedelta);
        $ripristinati += $dep->ripristina($b, $t - 1) ? 1 : 0;
        $b->tick = $t;
        (new EsecutoreTick($calFedelta, null, true, $b))->esegui($t, 1, null);
        $dep->salva($b, $t, Calendario::tickIso($t));
    }
    Prove::uguale('ogni tick riparte dallo stato salvato', $giri, $ripristinati);

    $fa = $impronta($a);
    $fb = $impronta($b);
    $diversi = [];
    foreach ($fa as $k => $v) {
        $w = $fb[$k] ?? null;
        $tolleranza = max(1e-6, abs($v) * 1e-9);
        if ($w === null || abs($v - $w) > $tolleranza) {
            $campo = strstr($k, '|', true);
            $diversi[$campo] = ($diversi[$campo] ?? 0) + 1;
        }
    }
    foreach (array_diff_key($fb, $fa) as $k => $_) {
        $campo = strstr($k, '|', true);
        $diversi[$campo] = ($diversi[$campo] ?? 0) + 1;
    }
    arsort($diversi);
    Prove::che("dopo $giri tick i due mondi coincidono campo per campo",
        $diversi === [],
        implode(', ', array_map(static fn($c, $k) => "$c ($k)",
            array_slice(array_keys($diversi), 0, 12), array_slice($diversi, 0, 12))));
} finally {
    $pdoFedelta->rollBack();
}
