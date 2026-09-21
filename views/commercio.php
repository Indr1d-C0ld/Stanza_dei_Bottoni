<?php
/** @var array<string,mixed> $poltrona */
/** @var list<array<string,mixed>> $fornitori $clienti $strozzature */
/** @var array<string,string> $nomi */
$settoriIt = ['energia'=>'energia','cibo'=>'cibo','tecnologia'=>'tecnologia',
              'finanza'=>'finanza','manifattura'=>'manifattura'];
$pct = fn(float $x): string => number_format(100 * $x, 2, ',', '.') . '%';
?>
<h1>Il commercio</h1>
<p class="tenue">Un embargo non è un pulsante. Chiudere un rubinetto fa male a
   chi resta senza — ma quanto male dipende da che cosa comprava da noi e da
   quanto in fretta lo trova altrove — e fa male anche a noi, perché smettiamo
   di essere pagati. Le due cifre stanno qui sotto, accanto a ogni nome.</p>
<p class="tenue">Le dotazioni non vengono da statistiche commerciali vere: il
   modello ricava la forma generale dai dati del seme e le vocazioni note
   stanno dichiarate in <code>db/seed/commercio-noto.php</code>.</p>

<section>
  <h2>Da chi dipendiamo</h2>
  <p class="tenue">Ordinati per quanto ci costerebbe, in punti di crescita
     annua, se chiudessero. La sostituibilità dice quanto in fretta si trova un
     altro fornitore: bassa vuol dire che non si trova.</p>
  <?php if ($fornitori === []): ?><p class="tenue">Non dipendiamo da nessuno in modo rilevante.</p><?php endif; ?>
  <table class="tabella">
    <tr><th>paese</th><th>che cosa ci vende</th><th>se chiudono</th></tr>
    <?php foreach ($fornitori as $f): ?>
      <tr>
        <td><strong><?= htmlspecialchars($nomi[$f['iso']] ?? $f['iso']) ?></strong></td>
        <td class="tenue">
          <?php foreach ($f['settori'] as $s): ?>
            <?= htmlspecialchars($settoriIt[$s['settore']] ?? $s['settore']) ?>
            <?= $pct($s['quota']) ?> del fabbisogno
            <span class="tenue">(sost. <?= (int) $s['sostituibilita'] ?>)</span><br>
          <?php endforeach; ?>
        </td>
        <td class="negativo">&minus;<?= $pct($f['danno']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</section>

<section>
  <h2>Chi compra da noi</h2>
  <p class="tenue">Questa è la nostra leva — e il suo prezzo. La prima colonna
     è quel che costerebbe a loro un nostro embargo; la seconda quel che
     costerebbe a noi. Quando la seconda è più grande della prima, l'embargo è
     un'arma puntata contro di noi.</p>
  <?php if ($clienti === []): ?><p class="tenue">Nessuno dipende da noi: non abbiamo questa leva.</p><?php endif; ?>
  <table class="tabella">
    <tr><th>paese</th><th>che cosa gli vendiamo</th><th>costa a loro</th><th>costa a noi</th></tr>
    <?php foreach ($clienti as $k): ?>
      <tr>
        <td><strong><?= htmlspecialchars($nomi[$k['iso']] ?? $k['iso']) ?></strong></td>
        <td class="tenue">
          <?php foreach (array_slice($k['settori'], 0, 3) as $s): ?>
            <?= htmlspecialchars($settoriIt[$s['settore']] ?? $s['settore']) ?><?= ' ' ?>
          <?php endforeach; ?>
        </td>
        <td class="<?= $k['costa_a_loro'] > $k['costa_a_noi'] ? 'positivo' : 'negativo' ?>">
          &minus;<?= $pct($k['costa_a_loro']) ?></td>
        <td class="<?= $k['costa_a_noi'] >= $k['costa_a_loro'] ? 'negativo' : 'tenue' ?>">
          &minus;<?= $pct($k['costa_a_noi']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</section>

<section>
  <h2>I rubinetti chiusi nel mondo</h2>
  <?php if ($strozzature === []): ?>
    <p class="tenue">Nessuno, per ora. Il commercio scorre.</p>
  <?php else: ?>
  <table class="tabella">
    <tr><th>chi chiude</th><th>verso</th><th>quanto</th><th>a loro</th><th>a chi chiude</th><th>ancora</th></tr>
    <?php foreach ($strozzature as $s): ?>
      <tr>
        <td><?= htmlspecialchars((string) $s['fornitore']) ?></td>
        <td><?= htmlspecialchars((string) $s['cliente']) ?></td>
        <td><?= number_format(100 * $s['quota'], 0) ?>%</td>
        <td class="negativo">&minus;<?= $pct($s['costa_al_cliente']) ?></td>
        <td class="negativo">&minus;<?= $pct($s['costa_al_fornitore']) ?></td>
        <td class="tenue"><?= (int) $s['restano'] ?> giri</td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</section>
