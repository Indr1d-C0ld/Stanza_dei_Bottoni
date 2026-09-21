<?php

declare(strict_types=1);

namespace App\Dati;

use RuntimeException;

/**
 * Il mondo in memoria.
 *
 * Serve a due cose: far girare il motore a vuoto per anni senza toccare la base
 * dati (gli ensemble del simulatore autonomo devono essere veloci), e collaudare
 * le formule prima che esista una riga di persistenza.
 */
final class Mondo
{
    /** @var array<string,Nazione> per ISO3 */
    public array $nazioni = [];

    public Relazioni $relazioni;
    public Intelligence $intelligence;
    public Commercio $commercio;

    /**
     * I flussi commerciali chiusi o ridotti, con la loro scadenza. Stanno qui
     * e non solo nel database perche' il motore gira anche a vuoto, senza
     * database, e un embargo deve funzionare lo stesso.
     *
     * @var list<array{fornitore:string,cliente:string,quota:float,fine:int}>
     */
    public array $strozzature = [];
    /** @var array<string,Gabinetto> solo le potenze giocabili ne hanno uno */
    public array $gabinetti = [];
    /** @var array<string,string> ideologia formale per ISO3, serve solo all'avvio */
    public array $ideologie = [];
    /** @var list<Evento> tutti gli eventi, in volo e conclusi */
    public array $eventi = [];
    public int   $prossimoIdEvento = 1;
    /** @var list<array<string,mixed>> il feed pubblico: cio' che il mondo sa */
    public array $notizie = [];
    /** @var list<array<string,mixed>> le guerre aperte fra Stati */
    public array $guerre = [];
    /** @var array<string,int> "MANDANTE|verbo|BERSAGLIO" => tick dell'ultima volta */
    public array $azioniRecenti = [];
    public int   $tick       = 0;
    public float $nastiness  = 0.0;
    public int   $livelloPace = 2;

