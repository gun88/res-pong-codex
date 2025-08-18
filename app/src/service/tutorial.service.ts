import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';

export interface TutorialStep {
    selector: string;
    text: string;
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

    register(path: string, steps: TutorialStep[]): void {
        this.tutorials.set(path, steps);
    }

    startForUrl(url: string): void {
        const match = Array.from(this.tutorials.keys()).find(p => url.startsWith(p));
        if (match) {
            this.start(match);
        }
    }

    private start(path: string): void {
        this.steps = this.tutorials.get(path) || [];
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

