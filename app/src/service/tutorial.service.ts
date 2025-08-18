import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';

export interface TutorialStep {
    selector: string;
    text: string;
    last?: boolean;
}

export interface TutorialState {
    step: TutorialStep;
    index: number;
    total: number;
}

@Injectable({ providedIn: 'root' })
export class TutorialService {
    private tutorials = new Map<string, TutorialStep[]>();
    private steps: TutorialStep[] = [];
    private index = 0;
    private state$ = new BehaviorSubject<TutorialState | null>(null);


    private tutorialSteps: TutorialStep[] = [
         {selector: '.rp-event-title-block-container', text: "Nome dell'evento visualizzato in questa pagina. Qui è indicata anche la data dell'evento e la sua durata."},
        {selector: '.rp-event-navigator', text: "Tramite questi pulsanti puoi navigare tra gli eventi. Da dispositivo mobile, è possibile navigare anche tramite swipe verso destra e sinistra."},
        {selector: '.rp-event-actions-block', text: "Usa questi pulsanti per Prenotare un evento o Cancellare la tua prenotazione."},
        {selector: '.rp-event-status-message-block', text: "Questo è un messaggio con le informazioni sull'evento e la tua prenotazione."},
        {selector: '.rp-event-participants-block', text: "In questa sezione trovi la lista dei giocatori iscritti all'evento corrente."},

        {selector: '.rp-reservations-title-box', text: "Qui sono riportati il mese e l'anno che stai visualizzando. Al di sotto dell'etichetta, il numero di eventi disponibili per questo intervallo di tempo."},
        {selector: '#rp-reservations-navigator', text: "Tramite questi pulsanti puoi navigare tra i mesi. Premi 'Oggi' per tornare al mese corrente. Da dispositivo mobile, è possibile navigare anche tramite swipe verso destra e sinistra."},
        {selector: '#rp-reservations-view-switcher', text: "Scegli la modalità di visualizzazione. La modalità 'Mese' mostra la pagina di calendario mentre la modalità 'Lista' mostra l'elenco degli eventi."},
        {selector: '.rp-calendar-day-with-events', text: "Qui c'è un evento. Clicca qui per aprire la pagina di dettaglio, dove potrai prenotarti, cancellare la tua prenotazione o vedere le informazioni dell'evento."},
        {selector: '.rp-calendar-legend-container', text: "Qui trovi la legenda delle disponibilità. Tramite i colori delle etichette degli eventi, puoi già capire se c'è disponibilità."},

        {selector: '.rp-user-data-section', text: "In questa sezione trovi i tuoi dati."},
        {selector: '.rp-user-data-section-username', text: "Il tuo username, autogenerato dalla piattaforma. Puoi usarlo per autenticarti al posto della e-mail."},
        {selector: '.rp-user-data-section-email', text: "L'indirizzo e-mail che hai fornito alla società e con il quale è stato registrato il tuo account."},
        {selector: '.rp-user-logout', text: "Pulsante di Log Out. Clicca qui per terminare la sessione."},
        {selector: '.rp-user-password-update', text: "In questa sezione trovi il modulo per reimpostare la password. Inserisci la tua nuova password e la conferma, poi premi invia."},

        {selector: '.p-menubar.p-menubar-mobile', text: "Dal menu potrai accedere alle pagine principali del sito. La pagina Prenotazioni, da dove potrai vedere tutti gli eventi in programma, e lo Storico, da dove potrai vedere tutte le tue prenotazioni."},
        {selector: '.p-menubar:not(.p-menubar-mobile) .p-menubar-root-list', text: "Dal menu potrai accedere alle pagine principali del sito. La pagina Prenotazioni, da dove potrai vedere tutti gli eventi in programma, e lo Storico, da dove potrai vedere tutte le tue prenotazioni."},
        {selector: '.rp-menu-profile-button-container', text: "Clicca qui per aprire il menu del tuo profilo. Da qui puoi visualizzare i tuoi dati, modificare la password o fare logout."},

    ];


    register(path: string, steps: TutorialStep[]): void {
        if (steps.length > 0) {
            steps[steps.length - 1].last = true
        }
        this.tutorials.set(path, steps);
    }

    start(): void {
        this.steps = this.tutorialSteps;
        this.index = 0;
        if (this.steps.length > 0) {
            this.state$.next({ step: this.steps[0], index: 0, total: this.steps.length });
        }
    }

    next(): void {
        this.index++;
        if (this.index < this.steps.length) {
            this.state$.next({ step: this.steps[this.index], index: this.index, total: this.steps.length });
        } else {
            this.end();
        }
    }

    end(): void {
        this.steps = [];
        this.index = 0;
        this.state$.next(null);
    }

    get tutorial$(): Observable<TutorialState | null> {
        return this.state$.asObservable();
    }
}

