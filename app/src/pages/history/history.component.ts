import {Component, inject, OnInit} from '@angular/core';
import {ResPongService} from '../../service/res-pong.service';
import {catchError, tap} from 'rxjs';
import {Message} from 'primeng/message';
import {DatePipe, NgIf} from '@angular/common';
import {TableModule} from 'primeng/table';
import {Button} from 'primeng/button';
import {RouterLink} from '@angular/router';
import {Common} from '../../util/common';

@Component({
  selector: 'res-pong-user-history',
  imports: [
    Message,
    NgIf,
    TableModule,
    Button,
    RouterLink,
    DatePipe
  ],
  templateUrl: './history.component.html',
  styleUrl: './history.component.scss'
})
export class HistoryComponent implements OnInit {


  private resPongService = inject(ResPongService);
  error: string = '';
  loading: boolean = false;
  reservations: any[] = [];
  cols = [
    {field: 'name', header: 'Evento'},
    {field: 'start_datetime', header: 'Data'},
    {field: 'created_at', header: 'Prenotazione'},
    {field: 'presence_confirmed', header: 'Presenza'},
  ];


  ngOnInit(): void {
    this.loading = true;
    this.error = '';
    this.resPongService.getUserReservations()
      .pipe(
        catchError((error: any) => {
          console.error('Errore nel caricamento dati utente:', error);
          this.error = 'Si è verificato un errore durante il caricamento dei dati utente.';
          this.loading = false;
          return [];
        }),
        tap((reservations: any) => {
          this.loading = false;
          reservations.forEach((reservation: any) => {
            reservation.presence_confirmed = this.isPast(reservation.start_datetime) ? reservation.presence_confirmed : 'future';
            reservation.date = new Date(reservation.start_datetime);
            reservation.duration = Common.duration(reservation.start_datetime, reservation.end_datetime);
            reservation.creation_date = new Date(reservation.created_at);
          })
          this.reservations = reservations
        })
      )
      .subscribe()
  }

  private isPast(dateTimeStr: string): boolean {
    const inputDate = new Date(dateTimeStr.replace(" ", "T"));
    const now = new Date();
    return inputDate.getTime() < now.getTime();
  }

}
