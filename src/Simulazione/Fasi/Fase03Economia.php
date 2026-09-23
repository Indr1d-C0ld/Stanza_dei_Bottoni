<?php

declare(strict_types=1);

namespace App\Simulazione\Fasi;

use App\Simulazione\ContestoTick;
use App\Simulazione\EsitoFase;
use App\Simulazione\Fase;

/**
 * Fase 03 — Economia e commercio.
 *
 * LEGGE:      lo stato economico del tick precedente
 * SCRIVE:     quote di bilancio, PIL, popolazione, redditi, equipaggiamento
 * INVARIANTE: quota_consumi + quota_investimenti + quota_militare = 1; la
 *             variazione del PIL resta entro i tetti di calibrazione.
 *
 * Modello a tre pressioni di Balance of Power. Il punto non ovvio, e il motivo
 * per cui funziona, è che le quote non sono decise da un governo: sono l'esito
 * di pressioni che agiscono sulla società. Che a risolverle sia un ministro, il
 * mercato o una banca non riguarda il modello.
 */
final class Fase03Economia implements Fase
{
    public function codice(): string { return '03'; }
    public function nome(): string   { return 'Economia e commercio'; }

    public function esegui(ContestoTick $c): EsitoFase
    {
        $mondo = $c->mondo;
        if ($mondo === null) {
            return EsitoFase::nonImplementata();
        }

        $cal          = $c->calibrazione;
        $perTick      = 1.0 / $cal->numero('tempo.tick_per_anno', 52.0);

        $mondo->commercio->morso = $cal->numero('commercio.morso', 0.42);
        $mondo->commercio->quotaFornitore = $cal->numero('commercio.quota_fornitore', 0.45);

        // I rubinetti chiusi, e quanto costano. Le strozzature scadute si
        // buttano qui: non servono piu' a nessuno.
        $mondo->strozzature = array_values(array_filter(
            $mondo->strozzature,
            static fn(array $s): bool => (int) $s['fine'] > $c->tick,
        ));
        $pressione = [];
        foreach ($mondo->strozzature as $st) {
            $forn   = (string) $st['fornitore'];
            $cliente = (string) $st['cliente'];
            $quota  = (float) $st['quota'];
            // Chi resta senza fornitore paga il prezzo pieno, pesato su quanto
            // dipendeva da lui e su quanto e' difficile rimpiazzarlo.
            $pressione[$cliente] = ($pressione[$cliente] ?? 0.0)
                + $mondo->commercio->dannoAlCliente($forn, $cliente, $quota);
            // Chi ha chiuso paga di meno, ma paga: ha perso un mercato.
            $pressione[$forn] = ($pressione[$forn] ?? 0.0)
                + $mondo->commercio->dannoAlFornitore($forn, $cliente, $quota);
        }
        // Si puo' strangolare un paese, non annientarlo.
        $tettoPressione = $cal->numero('commercio.tetto_pressione', 0.085);
        foreach ($pressione as $iso => $v) {
            $pressione[$iso] = min($tettoPressione, $v);
        }
        $soglia       = $cal->numero('economia.soglia_investimento', 0.12);
        $resa         = $cal->numero('economia.resa_investimento', 0.32);
        $tettoAlto    = $cal->numero('economia.crescita_max_anno', 0.10);
        $tettoBasso   = $cal->numero('economia.crescita_min_anno', -0.15);
        $kConsumi     = $cal->numero('economia.pressione_consumi_k', 1.0);
        $kInvest      = $cal->numero('economia.pressione_investimenti_k', 0.35);
        $kMilitare    = $cal->numero('economia.pressione_militare_k', 1.0);
        $spintaInvNeutra = $cal->numero('economia.spinta_investimenti_neutra', 0.71);
        $ampiezzaInv     = $cal->numero('economia.ampiezza_investimenti', 0.16);
        $spintaMilNeutra = $cal->numero('economia.spinta_militare_neutra', 0.21);
        $ampiezzaMil     = $cal->numero('economia.ampiezza_militare', 0.09);
        $rientro         = $cal->numero('economia.rientro_strutturale', 0.60);
        $margine         = $cal->numero('economia.margine_strutturale', 0.02);
        $recuperoUomini  = $cal->numero('economia.recupero_uomini_anno', 0.35);
        $elasticitaUomini = $cal->numero('economia.elasticita_uomini', 0.5);
        $tettoRelativo    = max(1.0, $cal->numero('economia.tetto_uomini_relativo', 3.0));
        $tettoPopolazione = $cal->numero('economia.tetto_uomini_popolazione', 0.05);
        $ammortamento    = $cal->numero('economia.ammortamento_equipaggiamento', 0.25);
        // Quanto le quote possono spostarsi in un anno: le economie non
        // cambiano struttura in una settimana.
        $velocita     = 0.25 * $perTick;

        $inRecessione = 0;

        foreach ($mondo->elenco() as $n) {
            // --- le tre pressioni ---------------------------------------
            // Un governo impopolare è spinto ad aumentare i consumi.
            $pressioneConsumi = $kConsumi * max(0.0, (55.0 - $n->legittimita) / 55.0);
            $pressioneInvest  = $kInvest;
            // Una minaccia interna spinge alla spesa militare: è la radice del
            // rapporto di forze insorti/governo.
            $minacciaInterna  = sqrt(max(0.0, $n->forzaInsorti) / max(1.0, $n->potenzaGoverno()));
            // E una minaccia di fuori pure. L'ansia militare esisteva, cresceva
            // a ogni provocazione e la leggeva solo la fase 06 per la
            // diffidenza: non spostava un centesimo di bilancio. Un paese
            // circondato che non arma nessuno non e' un modello, e' una svista.
            $minacciaEsterna  = $n->ansiaMilitare / 100.0;
            $pressioneMil     = $kMilitare * min(1.5, $minacciaInterna + $minacciaEsterna);

            // I consumi sono il RESIDUO, non una voce contesa: è quel che resta
            // dopo che investimenti e difesa si sono presi la loro parte. Farne
            // una voce come le altre significa lasciare che gli investimenti
            // assorbano mezzo prodotto interno, e il mondo esplode.
            $totale = max(0.0001, $pressioneConsumi + $pressioneInvest + $pressioneMil);
            // L'obiettivo ruota intorno alla quota storica del paese, non
            // intorno a una costante uguale per tutti: al tick 0 coincide con
            // quel che il paese fa davvero, e da li' le pressioni lo spostano.
            //
            // La pressione dei consumi non aumenta la loro quota: riduce quella
            // degli investimenti. È il modo in cui un governo debole si mangia
            // il futuro per comprare il presente. Entra QUI, dentro la spinta,
            // e non come fattore applicato all'obiettivo gia' calcolato: fuori
            // renderebbe il punto neutro indefinibile, e un paese fermo si
            // ritroverebbe un bersaglio sotto la propria quota di partenza.
            $spintaInvest = ($pressioneInvest / $totale)
                          * (1.0 - 0.45 * ($pressioneConsumi / $totale));
            $spintaMil    = $pressioneMil / $totale;

            $obiettivoInvest   = max(0.08, min(0.34, $n->quotaInvestimentiIniziale
                + $ampiezzaInv * ($spintaInvest - $spintaInvNeutra)));
            $obiettivoMilitare = max(0.004, min(0.22, $n->quotaMilitareIniziale
                + $ampiezzaMil * ($spintaMil - $spintaMilNeutra)));
            $obiettivoConsumi  = max(0.45, 1.0 - $obiettivoInvest - $obiettivoMilitare);

            $n->quotaConsumi      += ($obiettivoConsumi  - $n->quotaConsumi)      * $velocita;
            $n->quotaMilitare     += ($obiettivoMilitare - $n->quotaMilitare)     * $velocita;
            $n->quotaInvestimenti += ($obiettivoInvest   - $n->quotaInvestimenti) * $velocita;

            // L'invariante si fa valere qui, non si spera che regga.
            $somma = $n->quotaConsumi + $n->quotaMilitare + $n->quotaInvestimenti;
            $n->quotaConsumi      /= $somma;
            $n->quotaMilitare     /= $somma;
            $n->quotaInvestimenti /= $somma;

            // --- crescita ------------------------------------------------
            // Sotto la soglia di investimento il capitale si consuma più in
            // fretta di quanto lo rinnovi: strade, scuole e fabbriche vanno a
            // pezzi e il PIL cala, anche senza che succeda nulla di drammatico.
            // La crescita parte dalla tendenza osservata del paese e da lì
            // devia: investire più del proprio solito accelera, investire meno
            // rallenta, e sotto la soglia il capitale si consuma da sé.
            // Le economie rallentano man mano che si avvicinano alla frontiera
            // tecnologica: si cresce in fretta finché si copia, poi si deve
            // inventare. Senza questo, chi parte al 6,5% ci resta per sempre e
            // in trentacinque anni l'India vale un quinto del mondo.
            // Gli effetti degli investimenti esteri sfumano se non rinnovati:
            // senza questo rientro, ogni evento "investimenti" alzava la
            // tendenza di crescita PER SEMPRE e il mondo aveva una pompa
            // inflazionistica senza contrappeso.
            $n->crescitaStrutturale += ($n->crescitaBase - $n->crescitaStrutturale) * $rientro * $perTick;
            // e il tetto e' relativo alla base del paese, non assoluto: il
            // limite a 0,09 lasciava salire di quattro punti chi partiva basso.
            $n->crescitaStrutturale = min($n->crescitaBase + $margine, $n->crescitaStrutturale);

            $maturazione  = min(1.0, $n->pilProCapite / 50000.0);
            $strutturale  = $n->crescitaStrutturale * (1.0 - 0.85 * $maturazione)
                          + 0.014 * $maturazione;

            $crescita = $strutturale
                + $resa * ($n->quotaInvestimenti - $n->quotaInvestimentiIniziale)
                + $resa * min(0.0, $n->quotaInvestimenti - $soglia) * 2.0;

            // Una guerra costa punti di crescita. QUANTI non e' piu' inventato:
            //
            //   COLLIER et al. (2003), «Breaking the Conflict Trap: Civil War
            //   and Development Policy», Banca Mondiale — una guerra civile
            //   costa circa 2,3 punti percentuali di crescita l'anno.
            //
            // Qui c'erano SETTE punti per la guerra civile, tre volte tanto.
            // Finche' i conflitti nascevano solo in paesi piccoli non si
            // vedeva; seminando i conflitti veri — che comprendono l'India, il
            // Pakistan, la Nigeria — la crescita mondiale e' scesa sotto il 2%
            // e la fascia l'ha detto.
            //
            // E c'e' una ragione di modello oltre alla fonte: il nostro
            // netPeace e' uno stato NAZIONALE, mentre quasi tutte le
            // insurrezioni vere sono contenute in una regione. Il naxalismo non
            // ferma l'India. Sette punti trattavano ogni guerriglia come se
            // occupasse tutto il paese.
            $crescita -= match (true) {
                $n->netPeace >= 6 => 0.023,
                $n->netPeace >= 5 => 0.014,
                $n->netPeace >= 4 => 0.006,
                $n->netPeace >= 3 => 0.002,
                default           => 0.0,
            };

            // Sanzioni ed embarghi in corso. Non e' piu' un numero che decade
            // da solo: e' la somma di quel che costa, adesso, ogni rubinetto
            // chiuso — a chi lo subisce e a chi lo ha chiuso.
            $n->pressioneEsterna = $pressione[$n->iso3] ?? 0.0;
            $crescita -= $n->pressioneEsterna;


            // Il ciclo: shock persistenti, non rumore bianco, altrimenti le
            // aspettative non si muovono mai e nessun governo cade.
            $ciclo = $c->caso->rumore('03_ciclo', crc32($n->iso3), (int) ($c->tick / 26), 0.030);
            $crescita += $ciclo + $c->caso->rumore('03_economia', crc32($n->iso3), $c->tick, 0.006);
            $crescita  = max($tettoBasso, min($tettoAlto, $crescita));

            $n->crescitaPil = $crescita;
            $n->pil        *= (1.0 + $crescita) ** $perTick;
            $n->popolazione = max(1000.0, $n->popolazione * (1.0 + $n->crescitaPopolazione) ** $perTick);

            $n->pilProCapite        = $n->pil * 1_000_000.0 / $n->popolazione;
            $n->consumoProCapitePrec = $n->consumoProCapite;
            $n->consumoProCapite     = $n->pilProCapite * $n->quotaConsumi;

            // L'equipaggiamento è uno stock che si accumula e si deprezza.
            //
            // Il ritmo di deprezzamento decide dove lo stock si ferma: con
            // un ammortamento A l'equilibrio e' «spesa annua / A». Era 0,08,
            // cioe' dodici anni e mezzo di spesa — ma il seme lo avvia a
            // QUATTRO (Mondo::daSeme), e quindi cresceva per sempre: x1,9 in
            // cinque anni, x2,9 in dieci, e con lui potenzaGoverno, e con
            // quella tutta la taratura di insurrezioni e guerre, che scivolava
            // con l'eta' del mondo. Adesso l'equilibrio e' quello da cui si
            // parte. E un quarto l'anno non e' un ritmo irragionevole: lo stock
            // si costruisce con la spesa militare INTERA, e la parte che se ne
            // va in stipendi, addestramento e manutenzione non resta in
            // magazzino.
            $n->equipaggiamento = max(
                0.5,
                $n->equipaggiamento * (1.0 - $ammortamento * $perTick) + $n->pil * $n->quotaMilitare * $perTick,
            );

            // E GLI UOMINI ANCHE. Prima no: l'attrito li toglieva — la fase 05
            // per le insurrezioni, la 07 per le guerre — e nessuna fase li
            // rimpiazzava mai. Un esercito logorato rimpiccioliva PER SEMPRE,
            // mentre l'equipaggiamento qui sopra si ricostruiva dal bilancio:
            // il modello comprava carri armati e non arruolava nessuno.
            //
            // Non e' un dettaglio contabile. La Dominica e' arrivata a ZERO
            // soldati, e con potenzaGoverno() = 0 qualunque banda armata le
            // dichiarava guerra civile: il 29% dei paesi sotto il milione di
            // abitanti risultava in conflitto, contro lo 0% di quelli sopra i
            // duecento milioni.
            //
            // Il bersaglio sono gli uomini che il paese teneva al principio,
            // riscalati su quanto spende adesso rispetto ad allora: chi alza il
            // bilancio arruola, chi lo taglia congeda.
            $rapporto = $n->quotaMilitare / max(1e-6, $n->quotaMilitareIniziale);
            $rapporto = max(1.0 / $tettoRelativo, min($tettoRelativo, $rapporto));
            $obiettivoUomini = min(
                $n->soldatiIniziali * $rapporto ** $elasticitaUomini,
                max($n->soldatiIniziali, $n->popolazione * $tettoPopolazione),
            );
            $n->soldati = max(50.0,
                $n->soldati + ($obiettivoUomini - $n->soldati) * $recuperoUomini * $perTick);

            if ($crescita < 0) {
                $inRecessione++;
            }
        }

        return new EsitoFase([
            'nazioni'    => count($mondo->nazioni),
            'recessioni' => $inRecessione,
        ]);
    }
}