    public static function daSeme(string $percorsoCsv): self
    {
        if (!is_file($percorsoCsv)) {
            throw new RuntimeException("Seme non trovato: $percorsoCsv. Lancia prima bin/importa_factbook.php");
        }
        $mondo = new self();
        $fh = fopen($percorsoCsv, 'r');
        $intestazione = fgetcsv($fh, 0, ',', '"', '\\');
        while (($riga = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
            $d = array_combine($intestazione, $riga);

            $pil        = (float) $d['pil_milioni'];
            $quotaMil   = min(0.30, max(0.002, (float) $d['quota_militare']));
            $quotaInv   = 0.22;
            $quotaCons  = 1.0 - $quotaMil - $quotaInv;
            $popolazione = (float) $d['popolazione'];

            $n = new Nazione(
                iso3:             $d['iso3'],
                nome:             $d['nome'],
                regione:          $d['regione'],
                ideologiaFormale: $d['ideologia_formale'],
                maturita:         (int) $d['maturita'],
                valoreStrategico: (int) $d['valore_strategico'],
                valorePrestigio:  (int) $d['valore_prestigio'],
                areaKm2:          (int) $d['area_km2'],
                giocabile:        (bool) (int) $d['giocabile'],
                popolazione:         $popolazione,
                crescitaPopolazione: (float) $d['crescita_pop'],
                pil:                 $pil,
                crescitaPil:         (float) $d['crescita_pil'],
                // La tendenza osservata è il nostro ancoraggio: il modello la
                // fa deviare, non la inventa. Senza questo, ogni economia
                // scivola verso il tetto di calibrazione e il mondo triplica.
                crescitaStrutturale: max(-0.04, min(0.09, (float) $d['crescita_pil'])),
                crescitaBase:        max(-0.04, min(0.09, (float) $d['crescita_pil'])),
                quotaInvestimentiIniziale: $quotaInv,
                pilProCapite:        (float) $d['pil_pro_capite'],
                consumoProCapite:    (float) $d['pil_pro_capite'] * $quotaCons,
                quotaConsumi:        $quotaCons,
                quotaInvestimenti:   $quotaInv,
                quotaMilitare:       $quotaMil,
                // La legittimità non parte uguale per tutti: uno stato solido
                // che cresce ha credito, uno stato fragile che arranca no.
                legittimita:  max(18.0, min(78.0,
                    28.0 + 30.0 * ((int) $d['maturita'] / 255.0)
                    + 400.0 * (float) $d['crescita_pil']
                    - 8.0 * max(0.0, 1.0 - (float) $d['alfabetizzazione'] / 0.8)
                )),
                aspettativa:  max(0.005, min(0.06, (float) $d['crescita_pil'])),
                clamoreSociale: 0.0,
                qualitaVita:  1,
                statoPolizia: 2.0,
                ansiaMilitare: 10.0,
                controlloInfo: 50.0,
                cyberDifesa:   20.0 + 60.0 * ((int) $d['maturita'] / 255.0),
                // Base strutturale dell'etica, stabile fra le corse. Non e'
                // "buoni e cattivi": e' quanto uno Stato e' disposto ad agire
                // fuori dalle regole quando gli conviene.
                etica:        (int) max(1, min(6, 2 + (crc32($d['iso3']) % 3)
                                 + ((int) $d['maturita'] < 120 ? 1 : 0))),
                ambizione:    3,
                orientamento: 0,   // assegnato subito dopo, con gli scostamenti noti
                soldati:          (float) $d['soldati'],
                // L'equipaggiamento è uno stock, non un flusso: lo si avvia
                // capitalizzando qualche anno di spesa militare.
                equipaggiamento:  max(1.0, $pil * $quotaMil * 4.0),
                forzaInsorti:     0.0,
                netPeace:         2,
                posturaNucleare: 1,
                alfabetizzazione: (float) $d['alfabetizzazione'],
            );
            $mondo->nazioni[$n->iso3] = $n;
            $mondo->ideologie[$n->iso3] = $d['ideologia_formale'];
        }
        fclose($fh);

        // Prima l'orientamento strutturale, poi gli scostamenti dichiarati.
        $politica = @include dirname($percorsoCsv) . '/politica-nota.php';
        $politica = is_array($politica) ? $politica : ['orientamenti' => [], 'rapporti' => []];
        foreach ($mondo->nazioni as $n) {
            $n->posturaNucleare = $politica['postura_nucleare'][$n->iso3] ?? 1;
            $n->orientamento = $politica['orientamenti'][$n->iso3]
                ?? self::orientamentoDa($mondo->ideologie[$n->iso3] ?? '');
        }

        $mondo->relazioni = new Relazioni();
        $mondo->relazioni->caricaConfini(dirname($percorsoCsv) . '/confini.csv');
        $mondo->preparaRelazioni($politica['rapporti'] ?? []);

        // L'influenza serve alle condizioni iniziali dell'intelligence e la
        // calcola la fase 06: qui una stima grezza per non partire da zero.
        $pilTotale = max(1.0, $mondo->pilTotale());
        foreach ($mondo->nazioni as $n) {
            $n->influenzaTotale = 100.0 * $n->pil / $pilTotale;
        }
        $mondo->intelligence = Intelligence::iniziale($mondo, $politica['capacita_intelligence'] ?? []);
        $vocazioni = [];
        $fileCommercio = dirname($percorsoCsv) . '/commercio-noto.php';
        if (is_file($fileCommercio)) {
            $vocazioni = (array) ((require $fileCommercio)['vocazione'] ?? []);
        }
        // Il grafo si costruisce col seme; la scala del danno arriva dalla
        // calibrazione, che qui non c'e' ancora: la mette la fase 03.
        $mondo->commercio    = Commercio::iniziale($mondo, $vocazioni);

        return $mondo;
    }

    /**
     * [FABBRICATO] Posizione sull'asse politico a partire dall'ideologia
     * formale. -128 estrema sinistra, +128 estrema destra, come in Balance of
     * Power. Serve a dare un attrattore all'affinità: senza, tutti partono
     * identici e nessun blocco si forma mai.
     */
    private static function orientamentoDa(string $ideologia): int
    {
        return match ($ideologia) {
            'democrazia_liberale', 'democrazia_federale' =>  45,
            'monarchia_costituzionale'                   =>  35,
            'comunismo', 'partito_unico'                 => -85,
            'giunta_militare'                            => -55,
            'autoritarismo'                              => -45,
            'monarchia_assoluta'                         => -35,
            'teocrazia', 'islam_politico'                => -30,
            default                                      =>   0,
        };
    }

    /**
     * Costruisce le coppie che hanno una ragione di esistere, e le inizializza.
     *
     * Le affinità iniziali sono FABBRICATE: non abbiamo (ancora) dati reali su
     * alleanze e trattati. Derivano da distanza ideologica, contiguità e
     * asimmetria di potenza — tre cose che spiegano molto e non spiegano tutto.
     */
    /** @param list<array{0:string,1:string,2:int}> $rapportiNoti */
    public function preparaRelazioni(array $rapportiNoti = []): void
    {
        $elenco = $this->elenco();
        usort($elenco, static fn(Nazione $a, Nazione $b): int => $b->pil <=> $a->pil);
        $potenze = array_slice($elenco, 0, 24);
        $grandi  = array_slice($elenco, 0, 6);

        $coppie = [];
        $aggiungi = static function (Nazione $a, Nazione $b) use (&$coppie): void {
            if ($a->iso3 !== $b->iso3) {
                $coppie[$a->iso3 . '|' . $b->iso3] = [$a, $b];
            }
        };

        foreach ($potenze as $a) {
            foreach ($potenze as $b) { $aggiungi($a, $b); }
        }
        foreach ($this->elenco() as $n) {
            foreach ($grandi as $g) { $aggiungi($n, $g); $aggiungi($g, $n); }
            foreach ($this->relazioni->vicinato[$n->iso3] ?? [] as $isoVicino) {
                $v = $this->nazioni[$isoVicino] ?? null;
                if ($v !== null) { $aggiungi($n, $v); $aggiungi($v, $n); }
            }
        }

        foreach ($coppie as [$a, $b]) {
            $confinanti = $this->relazioni->confinanti($a->iso3, $b->iso3);

            // Distanza ideologica. Il valore di riposo fra due Stati che non
            // hanno nulla da spartire e' l'INDIFFERENZA, non l'amicizia:
            // partendo da 75 il mondo diventava un club di amici.
            $affinita = 25.0 - abs($a->orientamento - $b->orientamento) * 0.55;

            // La contiguita' scalda i simili e raffredda i diversi: si litiga
            // con chi si ha vicino, e ci si allea con chi si ha vicino.
            if ($confinanti) {
                $affinita += abs($a->orientamento - $b->orientamento) < 30 ? 8.0 : -18.0;
            }
            // Chi ha un gigante alle porte lo guarda con sospetto.
            if ($confinanti && $b->pil > $a->pil * 8.0) {
                $affinita -= 12.0;
            }

            $affinita = max(-127.0, min(127.0, $affinita));
            $r = new Relazione(
                affinita:   $affinita,
                confinanti: $confinanti,
                ancora:     $affinita,
            );
            $r->aggiornaUmore();
            $this->relazioni->imposta($a->iso3, $b->iso3, $r);
        }

        // Gli scostamenti dichiarati vincono sulla formula: se una coppia non
        // esisteva ancora, la si crea — un'inimicizia storica e' una relazione
        // anche fra paesi che non confinano e non contano nulla.
        $dichiarate = [];
        foreach ($rapportiNoti as [$da, $a2, $valore]) {
            $dichiarate[$da . '|' . $a2] = true;
        }
        foreach ($rapportiNoti as [$da, $a2, $valore]) {
            if (!isset($this->nazioni[$da], $this->nazioni[$a2])) {
                continue;
            }
            $r = $this->relazioni->fra($da, $a2)
                ?? new Relazione(confinanti: $this->relazioni->confinanti($da, $a2));
            $r->affinita = (float) $valore;
            $r->ancora   = (float) $valore;
            $r->aggiornaUmore();
            $this->relazioni->imposta($da, $a2, $r);

            // Se il reciproco non e' dichiarato lo si specchia attenuato: un
            // rapporto noto sovrascrive comunque la formula strutturale, anche
            // dove questa aveva gia' prodotto un valore.
            if (!isset($dichiarate[$a2 . '|' . $da])) {
                $rr = $this->relazioni->fra($a2, $da)
                    ?? new Relazione(confinanti: $this->relazioni->confinanti($a2, $da));
                $rr->affinita = (float) $valore * 0.9;
                $rr->ancora   = (float) $valore * 0.9;
                $rr->aggiornaUmore();
                $this->relazioni->imposta($a2, $da, $rr);
            }
        }

        // [FABBRICATO] Obblighi di trattato iniziali, dedotti dall'affinita'.
        // Senza trattati l'integrita' non ha su cosa mordere, e l'integrita' e'
        // il meccanismo che rende costose le promesse. Da sostituire con
        // Correlates of War.
        $grandiIso = [];
        foreach ($grandi as $g) {
            $grandiIso[$g->iso3] = true;
        }
        foreach ($this->relazioni->tutte() as $chiave => $r) {
            [$da, $verso] = explode('|', $chiave);
            // Un trattato e' un atto raro e costoso, non il sottoprodotto di
            // una simpatia. Si garantisce un vicino, o un cliente di peso.
            $plausibile = $r->confinanti || isset($grandiIso[$da]) || isset($grandiIso[$verso]);
            $r->obbligo = ($plausibile && $r->affinita >= 80.0)
                ? ($r->affinita >= 105.0 ? 96 : 64)
                : (($plausibile && $r->affinita >= 55.0) ? 32 : 0);
        }
    }

    /** @return list<Nazione> in ordine deterministico: mai affidarsi all'ordine implicito. */
    public function elenco(): array
    {
        $elenco = array_values($this->nazioni);
        usort($elenco, static fn(Nazione $a, Nazione $b): int => strcmp($a->iso3, $b->iso3));
        return $elenco;
    }

    public function pilTotale(): float
    {
        return array_sum(array_map(static fn(Nazione $n): float => $n->pil, $this->nazioni));
    }
}
