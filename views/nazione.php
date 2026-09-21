<?php
/** @var array<string,mixed> $n */
/** @var list<array<string,mixed>> $relazioni $gabinetto $fazioni */
$umori = [1=>'armonia di interessi',2=>'amicizia',3=>'cooperazione',4=>'indifferenza',
          5=>'competizione',6=>'rivalità',7=>'inimicizia'];
$etiche = [1=>'irreprensibile',2=>'onesta',3=>'collaborativa',4=>'guardinga',5=>'corrotta',6=>'spregiudicata'];
$ambizioni = [1=>'appartata',2=>'attiva',3=>'intraprendente',4=>'assertiva',5=>'aggressiva',6=>'spietata'];
$tensioni = [1=>'quiete',2=>'pace',3=>'tensione',4=>'conflitto aperto',5=>'guerra',6=>'guerra totale'];
$polizia = [1=>'società libera',2=>'perlopiù libera',3=>'ristretta',4=>'chiusa'];
$nucleare = [1=>'nessuna capacità',2=>'nucleare civile',3=>'programma avviato',4=>'ordigno provato',
             5=>'gittata regionale',6=>'gittata macro-regionale',7=>'gittata globale'];
$ruoli = App\Dati\Gabinetto::RUOLI;
?>
<h1><?= htmlspecialchars((string) $n['nome']) ?>
  <?php if ((int) $n['giocabile']): ?><span class="pastiglia">potenza giocabile</span><?php endif; ?>
</h1>
<p class="tenue"><?= htmlspecialchars((string) $n['ideologia']) ?> ·
   regione <?= htmlspecialchars((string) $n['regione']) ?> ·
   <?= htmlspecialchars($polizia[(int) $n['stato_polizia']] ?? '') ?></p>

<div class="quadranti">
  <section class="quadrante">
    <h3>Economia</h3>
    <dl>
      <dt>Prodotto interno</dt><dd><?= number_format((float) $n['pil'] / 1000, 1) ?> mld</dd>
      <dt>Reddito pro capite</dt><dd><?= number_format((float) $n['pil_pro_capite'], 0) ?></dd>
      <dt>Consumo pro capite</dt><dd><?= number_format((float) $n['consumo_pro_capite'], 0) ?></dd>
      <dt>Crescita</dt><dd><?= number_format((float) $n['crescita_pil'] * 100, 1) ?>%</dd>
      <dt>Popolazione</dt><dd><?= number_format((float) $n['popolazione'] / 1_000_000, 1) ?> mln</dd>
    </dl>
  </section>

  <section class="quadrante">
    <h3>Società</h3>
    <dl>
      <dt>Qualità della vita</dt><dd><?= (int) $n['qualita_vita'] ?>/10</dd>
      <dt>Legittimità</dt><dd><?= number_format((float) $n['legittimita'], 0) ?>/100</dd>
      <dt>Clamore sociale</dt><dd><?= number_format((float) $n['clamore_sociale'], 0) ?>%</dd>
      <dt>Maturità istituzionale</dt><dd><?= (int) $n['maturita'] ?>/255</dd>
      <dt>Tensione interna</dt><dd><?= $tensioni[(int) $n['net_peace']] ?? '—' ?></dd>
    </dl>
  </section>

  <section class="quadrante">
    <h3>Influenza</h3>
    <dl>
      <dt>Influenza totale</dt><dd><?= number_format((float) $n['influenza_totale'], 2) ?>%</dd>
      <dt>Valore di prestigio</dt><dd><?= (int) $n['valore_prestigio'] ?></dd>
      <dt>Valore strategico</dt><dd><?= (int) $n['valore_strategico'] ?>/100</dd>
      <dt>Etica percepita</dt><dd><?= $etiche[(int) $n['etica']] ?? '—' ?></dd>
      <dt>Ambizione</dt><dd><?= $ambizioni[(int) $n['ambizione']] ?? '—' ?></dd>
    </dl>
  </section>

  <section class="quadrante">
    <h3>Forze</h3>
    <dl>
      <dt>Effettivi</dt><dd><?= number_format((float) $n['soldati'], 0) ?></dd>
      <dt>Quota militare</dt><dd><?= number_format((float) $n['quota_militare'] * 100, 1) ?>% del PIL</dd>
      <dt>Potenza</dt><dd><?= number_format((float) $n['potenza_militare'], 0) ?></dd>
      <dt>Postura nucleare</dt><dd><?= $nucleare[(int) $n['postura_nucleare']] ?? '—' ?></dd>
      <dt>Insorti</dt><dd><?= number_format((float) $n['forza_insorti'], 0) ?></dd>
    </dl>
  </section>
</div>

<?php if ($gabinetto !== []): ?>
<section>
  <h2>Il gabinetto</h2>
  <table>
    <thead><tr><th>Poltrona</th><th>Titolare</th><th>Potere</th><th>Lealtà</th><th>Competenza</th></tr></thead>
    <tbody>
    <?php foreach ($gabinetto as $p): ?>
      <tr>
        <td class="tenue"><?= htmlspecialchars($ruoli[$p['ruolo']] ?? $p['ruolo']) ?></td>
        <td><?= htmlspecialchars((string) $p['nome']) ?> <span class="tenue"><?= (int) $p['eta'] ?> anni</span></td>
        <td class="num"><?= number_format((float) $p['potere'], 0) ?></td>
        <td class="num"><?= number_format((float) $p['lealta'], 0) ?></td>
        <td class="num"><?= number_format((float) $p['competenza'] * 100, 0) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php if ($fazioni !== []): ?>
  <h3>Chi lo tiene in piedi</h3>
  <ul class="fazioni">
    <?php foreach ($fazioni as $f): ?>
      <li><strong><?= htmlspecialchars((string) $f['nome']) ?></strong>
          <span class="num">forza <?= number_format((float) $f['forza'], 0) ?></span>
          <span class="num <?= (float) $f['favore'] < 0 ? 'ostile' : '' ?>">favore <?= number_format((float) $f['favore'], 0) ?></span>
          <span class="tenue">«<?= htmlspecialchars((string) $f['agenda']) ?>»</span></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</section>
<?php endif; ?>

<section>
  <h2>Rapporti</h2>
  <table>
    <thead><tr><th>Paese</th><th>Umore</th><th>Affinità</th><th>Trattato</th></tr></thead>
    <tbody>
    <?php foreach ($relazioni as $r): ?>
      <tr>
        <td><a href="<?= u('/nazione') ?>/<?= $r['codice'] ?>"><?= htmlspecialchars((string) $r['nome']) ?></a></td>
        <td class="<?= (int) $r['umore'] >= 6 ? 'ostile' : ((int) $r['umore'] <= 2 ? 'amico' : '') ?>">
            <?= $umori[(int) $r['umore']] ?? '—' ?></td>
        <td class="num"><?= number_format((float) $r['affinita'], 0) ?></td>
        <td class="tenue"><?= (int) $r['obbligo'] >= 96 ? 'difesa' : ((int) $r['obbligo'] >= 64 ? 'basi' : ((int) $r['obbligo'] >= 32 ? 'commercio' : '—')) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
