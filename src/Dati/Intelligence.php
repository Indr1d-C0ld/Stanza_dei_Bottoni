<?php

declare(strict_types=1);

namespace App\Dati;

/**
 * L'apparato di intelligence del mondo.
 *
 * Il modello è FATTORIZZATO: non teniamo una copertura per ogni terna
 * (servizio × paese × disciplina) — sarebbero duecentomila celle quasi tutte a
 * zero. Teniamo invece due cose che si moltiplicano:
 *
 *   capacità[servizio][disciplina]  quanto vale quel servizio in quel mestiere
 *   presenza[servizio][bersaglio]   quanto è radicato in quel paese
 *
 * È una semplificazione vera e va detta: implica che un servizio bravo con i
 * segnali lo sia ovunque abbia presenza, il che non è del tutto realistico. In
 * cambio costa poco e conserva la cosa che conta — che la copertura sia una
 * risorsa scarsa, costruita nel tempo e distribuita male.
 */
final class Intelligence
{
    public const DISCIPLINE = ['osint', 'humint', 'sigint', 'imint', 'cyber', 'finint'];

    /**
     * Quale disciplina serve per scoprire cosa. Ogni dominio ha il suo mestiere
     * principale e uno di rincalzo: le intenzioni si rubano alle persone, i
     * movimenti si vedono dal cielo, il denaro si segue nei conti.
     *
     * @var array<string,list<string>>
     */
    public const PERTINENTI = [
        'soc'  => ['osint', 'humint'],
        'eco'  => ['osint', 'finint'],
        'info' => ['cyber', 'osint'],
        'int'  => ['humint', 'finint'],
        'mil'  => ['imint', 'sigint'],
        'nuc'  => ['imint', 'humint'],
    ];

    /** @var array<string,array<string,float>> capacità[iso3][disciplina] 0..1 */
    public array $capacita = [];

    /** @var array<string,array<string,float>> presenza[iso3][bersaglio] 0..1 */
    public array $presenza = [];

    /** @var array<string,int> "OSSERVATORE|idEvento" => livello raggiunto (1..4) */
    public array $conoscenza = [];

    /** @var list<array<string,mixed>> i rapporti prodotti, in coda recente */
    public array $rapporti = [];

    /** @var array<int,list<string>> idEvento => chi e' arrivato a dimostrarlo */
    public array $attribuito = [];

    /**
     * Chi ciascun osservatore CREDE sia il mandante. Non sempre coincide con
     * chi lo e' davvero, ed e' esattamente lì che vive metà del gioco.
     *
     * @var array<int,array<string,string>> idEvento => osservatore => accusato
     */
    public array $accusa = [];

    public function accusato(string $osservatore, int $evento, string $ripiego): string
    {
        return $this->accusa[$evento][$osservatore] ?? $ripiego;
    }

    public function livello(string $osservatore, int $evento): int
    {
        return $this->conoscenza[$osservatore . '|' . $evento] ?? 0;
    }

    public function imponiLivello(string $osservatore, int $evento, int $livello): void
    {
        $chiave = $osservatore . '|' . $evento;
        // La conoscenza non regredisce MAI: i quattro livelli sono monotoni.
        $this->conoscenza[$chiave] = max($this->conoscenza[$chiave] ?? 0, $livello);
    }

    public function copertura(string $osservatore, string $bersaglio, string $disciplina): float
    {
        return ($this->capacita[$osservatore][$disciplina] ?? 0.0)
            * ($this->presenza[$osservatore][$bersaglio] ?? 0.0);
    }

    /**
     * Vedere in casa propria e' un mestiere diverso dal vedere all'estero: non
     * serve presenza, serve controspionaggio. Senza questa distinzione un
     * paese non si accorgeva nemmeno di cio' che gli accadeva in casa, perche'
     * la presenza di uno Stato dentro se' stesso non esiste nella matrice.
     */
    public function coperturaInterna(string $iso, string $disciplina, float $solidita): float
    {
        return ($this->capacita[$iso][$disciplina] ?? 0.0) * (0.35 + 0.55 * $solidita);
    }

    /**
     * Condizioni iniziali. [FABBRICATO] — non esiste alcuna fonte pubblica
     * seria sulla copertura di intelligence fra paesi, e questa è una stima
     * dichiarata: si deduce da peso, istituzioni, vicinanza e inimicizia.
     */
    /** @param array<string,float> $moltiplicatori */
    public static function iniziale(Mondo $mondo, array $moltiplicatori = []): self
    {
        $i = new self();

        foreach ($mondo->elenco() as $n) {
            $peso     = min(1.0, $n->influenzaTotale / 12.0);
            $solidita = $n->maturita / 255.0;
            $ricchezza = min(1.0, $n->pilProCapite / 45000.0);

            // Ogni servizio ha un profilo: chi ha molti soldi e poche persone
            // sul terreno compra satelliti, chi ha molte persone e pochi soldi
            // compra informatori. La variazione per paese è stabile fra le
            // corse (dipende dal codice) perché è una caratteristica, non caso.
            $inclinazione = (crc32($n->iso3) % 100) / 100.0;

            // Il peso economico e' una misura ingannevole della capacita' di
            // vedere: alcuni Stati piccoli vedono moltissimo. Gli scostamenti
            // noti stanno in db/seed/politica-nota.php.
            $m = $moltiplicatori[$n->iso3] ?? 1.0;
            $base = (0.12 + 0.55 * $peso + 0.20 * $solidita) * $m;
            $i->capacita[$n->iso3] = [
                'osint'  => min(1.0, 0.30 + 0.50 * $solidita),
                'humint' => min(1.0, $base * (0.7 + 0.6 * (1.0 - $inclinazione))),
                'sigint' => min(1.0, $base * (0.6 + 0.8 * $ricchezza)),
                'imint'  => min(1.0, $base * (0.4 + 1.1 * $peso)),
                'cyber'  => min(1.0, $base * (0.5 + 0.9 * $ricchezza * $inclinazione)),
                'finint' => min(1.0, $base * (0.5 + 0.7 * $ricchezza)),
            ];
        }

        // La presenza si concentra dove c'è interesse: i vicini, i rivali, e
        // chiunque conti. Nessuno sorveglia tutti: è il vincolo che rende
        // l'intelligence una scelta e non una barra da riempire.
        foreach ($mondo->relazioni->tutte() as $chiave => $r) {
            [$da, $verso] = explode('|', $chiave);
            $b = $mondo->nazioni[$verso] ?? null;
            $a = $mondo->nazioni[$da] ?? null;
            if ($a === null || $b === null) {
                continue;
            }
            $interesse = 0.10
                + 0.45 * (abs($r->affinita) / 127.0)
                + ($r->confinanti ? 0.25 : 0.0)
                + 0.30 * min(1.0, $b->valorePrestigio / 400.0);
            $i->presenza[$da][$verso] = min(1.0, $interesse * (0.4 + 0.9 * min(1.0, $a->influenzaTotale / 10.0)));
        }

        return $i;
    }
}
