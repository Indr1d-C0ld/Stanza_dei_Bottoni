<?php

declare(strict_types=1);

namespace App\Dati;

/**
 * Il commercio, e la dipendenza che ne nasce.
 *
 * Fino a ieri questo gioco aveva un embargo che funzionava sempre allo stesso
 * modo: toglieva punti di crescita al bersaglio in proporzione al peso di chi
 * lo imponeva, e a chi lo imponeva non costava niente. Sapeva *quanto* colpire
 * ma non *che cosa*, e non sapeva affatto che chi chiude un rubinetto smette
 * anche di essere pagato per l'acqua.
 *
 * Qui il commercio diventa una struttura. Ogni paese produce e consuma in
 * cinque settori; chi ha un avanzo vende, chi ha un disavanzo compra, e chi
 * compra sceglie i fornitori per gravita' — il peso dell'altro diviso
 * l'attrito che li separa. Da quella rete escono le due cose che mancavano:
 *
 * **La dipendenza.** Quanta parte del fabbisogno di un settore arriva da un
 * fornitore solo.
 *
 * **La sostituibilita'.** Quanto in fretta si trova un altro fornitore. E' lei
 * a decidere se una sanzione morde o e' teatro: il gas di un vicino unico non
 * si rimpiazza in un trimestre, i telefoni si'.
 *
 * Tutto quel che c'e' qui e' [FABBRICATO]: nessuna di queste cifre viene da
 * dati commerciali veri. Vengono da un modello di gravita' applicato ai pochi
 * dati veri che il seme porta — popolazione, area, PIL, ricchezza, istruzione,
 * valore strategico — e la loro pretesa e' di essere verosimili nella forma e
 * nei rapporti, non esatte nei valori.
 */
final class Commercio
{
    public const SETTORI = ['energia', 'cibo', 'tecnologia', 'finanza', 'manifattura'];

    /**
     * Quanto un settore fa male se manca. L'energia e il cibo si sentono
     * subito e in tutto il paese; la finanza si sente ma si aggira; la
     * manifattura si compra altrove.
     */
    public const PESO_SETTORE = [
        'energia'     => 1.00,
        'cibo'        => 0.95,
        'tecnologia'  => 0.55,
        'finanza'     => 0.45,
        'manifattura' => 0.35,
    ];

    /**
     * LIMITE NOTO: la taglia assoluta decide troppo.
     *
     * Un paese piccolo non entra nelle classifiche dei fornitori dei suoi
     * vicini grandi, per quanto sia aperto e specializzato. Il Belgio, che nel
     * mondo vero esporta piu' dell'ottanta per cento del proprio prodotto,
     * qui dentro esporta zero: e' tredicesimo fra i fornitori di tecnologia
     * della Francia, e il taglio e' a dieci.
     *
     * Ho provato ad allargare i tagli e non serve: il Belgio resta fuori per
     * un posto, e intanto l'energia si spalma e la leva del gas si annacqua —
     * i Paesi Bassi sono passati dal 23 al 10 per cento di export sul PIL.
     * Il modello non ha un concetto di specializzazione ne' di riesportazione,
     * e non si puo' fabbricarlo allargando una classifica. Resta cosi', e si
     * dice.
     */

    /**
     * Quanta parte di un fabbisogno puo' arrivare da un fornitore solo.
     *
     * Senza un tetto la gravita' premia la taglia senza limiti e un paese
     * finisce per coprire il sessanta per cento della manifattura di mezzo
     * mondo. Succede, ma e' l'eccezione clamorosa, non la regola.
     */
    private const TETTO_QUOTA = 0.55;

    /**
     * Quanto si concentra un settore.
     *
     * I gasdotti non sono container. L'energia arriva da pochi posti e cambiare
     * fornitore vuol dire costruire infrastrutture; la manifattura si compra in
     * quindici paesi e si sposta un ordine con una telefonata. Prima coppia:
     * quanto conta la taglia del fornitore. Seconda: da quanti si compra.
     */
    private const CONCENTRAZIONE = [
        'energia'     => [1.00,  6],
        'cibo'        => [0.80, 10],
        'tecnologia'  => [0.80, 10],
        'finanza'     => [0.90,  8],
        'manifattura' => [0.65, 15],
    ];

    /** Quanto e' facile, in generale, rimpiazzare un fornitore di un settore. */
    private const FACILITA_SETTORE = [
        'energia'     => 0.45,
        'cibo'        => 0.70,
        'tecnologia'  => 0.55,
        'finanza'     => 0.85,
        'manifattura' => 0.95,
    ];

