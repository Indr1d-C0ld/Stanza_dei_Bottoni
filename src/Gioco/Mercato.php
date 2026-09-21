<?php

declare(strict_types=1);

namespace App\Gioco;

use App\Dati\Commercio;
use App\Dati\Deposito;
use App\Dati\Mondo;
use App\Nucleo\Basedati;
use App\Nucleo\Calibrazione;

/**
 * Il commercio come lo vede chi siede a una scrivania.
 *
 * Il grafo non sta nel database e non ci deve stare: e' dato derivato, si
 * ricostruisce dal seme piu' lo stato in un ventesimo di secondo, e una copia
 * salvata sarebbe solo un'altra cosa che la memoria puo' riscrivere sopra —
 * un errore che questo progetto ha gia' fatto tre volte. Qui si ricostruisce
 * su richiesta, e solo quando qualcuno guarda la pagina del commercio.
 */
final class Mercato
{
    private ?Mondo $mondo = null;

    public function __construct(
        private readonly Basedati $db,
        private readonly Calibrazione $cal,
        private readonly string $radice,
    ) {}

    private function mondo(): Mondo
    {
        if ($this->mondo === null) {
            $m = Mondo::daSeme($this->radice . '/db/seed/nazioni.csv');
            $tick = (int) $this->db->esegui('SELECT COALESCE(MAX(tick), 0) FROM sdb_mondo_stato')
                ->fetchColumn();
            (new Deposito($this->db))->ripristina($m, $tick);
            $m->commercio->morso = $this->cal->numero('commercio.morso', 0.42);
            $m->commercio->quotaFornitore = $this->cal->numero('commercio.quota_fornitore', 0.45);
            $this->mondo = $m;
        }
        return $this->mondo;
    }

    public function commercio(): Commercio
    {
        return $this->mondo()->commercio;
    }

    /** @return array<string,string> iso3 => nome, per le tabelle */
    public function nomi(): array
    {
        $per = [];
        foreach ($this->mondo()->nazioni as $n) {
            $per[$n->iso3] = $n->nome;
        }
        return $per;
    }

    /**
     * Da chi dipendiamo, e quanto fa male se chiudono.
     *
     * @return list<array<string,mixed>>
     */
    public function fornitori(string $iso, int $quanti = 14): array
    {
        $c = $this->commercio();
        $righe = [];
        foreach ($c->fornitoriDi($iso, 60) as $r) {
            $chiave = $r['fornitore'];
            $righe[$chiave] ??= ['iso' => $chiave, 'settori' => [], 'quota_max' => 0.0, 'danno' => 0.0];
            $righe[$chiave]['settori'][] = $r;
            $righe[$chiave]['quota_max'] = max($righe[$chiave]['quota_max'], $r['quota']);
        }
        foreach ($righe as $k => $v) {
            $righe[$k]['danno'] = $c->dannoAlCliente($k, $iso);
        }
        usort($righe, static fn($a, $b) => $b['danno'] <=> $a['danno']);
        return array_slice(array_values($righe), 0, $quanti);
    }

    /**
     * Chi compra da noi — che è la nostra leva, se mai servisse.
     *
     * @return list<array<string,mixed>>
     */
    public function clienti(string $iso, int $quanti = 14): array
    {
        $c = $this->commercio();
        $righe = [];
        foreach ($c->clientiDi($iso, 60) as $r) {
            $chiave = $r['cliente'];
            $righe[$chiave] ??= ['iso' => $chiave, 'settori' => [], 'quota' => 0.0];
            $righe[$chiave]['settori'][] = $r;
            $righe[$chiave]['quota'] += $r['quota'];
        }
        foreach ($righe as $k => $v) {
            $righe[$k]['costa_a_loro'] = $c->dannoAlCliente($iso, $k);
            $righe[$k]['costa_a_noi']  = $c->dannoAlFornitore($iso, $k);
        }
        usort($righe, static fn($a, $b) => $b['costa_a_loro'] <=> $a['costa_a_loro']);
        return array_slice(array_values($righe), 0, $quanti);
    }

    /**
     * Che cosa costerebbe chiudere i rubinetti verso ciascuno: a loro e a noi.
     *
     * È la tabella che deve stare davanti al giocatore prima che ordini un
     * embargo, perché è l'unica informazione che trasforma il gesto in una
     * decisione. Un embargo verso chi non ci compra niente è teatro; verso chi
     * ci compra il gas è un atto di guerra economica che paghiamo anche noi.
     *
     * @return list<array<string,mixed>>
     */
    public function armiDisponibili(string $iso, int $quanti = 12): array
    {
        return $this->clienti($iso, $quanti);
    }

    /** I rubinetti chiusi adesso nel mondo. @return list<array<string,mixed>> */
    public function strozzature(int $tick): array
    {
        $nomi = $this->nomi();
        $c = $this->commercio();
        $righe = [];
        foreach ($this->mondo()->strozzature as $s) {
            $righe[] = [
                'fornitore' => $nomi[$s['fornitore']] ?? $s['fornitore'],
                'cliente'   => $nomi[$s['cliente']] ?? $s['cliente'],
                'quota'     => (float) $s['quota'],
                'fine'      => (int) $s['fine'],
                'restano'   => max(0, (int) $s['fine'] - $tick),
                'costa_al_cliente'   => $c->dannoAlCliente($s['fornitore'], $s['cliente'], (float) $s['quota']),
                'costa_al_fornitore' => $c->dannoAlFornitore($s['fornitore'], $s['cliente'], (float) $s['quota']),
            ];
        }
        usort($righe, static fn($a, $b) => $b['costa_al_cliente'] <=> $a['costa_al_cliente']);
        return $righe;
    }
}
