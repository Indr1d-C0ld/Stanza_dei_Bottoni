<?php

declare(strict_types=1);

/**
 * Il mondo coltivato: le incoerenze trovate controllando il mondo vivo dopo
 * tre anni e mezzo (docs/30), una per una.
 *
 *   - l'integrita' dei garanti crollava per cadute senza mano straniera;
 *   - il ciclo economico era rumore bianco, perche' si tirava col caso del tick;
 *   - le tendenze di crescita venivano da un Factbook fermo al 2018 per alcuni;
 *   - la legittimita' tornava a 50 per tutti e il seme partiva da 60;
 *   - le guerre civili si accendevano e degeneravano nei paesi piccoli e poveri.
 *
 * Tutto in memoria.
 */

use App\Dati\Mondo;
use App\Nucleo\Calibrazione;
use App\Nucleo\Caso;
use App\Simulazione\ContestoTick;
use App\Simulazione\Fasi\Fase05SicurezzaInterna;
use App\Simulazione\Fasi\Fase06Relazioni;

$radiceColt = dirname(__DIR__);
$semeColt   = $radiceColt . '/db/seed/nazioni.csv';
$calColt    = Calibrazione::carica($radiceColt, 'osservazione');

// ------------------------------------------------------------- l'integrita'
Prove::gruppo('L\'integrita\': solo le cadute con una mano straniera mettono alla prova una garanzia');

/**
 * Fa cadere Haiti con un colpo di Stato e fa girare la fase 06. Restituisce
 * l'integrita' di ogni garante (obbligo >= 64) dopo la fase, e l'obbligo.
 *
 * @return array<string,array{0:float,1:int}>
 */
$caduta = static function (?string $mano) use ($semeColt, $calColt): array {
    $m = Mondo::daSeme($semeColt);
    $m->tick = 100;
    if ($mano !== null) {
        $m->nazioni['HTI']->ingerenzaTick = 90;
        $m->nazioni['HTI']->ingerenzaDa   = $mano;
    }
    $c = new ContestoTick(tick: 100, seme: 1, caso: new Caso(1), calibrazione: $calColt, mondo: $m,
        casoDelMondo: new Caso(7));
    $c->annota('colpo_di_stato', ['nazione' => $m->nazioni['HTI']->nome, 'tick' => 100]);
    (new Fase06Relazioni())->esegui($c);
    $esito = [];
    foreach ($m->relazioni->tutte() as $chiave => $r) {
        [$a, $b] = explode('|', $chiave);
        if ($b === 'HTI' && $r->obbligo >= 64) {
            $esito[$a] = [$m->nazioni[$a]->integrita, $r->obbligo, $m->nazioni[$a]->influenzaTotale];
        }
    }
    return $esito;
};

$senza = $caduta(null);
Prove::che('Haiti ha dei garanti nel seme (il Trattato di Rio)', count($senza) >= 3, (string) count($senza));
Prove::che('un colpo di Stato senza mano straniera non costa la faccia a nessuno',
    $senza !== [] && min(array_map(static fn(array $v): float => $v[0], $senza)) >= 127.9);

$con = $caduta('VEN');
uasort($con, static fn(array $x, array $y): int => $y[2] <=> $x[2]);
$primo = array_key_first($con);
$ultimo = array_key_last($con);
Prove::che('con una mano straniera il garante piu\' forte paga per intero',
    $primo !== null && abs($con[$primo][0] - 128.0 * (1.0 - $con[$primo][1] / 128.0)) < 1.0,
    $primo . ' ' . round($con[$primo][0] ?? -1, 1));
Prove::che('e il piu\' piccolo in proporzione al suo peso (Olson e Zeckhauser)',
    $ultimo !== null && $con[$ultimo][0] > $con[$primo][0] + 10.0,
    $ultimo . ' ' . round($con[$ultimo][0] ?? -1, 1));

$sua = $caduta($primo ?? 'USA');
Prove::che('chi ha messo le mani sul proprio cliente non perde la faccia di garante',
    ($sua[$primo][0] ?? 0.0) >= 127.9);

// ----------------------------------------------------------------- il ciclo
Prove::gruppo('Il ciclo economico e\' un\'onda, non rumore bianco');

$caso = new Caso(42);
$valori = [];
for ($t = 0; $t < 52 * 400; $t++) {
    $valori[] = $caso->onda('prova', 1, $t, 52);
}
$media = array_sum($valori) / count($valori);
$varianza = array_sum(array_map(static fn(float $v): float => ($v - $media) ** 2, $valori)) / count($valori);
Prove::vicino('l\'onda ha media zero', 0.0, $media, 0.12);
Prove::vicino('e varianza uno', 1.0, $varianza, 0.15);
$salto = 0.0;
for ($t = 1; $t < 520; $t++) {
    $salto = max($salto, abs($valori[$t] - $valori[$t - 1]));
}
Prove::che('da una settimana all\'altra si muove poco', $salto < 0.25, (string) round($salto, 3));
Prove::uguale('col caso del mondo la stessa settimana da\' lo stesso valore, a ogni tick',
    (new Caso(42))->onda('prova', 1, 300, 52), $caso->onda('prova', 1, 300, 52));

