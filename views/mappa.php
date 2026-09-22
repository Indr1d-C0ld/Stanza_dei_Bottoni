<?php
/** @var App\Dati\Planisfero $planisfero */
/** @var array<string,array{colore:string,valore:string}> $colori */
/** @var array<string,string> $nomiMappa */
/** @var string $letturaScelta */
/** @var list<array<string,mixed>> $guerre $notevoli */
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
            <?= n((float) $g['morti'], 0) ?> morti</span></li>
    <?php endforeach; ?>
  </ul>
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
            <?= n((float) $g['morti'], 0) ?> morti</span></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<section>
  <h2>I casi estremi</h2>
  <p class="tenue">Le due code della lettura scelta: dove va peggio e dove va meglio.</p>
  <table class="tabella">
    <?php foreach ($notevoli as $r): ?>
      <tr>
        <td><span class="pastiglia" style="background:<?= htmlspecialchars((string) $r['colore']) ?>"></span></td>
        <td><a href="<?= u('/nazione/' . $r['codice']) ?>"><?= htmlspecialchars((string) $r['nome']) ?></a></td>
        <td class="tenue"><?= htmlspecialchars((string) $r['valore']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</section>
