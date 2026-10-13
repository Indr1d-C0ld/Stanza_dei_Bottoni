<?php defined('BASE') || exit; // si include da index.php, non si apre dal browser ?>
<?php
/** @var App\Dati\Planisfero $planisfero */
/** @var array<string,array{colore:string,valore:string}> $colori */
/** @var array<string,string> $nomiMappa */
/** @var string $letturaScelta */
/** @var list<array<string,mixed>> $guerre */
/** @var array{peggio:array{righe:list<string>,altri:list<string>},meglio:array{righe:list<string>,altri:list<string>}} $code */
$letture = App\Dati\Planisfero::LETTURE;
?>
<h1><?= htmlspecialchars($letture[$letturaScelta]['titolo'] ?? 'Il mondo') ?></h1>

<nav class="letture">
  <?php foreach ($letture as $k => $d): ?>
    <?php if ($k === 'rapporti' && $poltrona === null): continue; endif; ?>
    <a href="<?= u('/mappa') ?>?l=<?= $k ?>" class="<?= $k === $letturaScelta ? 'attivo' : '' ?>">
      <?= htmlspecialchars($d['titolo']) ?></a>
  <?php endforeach; ?>
</nav>
<p class="tenue"><?= htmlspecialchars($letture[$letturaScelta]['nota'] ?? '') ?>
   Passa sopra un paese per il valore; premi per la sua scheda.</p>

<?php if (!$planisfero->pronto()): ?>
  <p class="allarme">I confini non sono stati costruiti. Da riga di comando:
     <code>php bin/costruisci_mappa.php &lt;geojson&gt;</code></p>
<?php else: ?>
  <?php require __DIR__ . '/parti/mappa.php'; ?>
<?php endif; ?>

<?php
// Una guerra con fine_tick valorizzato e' finita: stamparla sotto «in corso»
// e' quello che faceva questa pagina, e raccontava conflitti spenti da mesi.
$inCorso   = array_filter($guerre, static fn ($g) => $g['fine_tick'] === null);
$concluse  = array_filter($guerre, static fn ($g) => $g['fine_tick'] !== null);
?>
<?php if ($inCorso !== []): ?>
<section>
  <h2>Guerre in corso</h2>
  <ul class="elenco-piano">
    <?php foreach ($inCorso as $g): ?>
      <li><a href="<?= u('/nazione/') ?><?= $g['cod_a'] ?>"><?= htmlspecialchars((string) $g['aggressore']) ?></a>
          contro <a href="<?= u('/nazione/') ?><?= $g['cod_d'] ?>"><?= htmlspecialchars((string) $g['difensore']) ?></a>
          <span class="tenue">dal <?= App\Nucleo\Calendario::tick((int) $g['inizio_tick']) ?>,
            <?= n((float) $g['morti'], 0) ?> morti<?= (int) $g['inizio_tick'] < 0 ? ' da gennaio 2026' : '' ?><?php
            if ((float) ($g['aiuti_difensore'] ?? 0) > 0): ?>; aiuti militari ricevuti da
            <?= htmlspecialchars((string) $g['difensore']) ?>: <?= n((float) $g['aiuti_difensore'] / 1000, (float) $g['aiuti_difensore'] < 10000 ? 1 : 0) ?> miliardi di dollari<?php
            endif; ?></span></li>
    <?php endforeach; ?>
  </ul>
  <?php if (array_filter($inCorso, static fn ($g) => (int) $g['inizio_tick'] < 0) !== []): ?>
  <p class="tenue">Le guerre cominciate prima di gennaio 2026 contano i morti da allora: le stime
     di quelli precedenti sono troppo distanti fra loro per farne un dato. Gli aiuti sono
     in dollari a parità di potere d'acquisto, come tutto il prodotto del modello.</p>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($concluse !== []): ?>