    /** @var array<string,array<string,float>> produzione[iso][settore] */
    public array $produzione = [];

    /** @var array<string,array<string,float>> fabbisogno[iso][settore] */
    public array $fabbisogno = [];

    /** @var array<string,array<string,array<string,float>>> flusso[fornitore][cliente][settore] */
    public array $flusso = [];

    /** @var array<string,array<string,array<string,int>>> sostituibilita[fornitore][cliente][settore] 0-100 */
    public array $sostituibilita = [];

    /** @var array<string,float> quanto vale l'export totale di ciascuno */
    public array $exportTotale = [];

    /** @var array<string,float> il PIL, per rapportarci l'export perduto */
    public array $pil = [];

    /**
     * Quanta parte dei consumi si compra fuori comunque.
     *
     * Un paese piccolo compra fuori quasi tutto, anche quel che saprebbe fare:
     * non ha senso che il Belgio produca da solo ogni categoria di merce. Un
     * continente economico come gli Stati Uniti o la Cina molto meno.
     *
     * @var array<string,float>
     */
    public array $apertura = [];

    /** La scala del danno, e quanto ne tocca a chi impone. Dalla calibrazione. */
    public float $morso = 0.42;
    public float $quotaFornitore = 0.45;

    // ---------------------------------------------------------------- costruzione

    /** @param array<string,array<string,float>> $vocazioni */
    public static function iniziale(Mondo $mondo, array $vocazioni = [],
        float $morso = 0.42, float $quotaFornitore = 0.45): self
    {
        $c = new self();
        $c->morso = $morso;
        $c->quotaFornitore = $quotaFornitore;
        $c->profili($mondo, $vocazioni);
        $c->intreccia($mondo);
        return $c;
    }

    /**
     * Chi produce cosa.
     *
     * Non serve un dato per settore: servono le inclinazioni giuste. Chi ha
     * molta terra e poca gente vende cibo ed energia; chi e' ricco e istruito
     * vende tecnologia e servizi finanziari; chi sta in mezzo fabbrica. Il
     * consumo invece segue la ricchezza: piu' si e' ricchi piu' si consuma
     * tecnologia e meno pesa il cibo sul totale.
     */
    /** @param array<string,array<string,float>> $vocazioni */
    private function profili(Mondo $mondo, array $vocazioni): void
    {
        foreach ($mondo->nazioni as $n) {
            $peso  = $n->pil;
            $ricch = min(1.0, $n->pilProCapite / 45000.0);
            // L'alfabetizzazione arriva dal seme gia' fra 0 e 1: dividerla per
            // cento la azzerava, e con lei tecnologia e manifattura.
            $istr  = max(0.0, min(1.0, $n->alfabetizzazione));
            $strat = $n->valoreStrategico / 100.0;

            // Terra per persona, schiacciata su 0-1: sopra un ettaro a testa
            // il vantaggio smette di crescere.
            $terra = min(1.0, ($n->areaKm2 * 100.0) / max(1.0, $n->popolazione));

            // La parte che il modello sa ricavare: la forma generale. I paesi
            // vuoti vendono cibo, i ricchi istruiti vendono servizi, quelli di
            // mezzo fabbricano — i poveri non ne hanno i mezzi, i ricchissimi
            // hanno smesso.
            $base = [
                'energia'     => $peso * (0.020 + 0.055 * $strat) * (0.6 + 0.9 * $terra),
                'cibo'        => $peso * (0.020 + 0.120 * $terra ** 0.8),
                'tecnologia'  => $peso * (0.015 + 0.180 * ($ricch * $istr) ** 1.3),
                // Era ($ricch * $matur) ** 1.2, ma `maturita` e' il reddito
                // travestito (r = 0,989 col suo logaritmo): il prodotto valeva
                // gia' ricch al quadrato, e adesso lo dice. Stesso numero,
                // una variabile inventata in meno.
                'finanza'     => $peso * (0.012 + 0.140 * $ricch ** 2.4),
                'manifattura' => $peso * (0.030 + 0.150 * (1.0 - abs($ricch - 0.45) / 0.55) * $istr),
            ];

            // E la parte che non puo' ricavare, perche' il seme non contiene
            // quel che sta sottoterra: sta dichiarata in commercio-noto.php.
            foreach (($vocazioni[$n->iso3] ?? []) as $settore => $moltiplicatore) {
                if (isset($base[$settore])) {
                    $base[$settore] *= (float) $moltiplicatore;
                }
            }
            $this->produzione[$n->iso3] = $base;

            $this->pil[$n->iso3] = max(1.0, $peso);

            // Quanto piccolo sei rispetto al mondo, tanto piu' compri fuori.
            $this->apertura[$n->iso3] = 0.46 - 0.26 * min(1.0, ($n->influenzaTotale / 14.0) ** 0.6);

            $this->fabbisogno[$n->iso3] = [
                'energia'     => $peso * (0.075 + 0.055 * $ricch),
                'cibo'        => $peso * (0.115 - 0.055 * $ricch),
                'tecnologia'  => $peso * (0.035 + 0.110 * $ricch),
                'finanza'     => $peso * (0.030 + 0.080 * $ricch),
                'manifattura' => $peso * 0.120,
            ];
        }
    }

