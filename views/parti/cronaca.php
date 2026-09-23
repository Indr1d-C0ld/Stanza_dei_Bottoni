<?php defined('BASE') || exit; // si include da index.php, non si apre dal browser ?>
<?php
/** @var list<array<string,mixed>> $cronaca */

$tensioni = [1=>'quiete', 2=>'pace', 3=>'tensione', 4=>'conflitto aperto',
             5=>'guerra civile', 6=>'guerra totale'];

/**
 * Da campi grezzi a frase. Il feed è la voce pubblica del mondo: deve leggersi,
 * non decodificarsi.
 * @param array<string,mixed> $d
 */
$racconta = static function (string $genere, array $d) use ($tensioni): array {
    $n = static fn(string $k): string => (string) ($d[$k] ?? '—');
    return match ($genere) {
        'colpo_di_stato'  => ['Colpo di stato', 'Il governo di ' . $n('nazione') . ' cade fuori dalle regole.'],
        'cambio_governo'  => ['Cambio di governo', 'Nuovo esecutivo in ' . $n('nazione') . '.'],
        'rivoluzione'     => ['Rivoluzione', 'Gli insorti prendono il potere in ' . $n('nazione') . '.'],
        'elezione'        => ['Consultazione', $n('nazione') . ': ' .
                              ($n('esito') === 'sfiducia' ? 'crisi di governo' : 'alternanza alle urne') . '.'],
        'conflitto'       => ['Conflitto', $n('nazione') . ' scivola verso ' .
                              ($tensioni[(int) ($d['livello'] ?? 0)] ?? 'la crisi') . '.'],
        'guerra'          => ['Guerra', $n('aggressore') . ' invade ' . $n('difensore') . '.'],
        'pace'            => ['Fine delle ostilità', $n('aggressore') . ' contro ' . $n('difensore') .
                              ': ' . $n('esito') . ', dopo ' . $n('anni') . ' anni.'],
        'embargo'         => ['Embargo', $n('da') . ' chiude i commerci con ' . $n('contro') . '.'],
        'attacco'         => ['Attacco', $n('da') . ' colpisce obiettivi in ' . $n('contro') . '.'],
        'mediazione'      => ['Mediazione', $n('mediatore') . ' si interpone in ' . $n('paese') . '.'],
        'scandalo'        => ['Scandalo', 'Attribuita a ' . $n('mandante') . ' un\'operazione di ' .
                              str_replace('_', ' ', $n('verbo')) . ' contro ' . $n('contro') .
                              '; la rivelazione è di ' . $n('chi') . '.'],
        'operazione_sventata' => ['Operazione sventata', $n('bersaglio') . ' blocca un\'operazione di ' .
                              str_replace('_', ' ', $n('verbo')) . ' attribuita a ' . $n('mandante') . '.'],
        'talpa_scoperta'  => ['Talpa scoperta', $n('chi') . ', ' . mb_strtolower($n('ruolo')) . ' di ' .
                              $n('paese') . ', lavorava per ' . $n('per_conto') . '.'],
        'reclutamento_denunciato' => ['Reclutamento denunciato', $n('paese') . ' accusa ' . $n('accusa') .
                              ' di aver tentato di comprare il suo ' . mb_strtolower($n('poltrona')) . '.'],
        'epoca_chiusa'    => ['Fine di un\'epoca', $n('racconto')],
        'incidente'       => ['Incidente', 'La crisi fra ' . $n('fra') . ' e ' . $n('e') .
                              ' sfugge di mano: ' . $n('gradino') . '.'],
        'bomba_ottenuta'  => ['Test nucleare', $n('paese') . ' ha provato il suo primo ordigno.'],
        'bomba_posata'    => ['Disarmo nucleare', $n('paese') . ' rinuncia all\'arsenale.'],
        'garanzia_onorata'=> ['Garanzia onorata', $n('garante') . ' si schiera con ' . $n('protetto') . '.'],
        'garanzia_tradita'=> ['Garanzia tradita', $n('garante') . ' non muove un dito per ' . $n('protetto') .
                              ', che aveva promesso di difendere.'],
        'restrizioni'     => ['Sanzioni', $n('da') . ' restringe i commerci con ' . $n('contro') . '.'],
        'dimissioni'      => ['Dimissioni', $n('chi') . ' lascia il ministero (' . mb_strtolower($n('ruolo')) .
                              ') in ' . $n('nazione') . '.'],
        default           => [ucfirst(str_replace('_', ' ', $genere)),
                              implode(' · ', array_map('strval', $d))],
    };
};
?>
<ul class="cronaca">
<?php foreach ($cronaca as $voce):
    $d = json_decode((string) $voce['dati'], true) ?: [];
    $genere = (string) $voce['genere'];
    [$etichetta, $testo] = $racconta($genere, $d);
?>
  <li class="voce g-<?= htmlspecialchars($genere) ?>">
    <span class="quando"><?= App\Nucleo\Calendario::tick((int) $voce['tick']) ?></span>
    <span class="etichetta"><?= htmlspecialchars($etichetta) ?></span>
    <span class="corpo"><?= htmlspecialchars($testo) ?></span>
  </li>
<?php endforeach; ?>
</ul>
