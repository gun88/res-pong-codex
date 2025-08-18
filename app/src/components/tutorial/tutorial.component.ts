import {Component, HostListener, inject, OnDestroy, OnInit, ViewChild} from '@angular/core';
import {NgIf} from '@angular/common';
import {Popover, PopoverModule} from 'primeng/popover';
import {TutorialService, TutorialState} from '../../service/tutorial.service';
import {Button} from "primeng/button";

@Component({
    selector: 'res-pong-user-tutorial',
    standalone: true,
    imports: [NgIf, PopoverModule, Button],
    templateUrl: './tutorial.component.html'
})
export class TutorialComponent implements OnInit, OnDestroy {
    private tutorial = inject(TutorialService);
    state: TutorialState | null = null;
    private sub: any;
    @ViewChild('popover') popover?: Popover;

    ngOnInit() {
        this.sub = this.tutorial.tutorial$.subscribe(state => {
            this.removeHighlight();
            this.popover?.hide();
            this.state = state;
            if (state) {
                const el = document.querySelector(state.step.selector) as HTMLElement | null;
                if (el) {
                    el.classList.add('tutorial-highlight');
                    el.scrollIntoView({behavior: 'smooth', block: 'center'});
                    setTimeout(() => this.popover?.show(null, el), 250);

                } else {
                    console.log('Tutorial selector not found:', state.step.selector);
                    this.next()
                }
            } else {
                setTimeout(() => {
                    this.popover?.hide();
                });
            }
        });
    }

    ngOnDestroy() {
        this.sub?.unsubscribe();
        this.removeHighlight();
    }

    next() {
        this.tutorial.next();
    }

    end() {
        this.tutorial.end();
        this.popover?.hide();
    }

    @HostListener('document:keydown', ['$event'])
    handleKeyboard(event: KeyboardEvent) {
        if (event.key === 'Escape') {
            this.end();
        }
        if (event.key === 'Enter') {
            event.preventDefault();
            this.next();
        }
    }

    private removeHighlight() {

        if (this.state) {
            const el = document.querySelector(this.state.step.selector) as HTMLElement | null;
            el?.classList.remove('tutorial-highlight');
        }

    }
}