    /**
     * Chi compra da chi.
     *
     * Modello di gravita': il fornitore piu' grosso e piu' vicino vince. Alla
     * distanza — che qui non esiste, perche' il seme non porta coordinate — si
     * sostituisce l'attrito: confinanti, stessa regione, oppure lontani. E
     * l'affinita' lo allenta, perche' si commercia piu' volentieri con gli
     * amici, o almeno con chi non ci minaccia.
     */
    private function intreccia(Mondo $mondo): void
    {
        $elenco = array_keys($mondo->nazioni);

        foreach (self::SETTORI as $settore) {
            [$esponente, $quanti] = self::CONCENTRAZIONE[$settore];
            // Chi ha da vendere in questo settore, dal piu' grosso.
            //
            // Non solo chi ha un avanzo netto: anche chi ne produce e ne
            // consuma in pari misura ne vende una parte all'estero, tanto piu'
            // quanto e' piccolo e aperto. Senza questo il Belgio — una delle
            // economie piu' aperte del mondo — non risultava fornitore di
            // niente e non esportava nulla, che e' l'errore speculare a quello
            // per cui chi produce un settore non ne importava mai.
            $offerta = [];
            foreach ($elenco as $iso) {
                $prod = $this->produzione[$iso][$settore];
                $serv = max(1.0, $this->fabbisogno[$iso][$settore]);
                // Si vende all'estero quel che si sa fare, non quel che si
                // consuma: chi copre a stento un terzo del proprio fabbisogno
                // di energia non e' un esportatore di energia, per quanto
                // grande sia. Senza questa stretta la Francia finiva per
                // vendere petrolio alla Germania.
                $competenza = min(1.0, $prod / $serv) ** 1.5;
                $vende = $prod * ($this->apertura[$iso] ?? 0.30) * $competenza
                    + max(0.0, $prod - $this->fabbisogno[$iso][$settore]);
                if ($vende > 0.0) {
                    $offerta[$iso] = $vende;
                }
            }
            arsort($offerta);
            // Oltre i primi sessanta il resto e' polvere, e il costo si triplica.
            $offerta = array_slice($offerta, 0, 60, true);
            if ($offerta === []) {
                continue;
            }

            foreach ($elenco as $cliente) {
                // Quanto si compra fuori. Non e' solo il buco fra quel che
                // serve e quel che si produce: un'economia aperta compra
                // all'estero anche quel che sa fare: Francia e Germania si
                // vendono automobili a vicenda. Senza questo, fra due paesi che
                // producono entrambi un settore non esisteva nessun flusso, e
                // un embargo fra loro costava esattamente zero a tutti e due.
                $apertura = $this->apertura[$cliente] ?? 0.30;
                $manca = max(
                    $this->fabbisogno[$cliente][$settore] * $apertura,
                    $this->fabbisogno[$cliente][$settore] - $this->produzione[$cliente][$settore],
                );
                if ($manca <= 0.0) {
                    continue;
                }

                // Il peso di ogni fornitore possibile.
                $pesi = [];
                foreach ($offerta as $fornitore => $avanzo) {
                    if ($fornitore === $cliente) {
                        continue;
                    }
                    // L'avanzo conta meno che proporzionalmente: essere il
                    // doppio piu' grande non vuol dire vendere il doppio, o un
                    // solo paese finirebbe per fornire mezzo mondo.
                    $pesi[$fornitore] = $avanzo ** $esponente
                        / $this->attrito($mondo, $cliente, $fornitore);
                }
                if ($pesi === []) {
                    continue;
                }
                arsort($pesi);
                // Un paese non compra da centottanta posti: da pochi, e quanto
                // pochi dipende da che cosa compra.
                $pesi = array_slice($pesi, 0, $quanti, true);
                $somma = array_sum($pesi);
                if ($somma <= 0.0) {
                    continue;
                }

                // Nessuno mette tutte le uova in un paniere solo. Il tetto si
                // applica e quel che avanza si ridistribuisce, due volte:
                // basta a togliere i casi assurdi senza appiattire tutto.
                $quote = [];
                foreach ($pesi as $fornitore => $p) {
                    $quote[$fornitore] = $p / $somma;
                }
                for ($giro = 0; $giro < 2; $giro++) {
                    $eccesso = 0.0;
                    $sotto   = 0.0;
                    foreach ($quote as $fornitore => $q) {
                        if ($q > self::TETTO_QUOTA) {
                            $eccesso += $q - self::TETTO_QUOTA;
                            $quote[$fornitore] = self::TETTO_QUOTA;
                        } else {
                            $sotto += $q;
                        }
                    }
                    if ($eccesso <= 0.0 || $sotto <= 0.0) {
                        break;
                    }
                    foreach ($quote as $fornitore => $q) {
                        if ($q < self::TETTO_QUOTA) {
                            $quote[$fornitore] = $q + $eccesso * ($q / $sotto);
                        }
                    }
                }

                foreach ($quote as $fornitore => $frazione) {
                    $quantita = $manca * $frazione;
                    if ($quantita < $manca * 0.02) {
                        continue;   // sotto il due per cento non e' una dipendenza
                    }
                    $this->flusso[$fornitore][$cliente][$settore] = $quantita;
                    $this->exportTotale[$fornitore] = ($this->exportTotale[$fornitore] ?? 0.0) + $quantita;

                    // Quota del fabbisogno che passa da questo fornitore: e' la
                    // dipendenza. La sostituibilita' e' il suo contrario,
                    // temperato da quanto quel settore si rimpiazza in fretta.
                    $quota = $quantita / max(1.0, $this->fabbisogno[$cliente][$settore]);
                    $this->sostituibilita[$fornitore][$cliente][$settore] = (int) round(
                        100.0 * (1.0 - min(1.0, $quota)) * self::FACILITA_SETTORE[$settore]);
                }
            }
        }
    }

