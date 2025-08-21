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
        })
      );
  }

  public updateMemoryUser(user: any) {
    localStorage.setItem('res_pong_user', JSON.stringify(user));
  }

  public logOut() {
    return this.http.post(`${this.baseServer}/?rest_route=/res-pong/v1/logout`, {})
      .pipe(tap(() => this.resetMemoryUser()));
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
    return this.http.post(`${this.baseServer}/?rest_route=/res-pong/v1/password/reset`, {email, updatePageUrl});
  }

  public updatePassword(password: string, confirm: string, token = '') {
    if (token)
      return this.http.post(`${this.baseServer}/?rest_route=/res-pong/v1/password/update-by-token`,
        {password, confirm, token});
    else
      return this.http.post(`${this.baseServer}/?rest_route=/res-pong/v1/password/update`, {password, confirm});
  }

  public getUserReservations() {
    return this.http.get(`${this.baseServer}/?rest_route=/res-pong/v1/reservations`);
  }

  public getEvent(eventId: number) {
    return this.http.get(`${this.baseServer}/?rest_route=/res-pong/v1/events/${eventId}`);
  }

  public createReservation(eventId: number) {
    return this.http.post(`${this.baseServer}/?rest_route=/res-pong/v1/reservations`, {event_id: eventId});
  }

  public deleteReservation(eventId: number) {
    return this.http.delete(`${this.baseServer}/?rest_route=/res-pong/v1/reservations&event_id=${eventId}`)
      .pipe(
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
    });

  }

  public subscribeEvent(eventId: any) {
    return this.http.post(`${this.baseServer}/?rest_route=/res-pong/v1/events/${eventId}/subscription`, {});
  }

  public unsubscribeEvent(eventId: any) {
    return this.http.delete(`${this.baseServer}/?rest_route=/res-pong/v1/events/${eventId}/subscription`);
  }

  public getConfigurations() {
    return this.http.get(`${this.baseServer}/?rest_route=/res-pong/v1/configurations`);
  }
}


