import {AfterViewInit, Component, ElementRef, HostListener, inject, NgZone, OnDestroy, ViewChild} from '@angular/core';
import {Menubar} from "primeng/menubar";
import {MenuItem, MenuItemCommandEvent} from 'primeng/api';
import {Avatar} from 'primeng/avatar';
import {MenuModule} from 'primeng/menu';
import {ResPongService} from '../../service/res-pong.service';
import {TutorialService} from '../../service/tutorial.service';
import {Observable} from 'rxjs';
import {AsyncPipe, NgIf} from '@angular/common';
import {Button} from 'primeng/button';
import {Router, RouterLink} from '@angular/router';
import {BlockUI} from 'primeng/blockui';
import {ProgressSpinner} from 'primeng/progressspinner';
import {Tooltip} from 'primeng/tooltip';


@Component({
  selector: 'res-pong-user-menu',
  imports: [
    Menubar,
    Avatar,
    MenuModule,
    AsyncPipe,
    NgIf,
    Button,
    BlockUI,
    ProgressSpinner,
    RouterLink,
    Tooltip
  ],
  standalone: true,
  templateUrl: './menu.component.html'
})
export class MenuComponent implements AfterViewInit, OnDestroy {
  private resPongService = inject(ResPongService);
  private router = inject(Router);
  private tutorial = inject(TutorialService);

  loggingOut = false;

  user$: Observable<any> = this.resPongService.user$;
  loggedIn$ = this.resPongService.loggedIn$;

  menu: MenuItem[] = [
    {
      label: 'Prenotazioni',
      icon: 'pi pi-calendar-plus',
      routerLink: '/reservations'
    },
    {
      label: 'Storico',
      icon: 'pi pi-history',
      routerLink: '/history'
    },
    {
      label: 'Torna al sito',
      icon: 'pi pi-external-link ',
      url: 'https://tennistavolomorelli.it/'
    }
  ];

  userMenu: MenuItem[] = [
    {
      label: 'Utente',
      items: [
        {
          label: 'Profilo Utente',
          icon: 'pi pi-user',
          routerLink: '/user',
        },
        {
          separator: true
        },
        {
          label: 'Log Out',
          icon: 'pi pi-sign-out',
          command: (event: MenuItemCommandEvent) => {
            this.loggingOut = true;
            this.resPongService.logOut().subscribe(
              () => {
                this.loggingOut = false;
                this.router.navigate(['/login']);
              },
              error => {
                this.loggingOut = false;
                console.error(error);
                alert("Errore durante la logout. Riprova!")
              }
            );

          }
        }
      ]
    }
  ];

  @ViewChild('mb', {read: ElementRef}) menubarEl!: ElementRef<HTMLElement>;


  @HostListener('document:click', ['$event'])
  onDocClick(ev: MouseEvent) {
    const root = this.menubarEl?.nativeElement;
    if (!root) return;

    const target = ev.target as Node;

    // 1) Trova il bottone hamburger della Menubar
    const btn = root.querySelector('.p-menubar-button') as HTMLButtonElement | null;

    // Se il click è sul bottone (o dentro di lui), lascia che gestisca lui il toggle
    if (btn && (target === btn || btn.contains(target))) return;

    // 2) Trova il contenitore del menu mobile (submenu) renderizzato dalla Menubar
    // Usa il selettore specifico che hai indicato e qualche fallback comune
    const submenu = root.querySelector(
      '.p-menubarsub, .p-menubar-root-list, .p-submenu-list'
    ) as HTMLElement | null;

    const clickedInsideSubmenu = submenu?.contains(target) ?? false;

    // 3) Se il menu è aperto e il click NON è dentro il submenu => chiudi
    const isOpen = btn?.getAttribute('aria-expanded') === 'true';
    if (btn && isOpen && !clickedInsideSubmenu) {
      btn.click(); // richiude usando la logica nativa della Menubar
    }
  }

  help() {
    this.tutorial.start();
  }

  private zone = inject(NgZone);


  @ViewChild(Tooltip) tip!: Tooltip;

  private idleMs = 10000;
  private timer: any;

  ngAfterViewInit(): void {
    this.bindActivityListeners();
    this.resetTimer();
  }

  ngOnDestroy(): void {
    this.clearTimer();
    window.removeEventListener('mousemove', this.onActivity, true);
    window.removeEventListener('keydown', this.onActivity, true);
    window.removeEventListener('click', this.onActivity, true);
    window.removeEventListener('scroll', this.onActivity, true);
    window.removeEventListener('touchstart', this.onActivity, true);
  }

  private onActivity = () => this.resetTimer();

  private bindActivityListeners(): void {
    window.addEventListener('mousemove', this.onActivity, true);
    window.addEventListener('keydown', this.onActivity, true);
    window.addEventListener('click', this.onActivity, true);
    window.addEventListener('scroll', this.onActivity, true);
    window.addEventListener('touchstart', this.onActivity, true);
  }

  private resetTimer(): void {
    this.clearTimer();
    this.zone.runOutsideAngular(() => {
      this.timer = setTimeout(() => {
        this.zone.run(() => {
          if (this.tip) this.tip.show();
        });
      }, this.idleMs);
    });
  }

  private clearTimer(): void {
    if (this.timer) {
      clearTimeout(this.timer);
      this.timer = null;
    }
  }
}
