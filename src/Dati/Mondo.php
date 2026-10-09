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
    /** @var list<array<string,mixed>> le guerre finite in QUESTO tick, col loro esito: le legge il Deposito */
    public array $guerreConcluse = [];
    /** @var array<string,int> "MANDANTE|verbo|BERSAGLIO" => tick dell'ultima volta */
    public array $azioniRecenti = [];
    public int   $tick       = 0;
    public float $nastiness  = 0.0;
    public int   $livelloPace = 2;
    /**
     * La crescita PRO CAPITE di medio periodo del paese mediano, secondo il
     * FMI: e' la media verso cui, oltre l'orizzonte delle proiezioni, tornano
     * le tendenze dei paesi (Pritchett e Summers 2014, fase 03).
     */
    public float $crescitaProCapiteMediana = 0.02;
    /**
     * Le rivalita' fra Stati: coppia «AAA|BBB» in ordine alfabetico => dispute
     * militarizzate nel 2006-2025 (db/seed/rivalita.php). La fase 00 ne fa il
     * rischio annuo di guerra.
     *
     * @var array<string,int>
     */
    public array $rivalita = [];
    /**
     * Il paese mediano del seme — reddito, popolazione, terreno — rispetto a
     * cui si misura il rischio di guerra civile di ciascuno (fase 05).
     *
     * @var array{reddito:float,popolazione:float,montuoso:float}
     */
    public array $mediano = ['reddito' => 12000.0, 'popolazione' => 9.0e6, 'montuoso' => 11.8];

    public static function daSeme(string $percorsoCsv): self
    {
        if (!is_file($percorsoCsv)) {
            throw new RuntimeException("Seme non trovato: $percorsoCsv. Lancia prima bin/importa_factbook.php");
        }
        $mondo = new self();
        // I file del seme che stanno accanto al CSV — V-Dem, alleanze — si
        // leggono per cartella, e la cartella serve anche piu' avanti.
        $mondo->cartellaSeme = dirname($percorsoCsv);
        // L'indice di democrazia liberale di V-Dem, accanto al seme. Se manca
        // il mondo gira lo stesso, con tutti alla mediana mondiale — ma il
        // modello di Goldstone non distinguerebbe piu' niente, quindi vale la
        // pena accorgersene.
        $democrazia = @include dirname($percorsoCsv) . '/democrazia.php';
        $gini = @include dirname($percorsoCsv) . '/disuguaglianza.php';
        $epr = @include dirname($percorsoCsv) . '/esclusione.php';
        $conflitti = @include dirname($percorsoCsv) . '/conflitti-noti.php';
        // La crescita del FMI (bin/importa_fmi.php): recente e di medio
        // periodo, con lo stesso anno per tutti. Chi non c'e' resta al Factbook.
        $fmi = @include dirname($percorsoCsv) . '/crescita.php';
        if (!is_array($fmi)) {
            $fmi = [];
        }
        // Le rivalita' fra Stati (bin/importa_rivalita.php).
        $rivalita = @include dirname($percorsoCsv) . '/rivalita.php';
        foreach (is_array($rivalita) ? $rivalita : [] as $coppia => $v) {
            $mondo->rivalita[(string) $coppia] = (int) ($v['dispute'] ?? 0);
        }
        // Il terreno e il petrolio (bin/importa_terreno.php).
        $terreno = @include dirname($percorsoCsv) . '/terreno.php';
        if (!is_array($terreno)) {
            $terreno = [];
        }
        // Repressione e censura di V-Dem (bin/importa_repressione.php).
        $diritti = @include dirname($percorsoCsv) . '/repressione.php';
        if (!is_array($diritti)) {
            $diritti = [];
        }
        // La qualita' del governo della Banca Mondiale (bin/importa_wgi.php).
        $wgi = @include dirname($percorsoCsv) . '/governo.php';
        if (!is_array($wgi)) {
            $wgi = [];
        }
        if (!is_array($conflitti)) {
            $conflitti = [];
        }
        if (!is_array($epr)) {
            $epr = [];
        }
        if (!is_array($gini)) {
            $gini = [];
        }
        if (!is_array($democrazia)) {
            $democrazia = [];
        }
        $fh = fopen($percorsoCsv, 'r');
        $intestazione = fgetcsv($fh, 0, ',', '"', '\\');
        while (($riga = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
            $d = array_combine($intestazione, $riga);

            $pil        = (float) $d['pil_milioni'];
            $quotaMil   = min(0.30, max(0.002, (float) $d['quota_militare']));
            $quotaInv   = 0.22;
            $quotaCons  = 1.0 - $quotaMil - $quotaInv;
            $popolazione = (float) $d['popolazione'];
            $recente = (float) ($fmi[$d['iso3']]['recente'] ?? $d['crescita_pil']);
            $lungo   = (float) ($fmi[$d['iso3']]['lungo'] ?? $d['crescita_pil']);

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
                crescitaPil:         $recente,
                // La tendenza di medio periodo è il nostro ancoraggio: il
                // modello la fa deviare, non la inventa. Senza questo, ogni
                // economia scivola verso il tetto di calibrazione e il mondo
                // triplica. E' la proiezione 2026-2030 del FMI, non la
                // crescita degli ultimi anni: quella diceva -17,7% per il
                // Venezuela, fermo al 2018 nel Factbook (docs/30).
                crescitaStrutturale: max(-0.04, min(0.09, $lungo)),
                crescitaBase:        max(-0.04, min(0.09, $lungo)),
                quotaInvestimentiIniziale: $quotaInv,
                quotaMilitareIniziale: $quotaMil,
                soldatiIniziali:      (float) $d['soldati'],
                democrazia:           $democrazia[$d['iso3']] ?? 0.355,
                disuguaglianza:       $gini[$d['iso3']] ?? 0.352,
                esclusioneEtnica:     (float) ($epr[$d['iso3']]['esclusa'] ?? 0.0),
                gruppiEsclusi:        (int) ($epr[$d['iso3']]['gruppi'] ?? 0),
                // Il reddito pro capite e' PIL/popolazione fin dall'inizio: e'
                // la definizione che la fase 03 usa a ogni tick. Prima partiva
                // dalla cifra del Factbook, che non coincide col rapporto dei
                // due campi (da 0,34 volte a Cuba a 2,71 nello Yemen: le voci
                // hanno anni diversi). Al primo tick la fase 03 ricalcolava, il
                // consumo «saltava», e la fase 04 leggeva il salto come
                // variazione di UNA settimana moltiplicata per 52: la
                // legittimita' di Cuba passava da 51,7 a zero, quella dello
                // Yemen da 18 a 100, e 107 paesi su 189 si muovevano di piu' di
                // cinque punti. Ogni mondo nuovo nasceva con uno shock.
                pilProCapite:        $pil * 1_000_000.0 / max(1.0, $popolazione),
                consumoProCapite:    $pil * 1_000_000.0 / max(1.0, $popolazione) * $quotaCons,
                quotaConsumi:        $quotaCons,
                quotaInvestimenti:   $quotaInv,
                quotaMilitare:       $quotaMil,
                // La legittimità non parte uguale per tutti: uno stato solido
                // che cresce ha credito, uno stato fragile che arranca no.
                legittimita:  max(18.0, min(78.0,
                    28.0 + 30.0 * ((int) $d['maturita'] / 255.0)
                    + 400.0 * $recente
                    - 8.0 * max(0.0, 1.0 - (float) $d['alfabetizzazione'] / 0.8)
                )),
                // L'aspettativa e' sul consumo PRO CAPITE, che e' quel che la
                // fase 04 le confronta. Partiva dalla crescita del PIL totale:
                // dove la popolazione cresce del 2-3% l'anno la gente
                // risultava delusa di altrettanto per i primi cinque anni, e
                // la legittimita' scendeva per un errore di unita' (docs/30).
                aspettativa:  max(0.005, min(0.06, $recente - (float) $d['crescita_pop'])),
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
            $n->potenzaIniziale = $n->potenzaGoverno();
            // La polizia e il racconto partono dalla norma del regime. Erano
            // 2 e 50 per tutti: la Corea del Nord e la Norvegia uguali (docs/31).
            if (isset($diritti[$n->iso3])) {
                $pi = (float) $diritti[$n->iso3]['integrita'];
                $fe = (float) $diritti[$n->iso3]['espressione'];
                $n->repressione = ((1.0 - $pi) + (1.0 - $fe)) / 2.0;
                $n->censura = 1.0 - $fe;
            }
            $n->statoPolizia = $n->basePolizia();
            $n->controlloInfo = 100.0 * $n->censura;
            $n->montuoso = (float) ($terreno[$n->iso3]['montuoso'] ?? 11.8);
            $n->petrolio = (bool) ($terreno[$n->iso3]['petrolio'] ?? false);
            $mondo->nazioni[$n->iso3] = $n;
            $mondo->ideologie[$n->iso3] = $d['ideologia_formale'];
        }
        fclose($fh);

        $mediana = static function (array $v): float {
            sort($v);
            return $v === [] ? 0.0 : (float) $v[intdiv(count($v), 2)];
        };
        $tutte = array_values($mondo->nazioni);
        $mondo->mediano = [
            'reddito'     => $mediana(array_map(static fn(Nazione $n): float => $n->pilProCapite, $tutte)),
            'popolazione' => $mediana(array_map(static fn(Nazione $n): float => $n->popolazione, $tutte)),
            'montuoso'    => $mediana(array_map(static fn(Nazione $n): float => $n->montuoso, $tutte)),
        ];

        $proCapite = array_map(static fn(Nazione $n): float => $n->crescitaBase - $n->crescitaPopolazione,
            array_values($mondo->nazioni));
        sort($proCapite);
        if ($proCapite !== []) {
            $mondo->crescitaProCapiteMediana = $proCapite[intdiv(count($proCapite), 2)];
        }

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
        // --- l'ultimo cambio di governo sta nel passato ------------------
        //
        // Era a zero per tutti, cioe' «il governo si e' appena insediato»,
        // in 189 paesi insieme. E la tregua che segue un cambio (0,8-2,6 anni
        // contro i colpi di Stato, due anni contro la vittoria degli insorti)
        // bloccava il mondo intero all'avvio: due cambi di governo nei primi
        // 26 tick, contro i 15-24 di ogni semestre successivo. Si colloca fra
        // uno e cinque anni prima della divergenza, diverso per ogni paese e
        // uguale in ogni corsa.
        // --- la qualita' del governo, e dove riposa la legittimita' -------
        //
        // La media di stabilita', efficacia e stato di diritto, riportata in
        // deviazioni standard dei NOSTRI paesi: cosi' il paese mediano sta a
        // zero e la sua legittimita' riposa a 50, dove il resto del modello e'
        // tarato. Chi manca prende la mediana della sua regione.
        $composito = [];
        $stabilita = [];
        foreach ($mondo->nazioni as $n) {
            $w = $wgi[$n->iso3] ?? null;
            if (is_array($w)) {
                $composito[$n->iso3] = ((float) $w['stabilita'] + (float) $w['efficacia'] + (float) $w['diritto']) / 3.0;
                $stabilita[$n->iso3] = (float) $w['stabilita'];
            }
        }
        // La stabilita' politica, centrata sul paese medio dei nostri e nelle
        // unita' della Banca Mondiale, che sono quelle dei coefficienti di
        // Fearon (2010).
        if ($stabilita !== []) {
            $mediaPv = array_sum($stabilita) / count($stabilita);
            foreach ($mondo->nazioni as $n) {
                $n->stabilitaPolitica = isset($stabilita[$n->iso3]) ? $stabilita[$n->iso3] - $mediaPv : 0.0;
            }
        }
        if ($composito !== []) {
            $media = array_sum($composito) / count($composito);
            $varianza = 0.0;
            foreach ($composito as $v) {
                $varianza += ($v - $media) ** 2;
            }
            $sd = max(1e-9, sqrt($varianza / count($composito)));
            $perRegione = [];
            foreach ($mondo->nazioni as $n) {
                if (isset($composito[$n->iso3])) {
                    $n->qualitaGoverno = ($composito[$n->iso3] - $media) / $sd;
                    $perRegione[$n->regione][] = $n->qualitaGoverno;
                }
            }
            foreach ($mondo->nazioni as $n) {
                if (!isset($composito[$n->iso3]) && isset($perRegione[$n->regione])) {
                    $r = $perRegione[$n->regione];
                    sort($r);
                    $n->qualitaGoverno = $r[intdiv(count($r), 2)];
                }
            }
        }

        foreach ($mondo->nazioni as $n) {
            $n->annoUltimoCambio = -(52 + (int) (crc32('ultimo_cambio|' . $n->iso3) % 208));
            // Lo stesso vale per la deriva politica: a zero per tutti, ogni
            // paese riposerebbe esattamente a 50 finche' il primo cambio di
            // governo non la riscrive. Si estrae dalla sua distribuzione a
            // regime (uniforme, deviazione tipica 2 punti come
            // societa.ampiezza_deriva), diversa per paese e uguale in ogni corsa.
            $u = (crc32('deriva|' . $n->iso3) % 100000) / 100000.0;
            $n->derivaPolitica = (2.0 * $u - 1.0) * sqrt(3.0) * 2.0;
            // E la legittimita' parte dove riposa. Prima partiva da una
            // formula sua — maturita', crescita, alfabetizzazione — con media
            // 60, mentre il modello la riportava a 50: il mondo passava i
            // primi anni a scendere di tre punti l'anno per un'incoerenza del
            // seme, non per qualcosa che accadeva (docs/30).
            $n->legittimita = max(5.0, min(95.0, $n->ancoraLegittimita() + $n->derivaPolitica));
        }

        // --- le insurrezioni in corso al momento della divergenza -------
        //
        // Le insurrezioni nascevano da ZERO per tutti, e siccome il
        // reclutamento e' rapido si formavano tutte insieme: il mondo apriva
        // con CINQUANTANOVE paesi in conflitto al primo anno — piu' che a
        // regime — e ci metteva otto anni a scendere ai trentasei di
        // equilibrio. Chi guardava si vedeva quasi un decennio di mondo
        // sbagliato prima che diventasse giusto.
        //
        // Un mondo che comincia oggi deve cominciare coi conflitti di oggi.
        // L'elenco viene da UCDP (db/seed/conflitti-noti.php); qui si traduce
        // il livello nel RAPPORTO DI FORZE che lo produce, che e' la scala su
        // cui la fase 05 ragiona:
        //
        //   livello 4  guerriglia     rapporto ~10  (fra le soglie 3 e 32)
        //   livello 5  grave          rapporto ~1,5 (fra 1 e 2)
        //   livello 6  guerra civile  rapporto ~0,7 (sotto 1)
        //
        // I rapporti scelti valgono per ENTRAMBI i profili di taratura, che
        // hanno soglie diverse: e' il motivo per cui non si usa il valore di
        // mezzo di ciascuna fascia ma uno che ci sta in tutte e due.
        foreach ($conflitti as $iso => $livello) {
            $n = $mondo->nazioni[(string) $iso] ?? null;
            if ($n === null) {
                continue;
            }
            $rapporto = match ((int) $livello) {
                6       => 0.7,
                5       => 1.5,
                default => 10.0,
            };
            $n->forzaInsorti = max(1.5, $n->potenzaGoverno() / $rapporto);
            $n->netPeace = (int) $livello;
        }

        // --- le guerre fra Stati in corso al momento della divergenza -----
        // (db/seed/guerre-note.php). L'inizio vero, anche se precede il tick
        // zero: la durata decide mobilitazione, stanchezza e armistizio.
        $guerreNote = @include dirname($percorsoCsv) . '/guerre-note.php';
        foreach (is_array($guerreNote) ? $guerreNote : [] as $gn) {
            $a = $mondo->nazioni[(string) $gn['aggressore']] ?? null;
            $d = $mondo->nazioni[(string) $gn['difensore']] ?? null;
            if ($a === null || $d === null) {
                continue;
            }
            $giorni = (int) (new \DateTimeImmutable(\App\Nucleo\Calendario::ORIGINE))
                ->diff(new \DateTimeImmutable((string) $gn['inizio']))->format('%r%a');
            $mondo->guerre[] = [
                'aggressore' => $a->iso3, 'difensore' => $d->iso3,
                'inizio' => (int) floor($giorni / \App\Nucleo\Calendario::GIORNI_PER_TICK),
                'morti' => 0.0, 'aiuti_difensore' => 0.0, 'aiuti_aggressore' => 0.0,
            ];
            $a->netPeace = 6;
            $d->netPeace = 6;
            $d->ansiaMilitare = 100.0;
        }

        foreach ($mondo->nazioni as $n) {
            $n->conflittoIniziale = $n->netPeace;
        }

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

        // E ogni coppia legata da un trattato vero, anche se non e' fra
        // potenze ne' fra vicini. La matrice e' volutamente rada — Crawford
        // dovette buttare via la multipolarita' perche' quella piena non gli
        // stava in memoria — ma un'alleanza E' un rapporto degno di essere
        // simulato: senza questo, delle 2.907 coppie di difesa del Correlates
        // of War ne atterravano 567 e le altre sparivano in silenzio.
        $alleanzeNote = $this->cartellaSeme !== ''
            ? @include $this->cartellaSeme . '/alleanze.php'
            : false;
        if (is_array($alleanzeNote)) {
            foreach ($alleanzeNote as $chiave => $gradino) {
                $pezzi = explode('|', (string) $chiave);
                // Solo le promesse di difesa (e le basi) fanno nascere una
                // relazione: ATOP porta anche migliaia di patti di non
                // aggressione e di consultazione fra paesi che non hanno altro
                // da spartire, e la matrice raddoppierebbe (docs/32). Dove la
                // relazione c'e' gia', il loro gradino si applica lo stesso.
                if (count($pezzi) !== 2 || (int) $gradino < 64) {
                    continue;
                }
                $a = $this->nazioni[$pezzi[0]] ?? null;
                $b = $this->nazioni[$pezzi[1]] ?? null;
                if ($a !== null && $b !== null) { $aggiungi($a, $b); }
            }
        }

        // E ogni rivalita': un'inimicizia con una storia di dispute e' un
        // rapporto anche fra paesi che non confinano e non contano nulla.
        foreach (array_keys($this->rivalita) as $chiave) {
            [$x, $z] = explode('|', (string) $chiave);
            if (isset($this->nazioni[$x], $this->nazioni[$z])) {
                $aggiungi($this->nazioni[$x], $this->nazioni[$z]);
                $aggiungi($this->nazioni[$z], $this->nazioni[$x]);
            }
        }

        // L'affinita' strutturale si MISURA: voti all'ONU, patti di difesa,
        // dispute militarizzate, coi pesi stimati da bin/importa_onu.php sui
        // rapporti dichiarati (docs/32). Prima era la formula ideologica qui
        // sotto per tutti, e fuori dai 137 rapporti scritti a mano quasi ogni
        // coppia del mondo valeva 25. Resta per chi non vota all'ONU (Taiwan,
        // il Kosovo).
        $onu = $this->cartellaSeme !== '' ? @include $this->cartellaSeme . '/onu.php' : false;
        $punti = is_array($onu) ? (array) ($onu['punti'] ?? []) : [];
        $pesi  = is_array($onu) ? (array) ($onu['pesi'] ?? []) : [];
        $patti = $this->cartellaSeme !== '' ? @include $this->cartellaSeme . '/patti-difesa.php' : false;
        $patti = is_array($patti) ? $patti : [];

        foreach ($coppie as [$a, $b]) {
            $confinanti = $this->relazioni->confinanti($a->iso3, $b->iso3);

            if ($pesi !== [] && isset($punti[$a->iso3], $punti[$b->iso3])) {
                $patto = $patti[$a->iso3 . '|' . $b->iso3] ?? $patti[$b->iso3 . '|' . $a->iso3] ?? '';
                $rivali = strcmp($a->iso3, $b->iso3) < 0 ? $a->iso3 . '|' . $b->iso3 : $b->iso3 . '|' . $a->iso3;
                $affinita = (float) $pesi['costante']
                    + (float) $pesi['distanza'] * abs((float) $punti[$a->iso3] - (float) $punti[$b->iso3])
                    + ($patto === 'bilaterale' ? (float) $pesi['bilaterale'] : 0.0)
                    + ($patto === 'integrato' ? (float) ($pesi['integrato'] ?? 0.0) : 0.0)
                    + (str_starts_with($patto, 'multilaterale:')
                        ? (float) $pesi['multilaterale'] / sqrt(max(1, (int) explode(':', $patto)[1] - 1)) : 0.0)
                    + (float) $pesi['dispute'] * min(10, $this->rivalita[$rivali] ?? 0);
                $affinita = max(-127.0, min(127.0, $affinita));
                $r = new Relazione(affinita: $affinita, confinanti: $confinanti, ancora: $affinita);
                $r->aggiornaUmore();
                $this->relazioni->imposta($a->iso3, $b->iso3, $r);
                continue;
            }

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

        // --- gli obblighi di trattato -------------------------------------
        //
        // Vengono dal Correlates of War, Formal Alliances v4.1, attraverso
        // db/seed/alleanze.php. Prima erano [FABBRICATO]: si deducevano
        // dall'affinita', cioe' chi si piaceva abbastanza risultava alleato.
        // E' un modo per avere dei trattati, non per avere QUELLI VERI —
        // e l'integrita', che e' il meccanismo con cui Crawford rende costose
        // le promesse, mordeva su garanzie che nessuno aveva mai firmato.
        //
        // La differenza si vede dove conta: gli Stati Uniti e Israele NON
        // hanno un patto di difesa reciproca, e la vecchia formula glielo
        // dava; la Cina e la Corea del Nord ce l'hanno dal 1961, e la vecchia
        // formula non glielo dava.
        $alleanze = $this->cartellaSeme !== ''
            ? @include $this->cartellaSeme . '/alleanze.php'
            : false;
        if (!is_array($alleanze)) {
            $alleanze = [];
        }

        foreach ($this->relazioni->tutte() as $chiave => $r) {
            $r->obbligo = (int) ($alleanze[$chiave] ?? 0);
            $r->obbligoFirmato = $r->obbligo;
        }

        // Il gradino piu' alto — la garanzia nucleare — non sta nel dataset,
        // perche' COW classifica i patti per quel che promettono e non per chi
        // li firma. Si deriva qui: un patto di difesa il cui GARANTE ha
        // l'atomica non e' un patto di difesa qualunque, ed e' il motivo per
        // cui l'articolo 5 pesa piu' di qualunque altra firma al mondo.
        //
        // La relazione «A|B» e' l'obbligo di A verso B: quindi conta l'arsenale
        // di A. Gli Stati Uniti garantiscono la Germania a 128, la Germania
        // garantisce gli Stati Uniti a 96.
        foreach ($this->relazioni->tutte() as $chiave => $r) {
            if ($r->obbligo < 96) {
                continue;
            }
            [$garante] = explode('|', $chiave);
            $g = $this->nazioni[$garante] ?? null;
            if ($g !== null && $g->posturaNucleare >= 4) {
                $r->obbligo = 128;
                $r->obbligoFirmato = 128;
            }
        }
    }

    /** @return list<Nazione> in ordine deterministico: mai affidarsi all'ordine implicito. */
    /** La cartella da cui viene il seme: ci stanno accanto gli altri dati. */
    public string $cartellaSeme = '';

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
