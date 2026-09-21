<?php
/** @var list<array<string,mixed>> $potenze $cronaca $guerre */
/** @var array<string,mixed> $stato */
$umori = [1=>'armonia',2=>'amicizia',3=>'cooperazione',4=>'indifferenza',
          5=>'competizione',6=>'rivalità',7=>'inimicizia'];
?>
<h1>Il mondo</h1>

<?php if ($planisfero->pronto()): ?>
  <?php require __DIR__ . '/parti/mappa.php'; ?>
  <p class="tenue">Dove si spara, adesso: dalla quiete alla guerra totale.
     <a href="<?= u('/mappa') ?>">Il planisfero, con le altre letture</a>.</p>
<?php endif; ?>

<?php if ($guerre !== [] && $guerre[0]['fine_tick'] === null): ?>
<section class="allarme">
  <h2>Guerre in corso</h2>
  <ul>
  <?php foreach ($guerre as $g): if ($g['fine_tick'] !== null) continue; ?>
    <li><a href="<?= u('/nazione') ?>/<?= $g['cod_a'] ?>"><?= htmlspecialchars($g['aggressore']) ?></a>
        contro <a href="<?= u('/nazione') ?>/<?= $g['cod_d'] ?>"><?= htmlspecialchars($g['difensore']) ?></a>
        <span class="tenue">dal <?= App\Nucleo\Calendario::tick((int) $g['inizio_tick']) ?></span></li>
  <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<section>
  <h2>Chi conta</h2>
  <table>
    <thead><tr><th>Potenza</th><th>Influenza</th><th>PIL</th><th>Legittimità</th><th>Qualità della vita</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($potenze as $p): ?>
      <tr>
        <td><a href="<?= u('/nazione') ?>/<?= $p['codice'] ?>"><?= htmlspecialchars($p['nome']) ?></a>
            <?php if ((int) $p['giocabile']): ?><span class="pastiglia">giocabile</span><?php endif; ?></td>
        <td class="num"><?= number_format((float) $p['influenza_totale'], 2) ?>%</td>
        <td class="num"><?= number_format((float) $p['pil'] / 1000, 0) ?> mld</td>
        <td class="num"><?= number_format((float) $p['legittimita'], 0) ?></td>
        <td class="num"><?= (int) $p['qualita_vita'] ?>/10</td>
        <td><?php if ((float) $p['influenza_totale'] >= 5): ?><span class="rango">grande potenza</span>
            <?php elseif ((float) $p['influenza_totale'] >= 3): ?><span class="rango tenue">potenza maggiore</span>
            <?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>

<section>
  <h2>Ultime notizie</h2>
  <?php require __DIR__ . '/parti/cronaca.php'; ?>
  <p><a href="<?= u('/cronaca') ?>">Tutta la cronaca →</a></p>
</section>