// -------------------------------------------------------------- la crescita
Prove::gruppo('La crescita parte dal FMI, non da un Factbook fermo al 2018');

$m = Mondo::daSeme($semeColt);
Prove::che('il Venezuela non ha piu\' una tendenza di -4% per sempre', $m->nazioni['VEN']->crescitaBase > 0.0,
    (string) $m->nazioni['VEN']->crescitaBase);
Prove::vicino('la Germania cresce come dice il FMI (0,96%)', 0.0096, $m->nazioni['DEU']->crescitaBase, 0.001);
$pesata = 0.0;
$pil = 0.0;
foreach ($m->elenco() as $n) {
    $pesata += $n->crescitaBase * $n->pil;
    $pil += $n->pil;
}
Prove::fra('il mondo cresce del 2,8-3,4% di tendenza, come nelle proiezioni 2026-2030', 0.028, 0.034, $pesata / $pil);
Prove::che('l\'aspettativa e\' pro capite: in Niger non e\' la crescita del PIL',
    $m->nazioni['NER']->aspettativa < $m->nazioni['NER']->crescitaPil - 0.02);

// ---------------------------------------------------------- la legittimita'
Prove::gruppo('La legittimita\' riposa dove il paese la tiene');

$somma = 0.0;
foreach ($m->elenco() as $n) {
    $somma += $n->ancoraLegittimita();
}
Prove::vicino('il paese medio riposa a 50, dove il modello e\' tarato', 50.0, $somma / count($m->nazioni), 0.5);
Prove::che('la Danimarca riposa piu\' in alto della Somalia di oltre venti punti',
    $m->nazioni['DNK']->ancoraLegittimita() - $m->nazioni['SOM']->ancoraLegittimita() > 20.0);
Prove::che('e il seme parte dal punto di riposo, non da una formula sua',
    abs($m->nazioni['FRA']->legittimita - $m->nazioni['FRA']->ancoraLegittimita()) < 4.0);

// ---------------------------------------------------------- la guerra civile
Prove::gruppo('Il rischio di guerra civile e\' quello stimato da Fearon');

$c = new ContestoTick(tick: 1, seme: 1, caso: new Caso(1), calibrazione: $calColt, mondo: $m);
$f = new Fase05SicurezzaInterna();
$rischio = new ReflectionMethod($f, 'rischioDiGuerraCivile');
$terreno = new ReflectionMethod($f, 'terrenoDiGuerra');
$r = static fn(string $iso): float => $rischio->invoke($f, $m->nazioni[$iso], $c, 52.0);
$t = static fn(string $iso): float => $terreno->invoke($f, $m->nazioni[$iso], $c);
Prove::che('la Danimarca rischia meno degli Stati Uniti, e gli Stati Uniti meno della Nigeria',
    $r('DNK') < $r('USA') && $r('USA') < $r('NGA'));
Prove::che('l\'India piu\' del Madagascar: la popolazione conta, la sola poverta\' no',
    $r('IND') > $r('MDG'), round($r('IND'), 2) . ' contro ' . round($r('MDG'), 2));
$base = $calColt->numero('insurrezione.innesco_base_anno');
$tetto = $calColt->numero('insurrezione.innesco_massimo_anno');
// Il tetto conta per chi un'insurrezione non ce l'ha: chi ce l'ha gia' (il
// seme UCDP) non si riaccende.
$alTetto = array_keys(array_filter($m->nazioni,
    static fn($n): bool => !$n->haInsorti() && $base * $rischio->invoke($f, $n, $c, 52.0) >= $tetto));
Prove::che('pochi paesi in pace stanno al tetto di innesco (erano sedici, compreso il Ruanda)',
    count($alTetto) <= 6, implode(' ', $alTetto));
Prove::che('il Ruanda, Stato efficiente, non sta al tetto', $base * $r('RWA') < $tetto);
$soglia = 1.0 + $calColt->numero('insurrezione.risposta_governo', 6.0);
$k = $calColt->numero('insurrezione.reclutamento', 1.0);
Prove::che('il terreno del Togo non basta a farne una guerra civile (era la meta\' dei casi spuri)',
    $k * $t('TGO') < $soglia, (string) round($k * $t('TGO'), 2));
Prove::che('quello della Somalia si\'', $k * $t('SOM') * 1.5 > $soglia, (string) round($k * $t('SOM'), 2));
