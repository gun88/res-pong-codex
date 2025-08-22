import {inject, Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {environment} from '../environments/environment';
import {BehaviorSubject, catchError, map, switchMap, tap} from 'rxjs';


@Injectable({
  providedIn: 'root'
})
export class ResPongService {
  private http = inject(HttpClient)
  private baseServer = `${environment.server}/index.php`
  private userSubject = new BehaviorSubject<any>(JSON.parse(localStorage.getItem('res_pong_user') || 'null'));
  readonly user$ = this.userSubject.asObservable();
  readonly loggedIn$ = this.user$.pipe(map(u => !!u));
  private eventSubject = new BehaviorSubject<string>('');
  readonly event$ = this.eventSubject.asObservable();

  constructor() {
    this.user$.pipe(
      switchMap(value => {
        if (value) {
          return this.getUserData().pipe(
            catchError((err: any) => {
              if (err.status === 401 || err.status === 403) {
                this.resetMemoryUser();

              }
              return [];
            })
          );
        } else {
          return [];
        }
      })
    ).subscribe()
  }

  public getEvents(start: string, end: string) {
    return this.http.get<any[]>(`${this.baseServer}/?rest_route=/res-pong/v1/events&start=${start}&end=${end}`);
  }

  public login(username: string, password: string, remember: boolean) {
    return this.http.post(`${this.baseServer}/?rest_route=/res-pong/v1/login`, {username, password, remember})
      .pipe(
        tap((res: any) => {
          let user = res?.success ? res.user : null;
          this.updateMemoryUser(user);
          this.userSubject.next(user);
        }),
        tap(this.manageEvent('login'))
      );
  }

  public updateMemoryUser(user: any) {
    localStorage.setItem('res_pong_user', JSON.stringify(user));
  }

  public logOut() {
    return this.http.post(`${this.baseServer}/?rest_route=/res-pong/v1/logout`, {})
      .pipe(
        tap(this.manageEvent('logout')),
        tap(() => this.resetMemoryUser())
      );
  }

  public resetMemoryUser() {
    localStorage.removeItem('res_pong_user');
    this.userSubject.next(null);
  }

  public getUserData() {
    return this.http.get(`${this.baseServer}/?rest_route=/res-pong/v1/user`)
      .pipe(tap((user: any) => this.updateMemoryUser(user)));
  }

  public getUserDataByToken(token: string) {
    return this.http.post(`${this.baseServer}/?rest_route=/res-pong/v1/user-by-token`, {token});
  }

  public recoverPassword(email: string, updatePageUrl: string) {
    return this.http.post(`${this.baseServer}/?rest_route=/res-pong/v1/password/reset`, {email, updatePageUrl})
      .pipe(tap(this.manageEvent('recover_password')));
  }

  public updatePassword(password: string, confirm: string, token = '') {
    if (token)
      return this.http.post(`${this.baseServer}/?rest_route=/res-pong/v1/password/update-by-token`,
        {password, confirm, token})
        .pipe(tap(this.manageEvent('update_password_by_token')));
    else
      return this.http.post(`${this.baseServer}/?rest_route=/res-pong/v1/password/update`, {password, confirm})
        .pipe(tap(this.manageEvent('update_password')));
  }

  public getUserReservations() {
    return this.http.get(`${this.baseServer}/?rest_route=/res-pong/v1/reservations`);
  }

  public getEvent(eventId: number) {
    return this.http.get(`${this.baseServer}/?rest_route=/res-pong/v1/events/${eventId}`);
  }

  public createReservation(eventId: number) {
    return this.http.post(`${this.baseServer}/?rest_route=/res-pong/v1/reservations`, {event_id: eventId})
      .pipe(tap(this.manageEvent('reservations')));
  }

  public deleteReservation(eventId: number) {
    return this.http.delete(`${this.baseServer}/?rest_route=/res-pong/v1/reservations&event_id=${eventId}`)
      .pipe(
        tap(this.manageEvent('delete_reservation')),
        tap(() => {
          const microtime = Date.now() * 1000;
          this.http.get(`${environment.server}/wp-cron.php?doing_wp_cron=${microtime}`).subscribe()
        })
      );
  }

  public saveEmailPreferences(send_email_on_reservation: boolean, send_email_on_deletion: boolean) {
    return this.http.post(`${this.baseServer}/?rest_route=/res-pong/v1/user/email-preferences`, {
      send_email_on_reservation,
      send_email_on_deletion
    })
      .pipe(tap(this.manageEvent('save_email_preferences')));

  }

  public subscribeEvent(eventId: any) {
    return this.http.post(`${this.baseServer}/?rest_route=/res-pong/v1/events/${eventId}/subscription`, {})
      .pipe(tap(this.manageEvent('subscribe_event')));
  }

  public unsubscribeEvent(eventId: any) {
    return this.http.delete(`${this.baseServer}/?rest_route=/res-pong/v1/events/${eventId}/subscription`)
      .pipe(tap(this.manageEvent('unsubscribe_event')));
  }

  public getConfigurations() {
    return this.http.get(`${this.baseServer}/?rest_route=/res-pong/v1/configurations`);
  }

  private manageEvent(eventName: string) {
    return {
      next: () => this.eventSubject.next(`${eventName}_success`),
      error: (err: any) => {
        const code = err?.status ? `_${err.status}` : '';
        this.eventSubject.next(`${eventName}_error${code}`);
      }
    };
  }
}


