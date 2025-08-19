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
        {selector: '.rp-event-title-block-container', text: "event-title"},
        {selector: '.rp-event-navigator', text: "event-navigator"},
        {selector: '.rp-event-actions-block', text: "event-actions"},
        {selector: '.rp-event-status-message-block', text: "event-status"},
        {selector: '.rp-event-participants-block', text: "event-participants"},

        {selector: '.rp-reservations-title-box', text: "reservations-title"},
        {selector: '#rp-reservations-navigator', text: "reservations-navigator"},
        {selector: '#rp-reservations-view-switcher', text: "reservations-view-switcher"},
        {selector: '.p-datepicker-day-cell:not(.p-datepicker-other-month) .rp-calendar-day-with-events', text: "reservations-event"},
        {selector: '.rp-calendar-legend-container', text: "reservations-legend"},

        {selector: '.rp-user-data-section', text: "user-data-section"},
        {selector: '.rp-user-data-section-username', text: "user-data-section-username"},
        {selector: '.rp-user-data-section-email', text: "user-data-section-email"},
        {selector: '.rp-user-logout', text: "user-logout"},
        {selector: '.rp-user-password-update', text: "user-password"},

        {selector: '.rp-history-name-col', text: "history-name-col"},
        {selector: '.rp-history-date-col', text: "history-date-col"},
        {selector: '.rp-history-duration-col', text: "history-duration-col"},
        {selector: '.rp-history-reservation-col', text: "history-reservation-col"},
        {selector: '.rp-presence-col', text: "history-presence-col"},
        {selector: '.rp-action-col', text: "history-action-col"},
        {selector: '.rp-history-page .p-paginator', text: "history-paginator"},

        {selector: '.p-menubar.p-menubar-mobile', text: "menubar-menu"},
        {selector: '.p-menubar:not(.p-menubar-mobile) .p-menubar-root-list', text: "menubar-menu"},
        {selector: '.rp-menu-profile-button-container', text: "menubar-profile"},

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

