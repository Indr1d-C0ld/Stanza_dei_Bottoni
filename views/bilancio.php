<?php
/** @var array<string,mixed>|null $epocaCorrente */
/** @var list<array<string,mixed>> $epocheChiuse $classifica */
/** @var array<string,list<array<string,mixed>>> $rivelazioni */
/** @var int $restano $tick */
$generi = [
  'operazione' => 'Le operazioni coperte, col loro vero mandante',
  'talpa'      => 'Chi lavorava per chi',
  'falso'      => 'Le lettere riscritte',
  'crisi'      => 'Le crisi, e cosa c\'era in palio',
];
?>
<h1>Il bilancio</h1>

<?php if ($epocaCorrente !== null): ?>
  <section>
    <h2>Epoca <?= (int) $epocaCorrente['numero'] ?> — «<?= htmlspecialchars((string) $epocaCorrente['titolo']) ?>»</h2>
    <p>Aperta il <?= App\Nucleo\Calendario::tick((int) $epocaCorrente['inizio_tick']) ?>, dura
       <?= (int) $epocaCorrente['durata_tick'] ?> giri. <strong>Ne restano <?= $restano ?></strong>.</p>
    <p class="tenue">Quando finisce, si conta — e si scopre tutto. Ogni operazione
       coperta col suo mandante vero, ogni talpa col suo padrone, ogni lettera
       riscritta accanto all'originale. Per un'epoca intera questo gioco è fatto
       di cose non dette; quando si dicono tutte insieme, si capisce finalmente
       che partita si stava giocando.</p>
  </section>
<?php else: ?>
  <p class="tenue">Nessuna epoca in corso.</p>
<?php endif; ?>

<?php if ($epocheChiuse === []): ?>
  <p class="tenue">Nessuna epoca si è ancora chiusa: non c'è niente da contare.</p>
<?php else: ?>

<section>
  <h2>Epoca <?= (int) $epocheChiuse[0]['numero'] ?> —
      «<?= htmlspecialchars((string) $epocheChiuse[0]['titolo']) ?>», chiusa il
      <?= App\Nucleo\Calendario::tick((int) $epocheChiuse[0]['fine_tick']) ?></h2>

  <h3>Il conto</h3>
  <?php if ($classifica === []): ?><p class="tenue">Nessuno era seduto.</p><?php endif; ?>
  <?php foreach ($classifica as $i => $r): ?>
    <article class="messaggio">
      <p><strong><?= $i + 1 ?>. <?= htmlspecialchars((string) $r['giocatore']) ?></strong>
         — <?= htmlspecialchars(App\Gioco\Canale::etichettaRuolo((string) $r['ruolo'])) ?>
         di <?= htmlspecialchars((string) $r['nazione']) ?>
         · <strong><?= sprintf('%+.2f', (float) $r['totale']) ?></strong></p>
      <table class="tabella">
        <?php foreach ((array) json_decode((string) $r['voci'], true) as $voce => $v): ?>
          <?php if (abs((float) $v) >= 0.005): ?>
          <tr>
            <td><?= htmlspecialchars((string) $voce) ?></td>
            <td class="<?= (float) $v < 0 ? 'negativo' : 'positivo' ?>"><?= sprintf('%+.2f', (float) $v) ?></td>
          </tr>
          <?php endif; ?>
        <?php endforeach; ?>
      </table>
    </article>
  <?php endforeach; ?>

  <h3>Quel che nessuno sapeva</h3>
  <?php if ($rivelazioni === []): ?><p class="tenue">Un'epoca senza segreti. Difficile.</p><?php endif; ?>
  <?php foreach ($generi as $g => $titolo): ?>
    <?php if (!isset($rivelazioni[$g])): continue; endif; ?>
    <h4><?= htmlspecialchars($titolo) ?> <span class="tenue">(<?= count($rivelazioni[$g]) ?>)</span></h4>
    <?php foreach ($rivelazioni[$g] as $r): ?>
      <article class="messaggio intercetto">
        <p class="tenue"><?= App\Nucleo\Calendario::tick((int) $r['tick']) ?></p>
        <p><strong><?= htmlspecialchars((string) $r['titolo']) ?></strong></p>
        <p><?= nl2br(htmlspecialchars((string) $r['dettaglio'])) ?></p>
      </article>
    <?php endforeach; ?>
  <?php endforeach; ?>
</section>

<?php if (count($epocheChiuse) > 1): ?>
<section>
  <h3>Le epoche precedenti</h3>
  <ul class="elenco-piano">
    <?php foreach (array_slice($epocheChiuse, 1) as $e): ?>
      <li>Epoca <?= (int) $e['numero'] ?> — «<?= htmlspecialchars((string) $e['titolo']) ?>»,
          dal <?= App\Nucleo\Calendario::tick((int) $e['inizio_tick']) ?> al <?= App\Nucleo\Calendario::tick((int) $e['fine_tick']) ?></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<?php endif; ?>
