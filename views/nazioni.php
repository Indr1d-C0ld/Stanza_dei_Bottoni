<?php defined('BASE') || exit; // si include da index.php, non si apre dal browser ?>
<?php
/** @var list<array<string,mixed>> $nazioni */
/** @var string $ordine */
$colonne = [
    'nome' => 'Nazione', 'influenza_totale' => 'Influenza', 'pil' => 'PIL',
    'legittimita' => 'Legittimità', 'qualita_vita' => 'Qualità della vita',
    'net_peace' => 'Tensione',
];
$tensioni = [1=>'quiete',2=>'pace',3=>'tensione',4=>'conflitto',5=>'guerra',6=>'guerra totale'];
?>
<h1>Le nazioni</h1>
<p class="tenue"><?= count($nazioni) ?> Stati sovrani. Ordina per:
<?php foreach ($colonne as $c => $etichetta): ?>
  <a class="<?= $ordine === $c ? 'attivo' : '' ?>" href="<?= u('/nazioni') ?>?ordine=<?= $c ?>"><?= $etichetta ?></a>
<?php endforeach; ?>
</p>
<table>
  <thead><tr><th>Nazione</th><th>Regione</th><th>Influenza</th><th>PIL</th><th>Legittimità</th><th>QdV</th><th>Tensione</th></tr></thead>
  <tbody>
  <?php foreach ($nazioni as $n): ?>
    <tr<?= (int) $n['net_peace'] >= 4 ? ' class="in-crisi"' : '' ?>>
      <td><a href="<?= u('/nazione') ?>/<?= $n['codice'] ?>"><?= htmlspecialchars($n['nome']) ?></a></td>
      <td class="tenue"><?= htmlspecialchars((string) $n['regione']) ?></td>
      <td class="num"><?= n((float) $n['influenza_totale'], 2) ?>%</td>
      <td class="num"><?= n((float) $n['pil'] / 1000, 0) ?></td>
      <td class="num"><?= n((float) $n['legittimita'], 0) ?></td>
      <td class="num"><?= (int) $n['qualita_vita'] ?></td>
      <td class="tenue"><?= $tensioni[(int) $n['net_peace']] ?? '—' ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
