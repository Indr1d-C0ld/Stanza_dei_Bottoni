<?php
/** @var list<array<string,mixed>> $libere */
$ruoli = App\Dati\Gabinetto::RUOLI;
$per = [];
foreach ($libere as $p) { $per[$p['nazione']][] = $p; }
?>
<h1>Le poltrone</h1>
<?php if ($poltrona !== null): ?>
  <p>Occupi la poltrona di <strong><?= htmlspecialchars($ruoli[$poltrona['ruolo']] ?? '') ?></strong>
     di <strong><?= htmlspecialchars((string) $poltrona['nazione']) ?></strong>.</p>
  <form method="post" action="" class="modulo">
    <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
    <input type="hidden" name="azione" value="lascia">
    <button type="submit" class="pericolo">Lascia la poltrona</button>
  </form>
<?php else: ?>
<p class="tenue">Ogni potenza giocabile ha otto poltrone. Vedrai soltanto il tuo
dominio: il gabinetto esiste perché il mondo è troppo grande per una persona sola.
Le poltrone vuote restano in mano all'apparato, che decide da sé.</p>
<?php foreach ($per as $nazione => $poltrone): ?>
  <h2><?= htmlspecialchars((string) $nazione) ?>
      <span class="tenue"><?= number_format((float) $poltrone[0]['influenza_totale'], 2) ?>% di influenza</span></h2>
  <table>
    <tbody>
    <?php foreach ($poltrone as $p): ?>
      <tr>
        <td><?= htmlspecialchars($ruoli[$p['ruolo']] ?? (string) $p['ruolo']) ?></td>
        <td class="tenue">titolare uscente: <?= htmlspecialchars((string) $p['titolare']) ?></td>
        <td class="num">potere <?= number_format((float) $p['potere'], 0) ?></td>
        <td>
          <form method="post" action="" class="in-linea">
            <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
            <input type="hidden" name="azione" value="occupa">
            <input type="hidden" name="poltrona" value="<?= (int) $p['id'] ?>">
            <button type="submit">Prendi posto</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endforeach; ?>
<?php endif; ?>
