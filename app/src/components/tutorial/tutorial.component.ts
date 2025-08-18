import {Component, inject, OnDestroy, OnInit, ViewChild} from '@angular/core';
import {NgIf} from '@angular/common';
import {Popover, PopoverModule} from 'primeng/popover';
import {TutorialService, TutorialState} from '../../service/tutorial.service';

@Component({
    selector: 'res-pong-user-tutorial',
    standalone: true,
    imports: [NgIf, PopoverModule],
    templateUrl: './tutorial.component.html'
})
export class TutorialComponent implements OnInit, OnDestroy {
    private tutorial = inject(TutorialService);
    state: TutorialState | null = null;
    private sub: any;
    @ViewChild('popover') popover?: Popover;

    ngOnInit() {
        this.sub = this.tutorial.tutorial$.subscribe(state => {
            this.popover?.hide();
            this.removeHighlight();
            this.state = state;
            if (state) {
                const el = document.querySelector(state.step.selector) as HTMLElement | null;
                if (el) {
                    el.classList.add('tutorial-highlight');
                    el.scrollIntoView({behavior: 'smooth', block: 'center'});
                    setTimeout(() => this.popover?.show(null, el));
                } else {
                    setTimeout(() => this.popover?.show(null));
                }
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

    private removeHighlight() {

        if (this.state) {
            const el = document.querySelector(this.state.step.selector) as HTMLElement | null;
            el?.classList.remove('tutorial-highlight');
        }

    }
}