<section>
  <h2>Guerre concluse</h2>
  <ul class="elenco-piano">
    <?php foreach ($concluse as $g): ?>
      <li><a href="<?= u('/nazione/') ?><?= $g['cod_a'] ?>"><?= htmlspecialchars((string) $g['aggressore']) ?></a>
          contro <a href="<?= u('/nazione/') ?><?= $g['cod_d'] ?>"><?= htmlspecialchars((string) $g['difensore']) ?></a>
          <span class="tenue">dal <?= App\Nucleo\Calendario::tick((int) $g['inizio_tick']) ?>
            al <?= App\Nucleo\Calendario::tick((int) $g['fine_tick']) ?>,
            <?= n((float) $g['morti'], 0) ?> morti<?= (int) $g['inizio_tick'] < 0 ? ' da gennaio 2026' : '' ?><?php
            if (!empty($g['esito'])): ?> — <?= htmlspecialchars(match ((string) $g['esito']) {
                'conquista' => 'conquista', 'ritirata' => 'ritirata dell\'aggressore',
                'armistizio' => 'armistizio', default => (string) $g['esito'] }) ?><?php endif; ?></span></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<?php
// Un nome con il suo colore, il suo valore e il collegamento alla scheda.
$riga = static function (string $iso) use ($colori, $nomiMappa): string {
    return '<tr><td><span class="pastiglia" style="background:'
        . htmlspecialchars($colori[$iso]['colore'] ?? '#555') . '"></span></td>'
        . '<td><a href="' . u('/nazione/' . $iso) . '">' . htmlspecialchars($nomiMappa[$iso] ?? $iso) . '</a></td>'
        . '<td class="tenue">' . htmlspecialchars($colori[$iso]['valore'] ?? '—') . '</td></tr>';
};
// Una coda. Se e' tutta un gruppo alla pari (i 23 paesi a 1 su 10, i 157 in
// pace) cinque nomi in ordine alfabetico sarebbero arbitrari: si nominano
// tutti finche' sono pochi, si contano quando sono tanti. Altrimenti si
// mostrano i primi, e sotto chi sta al loro stesso valore.
$coda = static function (array $c) use ($riga, $colori, $nomiMappa): string {
    $nomi = static fn (array $isi) => implode(', ', array_map(static fn ($iso) => '<a href="'
        . u('/nazione/' . $iso) . '">' . htmlspecialchars($nomiMappa[$iso] ?? $iso) . '</a>', $isi));
    $tutti = array_merge($c['righe'], $c['altri']);
    $valori = array_unique(array_map(static fn ($iso) => $colori[$iso]['valore'] ?? '', $tutti));
    if ($c['altri'] !== [] && count($valori) === 1) {
        $v = htmlspecialchars((string) reset($valori));
        return count($tutti) <= 25
            ? '<p>' . count($tutti) . ' paesi a pari valore, ' . $v . ': ' . $nomi($tutti) . '.</p>'
            : '<p>' . count($tutti) . ' paesi a pari valore, ' . $v . '.</p>';
    }
    $html = '<table class="tabella">' . implode('', array_map($riga, $c['righe'])) . '</table>';
    if ($c['altri'] !== []) {
        $html .= count($c['altri']) <= 12
            ? '<p class="tenue">Allo stesso valore dell\'ultimo anche: ' . $nomi($c['altri']) . '.</p>'
            : '<p class="tenue">E altri ' . count($c['altri']) . ' paesi allo stesso valore dell\'ultimo.</p>';
    }
    return $html;
};
?>
<section>
  <h2>I casi estremi</h2>
  <p class="tenue">Le due code della lettura scelta, sul valore e non sul colore.</p>
  <h3>Dove va peggio</h3>
  <?= $code['peggio']['righe'] === []
      ? '<p class="tenue">Nessuno: tutti i paesi stanno allo stesso valore.</p>'
      : $coda($code['peggio']) ?>
  <h3>Dove va meglio</h3>
  <?= $coda($code['meglio']) ?>
</section>
