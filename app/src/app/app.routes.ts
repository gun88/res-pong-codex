import {CanActivateFn, Router, Routes} from '@angular/router';
import {ReservationsComponent} from '../pages/reservations/reservations.component';
import {HistoryComponent} from '../pages/history/history.component';
import {UserComponent} from '../pages/user/user.component';
import {LoginComponent} from '../pages/login/login.component';
import {inject} from '@angular/core';
import {ResPongService} from '../service/res-pong.service';
import {map, tap} from 'rxjs';
import {PasswordRecoveryComponent} from '../pages/password/password-recovery.component';
import {PasswordUpdateComponent} from '../pages/password/password-update.component';
import {PasswordFirstAccessComponent} from '../pages/password/password-first-access.component';
import {EventComponent} from '../pages/event/event.component';



export const isLogged: CanActivateFn = () => {
  const router = inject(Router)
  const resPongService = inject(ResPongService)
  return resPongService.loggedIn$.pipe(map(ok => ok || router.createUrlTree(['/login'])));
};

export const isNotLogged: CanActivateFn = () => {
  const router = inject(Router)
  const resPongService = inject(ResPongService)
  return resPongService.loggedIn$.pipe(
    map(ok => !ok || router.createUrlTree(['/'])));
};

export const routes: Routes = [
  {path: 'reservations', component: ReservationsComponent, canActivate: [isLogged]},
  {path: 'history', component: HistoryComponent, canActivate: [isLogged]},
  {path: 'user', component: UserComponent, canActivate: [isLogged]},
  {path: 'events/:id', component: EventComponent, canActivate: [isLogged]},
  {path: 'login', component: LoginComponent, canActivate: [isNotLogged]},
  {path: 'password-recovery', component: PasswordRecoveryComponent, canActivate: [isNotLogged]},
  {path: 'password-update', component: PasswordUpdateComponent, canActivate: [isNotLogged]},
  {path: 'first-access', component: PasswordFirstAccessComponent, canActivate: [isNotLogged]},
  {path: '', redirectTo: 'reservations', pathMatch: 'full'},
  {path: '**', redirectTo: 'reservations'}
];
