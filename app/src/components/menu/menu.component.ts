import {Component, inject} from '@angular/core';
import {Menubar} from "primeng/menubar";
import {MenuItem, MenuItemCommandEvent} from 'primeng/api';
import {Avatar} from 'primeng/avatar';
import {MenuModule} from 'primeng/menu';
import {ResPongService} from '../../service/res-pong.service';
import {Observable} from 'rxjs';
import {AsyncPipe, NgIf} from '@angular/common';
import {Button} from 'primeng/button';
import {Router} from '@angular/router';
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
    ProgressSpinner
  ],
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
}