    /** Quanto costa far viaggiare la merce fra due paesi. */
    private function attrito(Mondo $mondo, string $a, string $b): float
    {
        $r = $mondo->relazioni->fra($a, $b);
        // Il commercio vero e' molto piu' regionale di quanto la sola taglia
        // farebbe pensare: i primi partner del Belgio sono i suoi vicini, non
        // i giganti. Con un attrito troppo debole i giganti vincevano ovunque e
        // le economie piccole non risultavano fornitrici di nessuno.
        $base = match (true) {
            $r !== null && $r->confinanti                      => 0.70,
            ($mondo->nazioni[$a]->regione ?? '')
                === ($mondo->nazioni[$b]->regione ?? '·')      => 1.10,
            default                                            => 3.20,
        };
        // Si commercia piu' volentieri con chi non ci minaccia. Non e' un
        // divieto: e' una frizione, e infatti i nemici continuano a commerciare.
        $aff = $r?->affinita ?? 0.0;
        return $base * (1.0 - 0.22 * ($aff / 127.0));
    }

    // ------------------------------------------------------------------ lettura

    /**
     * Il saldo commerciale di un paese, in quota del suo PIL.
     *
     * Positivo: vende all'estero piu' di quel che compra.
     *
     * ATTENZIONE: questa non e' una bilancia dei pagamenti e non va usata per
     * muovere la crescita. Le importazioni qui dentro sono costruite come
     * fabbisogno non coperto piu' una quota di apertura, quindi il saldo dice
     * piu' cose sul modello che sul paese. Provato a collegarlo alla crescita
     * con peso_commercio = 0,25: i colpi di Stato irregolari sono passati da
     * 11,9 a 18,1 l'anno e la legittimita' media e' crollata di otto punti,
     * perche' i paesi poveri risultavano tutti in disavanzo cronico. Serve un
     * modello di partite correnti vero, e non c'e'. Qui resta solo per essere
     * mostrata: e' una descrizione, non una forza.
     */
    public function saldo(string $iso): float
    {
        $esporta = $this->exportTotale[$iso] ?? 0.0;
        $importa = 0.0;
        foreach ($this->flusso as $fornitore => $clienti) {
            $importa += array_sum($clienti[$iso] ?? []);
        }
        $pil = $this->pil[$iso] ?? 0.0;
        return $pil <= 0.0 ? 0.0 : ($esporta - $importa) / $pil;
    }

