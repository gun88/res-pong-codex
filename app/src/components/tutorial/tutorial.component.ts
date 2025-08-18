import { Component, OnDestroy, OnInit, inject } from '@angular/core';
import { NgIf, NgStyle } from '@angular/common';
import { TutorialService, TutorialState } from '../../service/tutorial.service';
import {Popover} from 'primeng/popover';

@Component({
    selector: 'res-pong-user-tutorial',
    standalone: true,
  imports: [NgIf, NgStyle, Popover],
    templateUrl: './tutorial.component.html'
})
export class TutorialComponent implements OnInit, OnDestroy {
    private tutorial = inject(TutorialService);
    state: TutorialState | null = null;
    popupStyle: any = {};
    private sub: any;

    ngOnInit(): void {
        this.sub = this.tutorial.tutorial$.subscribe(state => {
            this.removeHighlight();
            this.state = state;
            if (state) {
                const el = document.querySelector(state.step.selector) as HTMLElement | null;
                if (el) {
                    el.classList.add('tutorial-highlight');
                    const rect = el.getBoundingClientRect();
                    this.popupStyle = {
                        top: rect.bottom + 8 + 'px',
                        left: rect.left + 'px'
                    };
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    this.popupStyle = { top: '20px', left: '20px' };
                }
            }
        });
    }

    ngOnDestroy(): void {
        this.sub?.unsubscribe();
        this.removeHighlight();
    }

    next(): void {
        this.tutorial.next();
    }

    end(): void {
        this.tutorial.end();
    }

    private removeHighlight(): void {
        if (this.state) {
            const el = document.querySelector(this.state.step.selector) as HTMLElement | null;
            el?.classList.remove('tutorial-highlight');
        }
    }
}

