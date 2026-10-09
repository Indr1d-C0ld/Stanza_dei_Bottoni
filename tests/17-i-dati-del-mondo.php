<?php

declare(strict_types=1);

/**
 * I dati del mondo (docs/31): le rivalita' e la guerra fra Stati, il petrolio
 * della Banca Mondiale, Taiwan nei WGI, i conflitti del 2025, la polizia e la
 * censura di V-Dem. Tutto in memoria.
 */

use App\Dati\Mondo;
use App\Nucleo\Calibrazione;
use App\Nucleo\Caso;
use App\Simulazione\ContestoTick;
use App\Simulazione\Fasi\Fase00Chiusura;

$radiceDati = dirname(__DIR__);
$mDati = Mondo::daSeme($radiceDati . '/db/seed/nazioni.csv');
$calDati = Calibrazione::carica($radiceDati, 'osservazione');

// ------------------------------------------------------------- le rivalita'
Prove::gruppo('Le rivalita\' vengono dalle dispute vere');

$riv = $mDati->rivalita;
Prove::che('Russia e Ucraina sono una rivalita\' duratura', ($riv['RUS|UKR'] ?? 0) >= 6);
Prove::che('Armenia e Azerbaigian anche, dal Karabakh (UCDP, conflitto internazionalizzato)',
    ($riv['ARM|AZE'] ?? 0) >= 6);
Prove::che('Cina e Taiwan sono rivali', ($riv['CHN|TWN'] ?? 0) >= 3);
Prove::che('India e Pakistan, Iran e Israele, le due Coree',
    ($riv['IND|PAK'] ?? 0) >= 6 && ($riv['IRN|ISR'] ?? 0) >= 6 && ($riv['KOR|PRK'] ?? 0) >= 6);
Prove::che('Francia e Germania no', !isset($riv['DEU|FRA']) && !isset($riv['FRA|DEU']));
Prove::fra('le rivalita\' sono qualche decina, come nei dati', 30, 90, (float) count($riv));

Prove::gruppo('La guerra fra rivali e\' un rischio, non una lotteria');

$c = new ContestoTick(tick: 1, seme: 1, caso: new Caso(1), calibrazione: $calDati, mondo: $mDati);
$f = new Fase00Chiusura();
$puo = new ReflectionMethod($f, 'puoInvadere');
$rel = static fn(string $a, string $b) => $mDati->relazioni->fra($a, $b);
Prove::che('la Russia puo\' invadere la Georgia, se la odia', (static function () use ($puo, $f, $mDati, $rel, $c): bool {
    $r = $rel('RUS', 'GEO');
    $prima = $r->affinita;
    $r->affinita = -80.0;
    $esito = $puo->invoke($f, $mDati->nazioni['RUS'], $mDati->nazioni['GEO'], $r, $mDati, $c, -35.0);
    $r->affinita = $prima;
    return $esito;
})());
Prove::che('nessuno invade chi ha l\'atomica', (static function () use ($puo, $f, $mDati, $rel, $c): bool {
    $r = $rel('IND', 'PAK');
    $prima = $r->affinita;
    $r->affinita = -100.0;
    $esito = $puo->invoke($f, $mDati->nazioni['IND'], $mDati->nazioni['PAK'], $r, $mDati, $c, -35.0);
    $r->affinita = $prima;
    return !$esito;
})());
Prove::uguale('il tasso fra rivali e\' quello misurato (0,53%)', 0.0053, $calDati->numero('dottrina.guerra.rivalita'));
Prove::uguale('e fra rivali duraturi (1,26%)', 0.0126, $calDati->numero('dottrina.guerra.duratura'));

// --------------------------------------------------------------- i dati
Prove::gruppo('Il petrolio, Taiwan, i conflitti del 2025');

Prove::che('l\'Iran esporta petrolio, gli Stati Uniti no (combustibili oltre un terzo delle esportazioni)',
    $mDati->nazioni['IRN']->petrolio && !$mDati->nazioni['USA']->petrolio);
Prove::che('la Norvegia si\', i Paesi Bassi no', $mDati->nazioni['NOR']->petrolio && !$mDati->nazioni['NLD']->petrolio);
$governo = require $radiceDati . '/db/seed/governo.php';
Prove::che('Taiwan ha i suoi WGI, non quelli della Corea del Sud',
    ($governo['TWN']['efficacia'] ?? 0) !== ($governo['KOR']['efficacia'] ?? 0)
    && ($governo['TWN']['stabilita'] ?? 0) > 0.5);
$conflitti = require $radiceDati . '/db/seed/conflitti-noti.php';
Prove::uguale('Haiti e\' in guerra civile (UCDP 2025: 1.211 morti)', 6, $conflitti['HTI'] ?? 0);
Prove::uguale('l\'Afghanistan e\' sceso a guerriglia (81)', 4, $conflitti['AFG'] ?? 0);
Prove::che('il Burundi e il Senegal non sono piu\' in elenco', !isset($conflitti['BDI']) && !isset($conflitti['SEN']));

// ---------------------------------------------------- polizia e censura
Prove::gruppo('La polizia e la censura partono dalla norma del regime');

$p = static fn(string $iso): float => $mDati->nazioni[$iso]->statoPolizia;
Prove::che('Corea del Nord > Russia > Stati Uniti > Norvegia', $p('PRK') > $p('RUS') && $p('RUS') > $p('USA')
    && $p('USA') > $p('NOR'), sprintf('%.1f %.1f %.1f %.1f', $p('PRK'), $p('RUS'), $p('USA'), $p('NOR')));
Prove::che('la Corea del Nord parte quasi in morsa piena', $p('PRK') > 4.5);
Prove::che('Singapore censura molto piu\' di quanto reprima',
    $mDati->nazioni['SGP']->controlloInfo > 50.0 && $p('SGP') < 2.5);
Prove::che('le Filippine, con uno Stato violento, possono ancora votare: conta la stretta oltre la norma',
    $mDati->nazioni['PHL']->statoPolizia - $mDati->nazioni['PHL']->basePolizia() <= 1.0
    && $mDati->nazioni['PHL']->statoPolizia > 2.9);