    /** Quanto il cliente dipende dal fornitore, in un settore. 0-1. */
    public function dipendenza(string $fornitore, string $cliente, string $settore): float
    {
        $q = $this->flusso[$fornitore][$cliente][$settore] ?? 0.0;
        $b = $this->fabbisogno[$cliente][$settore] ?? 0.0;
        return $b <= 0.0 ? 0.0 : min(1.0, $q / $b);
    }

    /**
     * Che male fa, a chi lo subisce, che un fornitore chiuda i rubinetti.
     *
     * Espresso in punti di crescita annua. E' la somma su tutti i settori
     * della quota che veniva da li', per quanto quel settore pesa, per quanto
     * e' difficile rimpiazzarlo.
     */
    public function dannoAlCliente(string $fornitore, string $cliente, float $quotaTagliata = 1.0): float
    {
        $danno = 0.0;
        foreach (($this->flusso[$fornitore][$cliente] ?? []) as $settore => $quantita) {
            $quota = $this->dipendenza($fornitore, $cliente, $settore);
            $sost  = ($this->sostituibilita[$fornitore][$cliente][$settore] ?? 100) / 100.0;
            $danno += $quota * self::PESO_SETTORE[$settore] * (1.0 - $sost);
        }
        return $danno * $quotaTagliata * $this->morso;
    }

    /**
     * E che male fa a chi lo impone.
     *
     * Chi chiude un rubinetto smette di essere pagato per l'acqua. Fa meno
     * male che restare senza — un mercato si ritrova piu' facilmente di un
     * fornitore — ma non e' zero, ed e' questo che rende l'embargo una
     * decisione invece di un pulsante.
     */
    public function dannoAlFornitore(string $fornitore, string $cliente, float $quotaTagliata = 1.0): float
    {
        // Si misura sul PIL, non sull'export: perdere un quinto delle proprie
        // vendite estere e' una cosa diversa per chi vive di export e per chi
        // esporta poco. Rapportarlo al totale dell'export avrebbe dato lo
        // stesso colpo a tutti e due, che e' falso.
        $perso = array_sum($this->flusso[$fornitore][$cliente] ?? []);
        $pil   = $this->pil[$fornitore] ?? 0.0;
        if ($pil <= 0.0) {
            return 0.0;
        }
        return ($perso / $pil) * $quotaTagliata * $this->morso * $this->quotaFornitore;
    }

    /**
     * I fornitori di un paese, dal piu' importante.
     *
     * @return list<array{fornitore:string,settore:string,quantita:float,quota:float,sostituibilita:int}>
     */
    public function fornitoriDi(string $cliente, int $quanti = 25): array
    {
        $righe = [];
        foreach ($this->flusso as $fornitore => $clienti) {
            foreach (($clienti[$cliente] ?? []) as $settore => $quantita) {
                $righe[] = [
                    'fornitore'      => $fornitore,
                    'settore'        => $settore,
                    'quantita'       => $quantita,
                    'quota'          => $this->dipendenza($fornitore, $cliente, $settore),
                    'sostituibilita' => $this->sostituibilita[$fornitore][$cliente][$settore] ?? 100,
                ];
            }
        }
        usort($righe, static fn($a, $b) => $b['quota'] <=> $a['quota']);
        return array_slice($righe, 0, $quanti);
    }

    /**
     * I clienti di un paese, dal piu' importante.
     *
     * @return list<array{cliente:string,settore:string,quantita:float,quota:float}>
     */
    public function clientiDi(string $fornitore, int $quanti = 25): array
    {
        $tot = max(1.0, $this->exportTotale[$fornitore] ?? 0.0);
        $righe = [];
        foreach (($this->flusso[$fornitore] ?? []) as $cliente => $settori) {
            foreach ($settori as $settore => $quantita) {
                $righe[] = [
                    'cliente'  => $cliente,
                    'settore'  => $settore,
                    'quantita' => $quantita,
                    'quota'    => $quantita / $tot,
                ];
            }
        }
        usort($righe, static fn($a, $b) => $b['quota'] <=> $a['quota']);
        return array_slice($righe, 0, $quanti);
    }
}
