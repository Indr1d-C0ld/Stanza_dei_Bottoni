<?php
/**
 * Il disegno del planisfero.
 *
 * @var App\Dati\Planisfero $planisfero
 * @var array<string,array{colore:string,valore:string}> $colori
 * @var array<string,string> $nomiMappa   iso3 => nome, per le etichette
 * @var string $letturaScelta
 * @var bool $cliccabile
 */
?>
<div class="planisfero">
  <svg viewBox="0 0 <?= $planisfero->larghezza ?> <?= $planisfero->altezza ?>"
       xmlns="http://www.w3.org/2000/svg" role="img"
       aria-label="Planisfero: <?= htmlspecialchars(App\Dati\Planisfero::LETTURE[$letturaScelta]['titolo'] ?? '') ?>">
    <rect x="0" y="0" width="<?= $planisfero->larghezza ?>" height="<?= $planisfero->altezza ?>"
          class="mare"/>
    <?php foreach ($planisfero->tracciati as $iso => $d): ?>
      <?php
        $c = $colori[$iso] ?? null;
        $nome = $nomiMappa[$iso] ?? $iso;
      ?>
      <?php if ($cliccabile): ?><a href="<?= u('/nazione/' . $iso) ?>"><?php endif; ?>
      <path d="<?= $d ?>"
            fill="<?= $c !== null ? $c['colore'] : '#2a3038' ?>"
            class="paese"><title><?= htmlspecialchars($nome) ?><?php
              if ($c !== null): ?> — <?= htmlspecialchars($c['valore']) ?><?php
              endif; ?></title></path>
      <?php if ($cliccabile): ?></a><?php endif; ?>
    <?php endforeach; ?>
  </svg>
</div>
