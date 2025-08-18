import {Component, ElementRef, HostListener, inject, ViewChild} from '@angular/core';
import {Menubar} from "primeng/menubar";
import {MenuItem, MenuItemCommandEvent} from 'primeng/api';
import {Avatar} from 'primeng/avatar';
import {MenuModule} from 'primeng/menu';
import {ResPongService} from '../../service/res-pong.service';
import {Observable} from 'rxjs';
import {AsyncPipe, NgIf} from '@angular/common';
import {Button} from 'primeng/button';
import {Router, RouterLink} from '@angular/router';
import {BlockUI} from 'primeng/blockui';
import {ProgressSpinner} from 'primeng/progressspinner';


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
    RouterLink
  ],
  standalone: true,
  templateUrl: './menu.component.html'
})
export class MenuComponent {
  private resPongService = inject(ResPongService);
  private router = inject(Router);

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


  private isActive(path: string): boolean {
    console.log(this.router.url, path);
    return this.router.url === path;
  }

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
}
