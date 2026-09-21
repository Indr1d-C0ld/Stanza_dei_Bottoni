<?php

declare(strict_types=1);

/**
 * La scala delle crisi.
 *
 * Qui si prova la regola di decisione, non la persistenza: l'eccesso di
 * oltraggio di Crawford piu' l'impegno gia' speso, meno la reluttanza e la
 * paura. La prova che conta e' la forma della distribuzione, perche' i due
 * modi di sbagliarla sono opposti e tutti e due gia' successi: una macchina
 * che sale sempre (e allora la scala e' un binario) e una che cede sempre al
 * secondo gradino (e allora la meta' alta non serve a niente).
 */

use App\Nucleo\Calibrazione;

$cal = Calibrazione::carica(dirname(__DIR__), 'gioco');

$pesoImpegno = $cal->numero('crisi.peso_impegno', 0.18);
$pesoPaura   = $cal->numero('crisi.peso_paura', 28.0);
$espPaura    = $cal->numero('crisi.esponente_paura', 2.4);
$reluttanza  = $cal->numero('crisi.reluttanza', 2.0);
$pesoNuc     = $cal->numero('crisi.peso_nucleare', 45.0);
$espNuc      = $cal->numero('crisi.esponente_nucleare', 4.0);
$tettoForze  = $cal->numero('crisi.tetto_forze', 2.0);
$crescita    = $cal->numero('crisi.crescita_posta', 0.38);

/** Una crisi fra due macchine, dal primo gradino fino a dove si rompe. */
$partita = static function (float $base, float $affA, float $affB, float $forzeA,
    int $nucA, int $nucB, callable $rumore): int {
    global $pesoImpegno, $pesoPaura, $espPaura, $reluttanza, $pesoNuc, $espNuc, $tettoForze, $crescita;
    $l = 1;
    $primo = true;
    while (true) {
        $scala = 1.0 + $l * $crescita;
        $mia = $base * ($primo ? $affA : $affB) * $scala;
        $sua = $base * ($primo ? $affB : $affA) * $scala;
        $f = max(1.0 / $tettoForze, min($tettoForze, $primo ? $forzeA : 1.0 / $forzeA));
        $paura = ($l / 9.0) ** $espPaura * $pesoPaura / $f;
        if ($nucA >= 3 && $nucB >= 3) {
            $paura += ($l / 9.0) ** $espNuc * $pesoNuc * min(7, min($nucA, $nucB)) / 7.0;
        }
        $e = ($mia - $sua) + $pesoImpegno * $mia + max(0.0, 9 - $l) * 1.2
            + $rumore() - $reluttanza - $paura;
        if ($e <= 0.0) {
            return $l;
        }
        $l++;
        $primo = !$primo;
        if ($l >= 9) {
            return 9;
        }
    }
};

mt_srand(4242);
$rumore = static fn(): float => mt_rand(0, 600) / 100.0 - 3.0;

$corri = static function (int $nucA, int $nucB) use ($partita, $rumore): array {
    $g = [];
    for ($n = 0; $n < 3000; $n++) {
        $g[] = $partita(
            10 + mt_rand(0, 18),
            0.5 + mt_rand(0, 100) / 100,
            0.5 + mt_rand(0, 100) / 100,
            0.5 + mt_rand(0, 150) / 100,
            $nucA, $nucB, $rumore);
    }
    sort($g);
    return $g;
};

Prove::gruppo('Crisi: la scala si usa tutta, ma il fondo e\' raro');

$senzaBomba = $corri(0, 0);
$conteggio = array_count_values($senzaBomba);
$mediana = $senzaBomba[(int) (count($senzaBomba) / 2)];
$inFondo = ($conteggio[9] ?? 0) / count($senzaBomba);

Prove::fra('la rottura mediana sta nella meta\' bassa', 2, 5, (float) $mediana);
Prove::che('il nono gradino si raggiunge di rado',
    $inFondo < 0.05, sprintf('%.1f%%', 100 * $inFondo));
Prove::che('ma si raggiunge',
    $inFondo > 0.0, 'mai raggiunto: la scala e\' un muro');

$usati = 0;
foreach (range(1, 8) as $g) {
    if (($conteggio[$g] ?? 0) / count($senzaBomba) > 0.02) {
        $usati++;
    }
}
Prove::che('almeno sei gradini su otto sono usati davvero',
    $usati >= 6, sprintf('%d gradini con piu\' del 2%%', $usati));

Prove::gruppo('Crisi: la bomba tiene ferme le mani in cima');

$conBomba = $corri(6, 6);
$cb = array_count_values($conBomba);
$fondoNucleare = ($cb[9] ?? 0) / count($conBomba);
$altiNucleare  = (($cb[8] ?? 0) + ($cb[9] ?? 0)) / count($conBomba);

Prove::che('fra due potenze nucleari il nono gradino non si raggiunge',
    $fondoNucleare < 0.005, sprintf('%.2f%%', 100 * $fondoNucleare));
Prove::che('e anche l\'ottavo e\' quasi irraggiungibile',
    $altiNucleare < 0.02, sprintf('%.2f%%', 100 * $altiNucleare));
Prove::che('la deterrenza cambia davvero l\'esito',
    $fondoNucleare < $inFondo, 'la bomba non ha spostato niente');

Prove::gruppo('Crisi: la posta cresce salendo');

$poste = [];
foreach (range(1, 9) as $l) {
    $poste[$l] = 26.0 * (45 / 127.0) * (0.5 + 0.6) * (1.0 + $l * $crescita) * (0.4 + 0.5);
}
Prove::che('la posta al nono gradino e\' molto maggiore che al primo',
    $poste[9] > $poste[1] * 2.0,
    sprintf('%.1f contro %.1f', $poste[9], $poste[1]));
$sempreSu = true;
foreach (range(2, 9) as $l) {
    if ($poste[$l] <= $poste[$l - 1]) {
        $sempreSu = false;
    }
}
Prove::che('e cresce a ogni gradino, senza scalini all\'ingiu\'', $sempreSu);
