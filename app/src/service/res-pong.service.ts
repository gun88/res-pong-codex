import {inject, Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {environment} from '../environments/environment';
import {BehaviorSubject, catchError, map, switchMap, tap} from 'rxjs';


@Injectable({
  providedIn: 'root'
})
export class ResPongService {
  private http = inject(HttpClient)
  private baseServer = environment.server
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
          localStorage.setItem('res_pong_user', JSON.stringify(user));
          this.userSubject.next(user);
        })
      );
  }

  public logOut() {
    return this.http.post(`${this.baseServer}/?rest_route=/res-pong/v1/logout`, {})
      .pipe(
        tap(() => {
          this.resetMemoryUser();
        })
      );
  }


  public resetMemoryUser() {
    localStorage.removeItem('res_pong_user');
    this.userSubject.next(null);
  }

  public getUserData() {
    return this.http.get(`${this.baseServer}/?rest_route=/res-pong/v1/user`)
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
    return this.http.delete(`${this.baseServer}/?rest_route=/res-pong/v1/reservations&event_id=${eventId}`);
  }
}


