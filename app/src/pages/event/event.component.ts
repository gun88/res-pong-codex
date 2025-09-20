import {Component, inject, OnInit} from '@angular/core';
import {DatePipe, NgForOf, NgIf, NgStyle} from '@angular/common';
import {Tag} from 'primeng/tag';
import {ProgressBar} from 'primeng/progressbar';
import {Divider} from 'primeng/divider';
import {Message} from 'primeng/message';
import {Badge} from 'primeng/badge';
import {Avatar} from 'primeng/avatar';
import {Button, ButtonDirective} from 'primeng/button';
import {FormsModule} from '@angular/forms';
import {catchError, EMPTY, finalize, map, of, switchMap, tap} from 'rxjs';
import {Skeleton} from 'primeng/skeleton';
import {ResPongService} from '../../service/res-pong.service';
import {ActivatedRoute, Router} from '@angular/router';
import {Common} from '../../util/common';
import {ConfirmDialog} from 'primeng/confirmdialog';
import {ConfirmationService} from 'primeng/api';
import {ToggleSwitch} from 'primeng/toggleswitch';
import {Popover} from 'primeng/popover';
import {SafeHtmlPipe} from 'primeng/menu';

@Component({
  selector: 'res-pong-user-event',
  imports: [
    DatePipe,
    Tag,
    ProgressBar,
    Divider,
    Message,
    NgIf,
    Badge,
    Avatar,
    NgForOf,
    ButtonDirective,
    FormsModule,
    NgStyle,
    Skeleton,
    Button,
    ConfirmDialog,
    ToggleSwitch,
    Popover,
    SafeHtmlPipe
  ],
  standalone: true,
  providers: [ConfirmationService],
  templateUrl: './event.component.html',
  styleUrl: './event.component.scss'
})
export class EventComponent implements OnInit {
  private router = inject(Router);
  private resPongService = inject(ResPongService);
  private activatedRoute = inject(ActivatedRoute);
  private confirmationService = inject(ConfirmationService);

  event!: any;
  loading = false;
  dev = Math.floor(Math.random() * 20);
  private eventId!: number;
  eventLoadingError: any = false;


  ngOnInit(): void {
    this.activatedRoute.paramMap
      .pipe(
        map((params: any) => params.get('id') || '-1'),
        tap((eventId: number) => this.eventId = eventId),
        tap(() => this.event = undefined),
        switchMap((eventId: number) => this.resPongService.getEvent(eventId)),
        catchError((err, caught) => {
          this.eventLoadingError = "Errore durante il caricamento dell'evento.";
          if (err?.status === 404) {
            this.eventLoadingError = "Errore durante il caricamento dell'evento: evento non trovato.";
          }
          return EMPTY;
        }),
        tap((event: any) => this.enrichEvent(event)),
        tap((event: any) => this.event = event)
      )
      .subscribe();

  }


  onJoin() {
    this.callAction(this.resPongService.createReservation(this.eventId))
  }

  onRemove() {
    this.confirmationService.confirm({
      message: 'Una volta cancellata la prenotazione potresti perdere la priorità per questo evento. Sei sicuro di voler cancellare questa prenotazione?',
      header: 'Conferma',
      icon: 'pi pi-exclamation-triangle',
      acceptLabel: 'Sì',
      rejectLabel: 'No',
      closable: true,
      closeOnEscape: true,
      rejectButtonProps: {
        label: 'Annulla',
        severity: 'secondary',
        outlined: true,
      },
      acceptButtonProps: {
        severity: 'danger',
        label: 'Cancella',
      },
      accept: () => {
        this.callAction(this.resPongService.deleteReservation(this.eventId))
      }
    });

  }


  private enrichEvent(event: any) {
    Common.fixNumber(event, 'id')
    Common.fixNumber(event, 'max_players')
    Common.fixNumber(event, 'group_id')
    Common.fixNumber(event, 'enabled')

    event.date = new Date(event.start_datetime);
    event.duration = Common.duration(event.start_datetime, event.end_datetime);

    event.players.forEach((p: any) => {
      p.full_name = p.first_name + ' ' + p.last_name;
      p.monogram = p.first_name[0] + p.last_name[0];
    })
    let severity = this.getSeverityByStatus(event);
    event.players_badge_color = severity
    event.progress_bar_color = 'var(--p-tag-' + severity + '-background)';
    if (severity === 'secondary') {
      event.progress_bar_color = 'var(--p-gray-400)';
    }
    if (event.status_message) {

      event.status_message.icon = this.getStatusMessageIcon(event.status_message.type);
    }

  }

  private getSeverityByStatus(event: any) {
    if (event.status === "closed") {
      return 'secondary';
    } else if (event.status === "disabled") {
      return 'contrast';
    } else if (event.booked) {
      return 'success';
    } else if (event.status === "almost-closed") {
      return 'info';
    } else if (event.status === "full") {
      return 'danger';
    } else if (event.status === "almost-full") {
      return 'warn';
    } else {
      return 'primary';
    }
  }

  private callAction(observable: any) {
    this.loading = true;
    observable
      .pipe(
        catchError((err) => {
          console.error(err);
          let msg = err?.status === 400 ? 'Azione non concessa.' : 'Si è verificato un errore. Riprova.';
          if (err?.error?.message) {
            msg = err.error.message;
          }
          return of({
            ...this.event,
            status_message: {type: (err?.status === 400 ? 'warn' : 'error'), text: msg,}
          });
        }),
        finalize(() => this.loading = false),
        tap((response: any) => {
          this.event = response;
          this.enrichEvent(this.event);
        })
      ).subscribe()


  }

  private getStatusMessageIcon(type: string) {
    switch (type) {
      case 'warn':
        return 'pi pi-exclamation-triangle';
      case 'error':
        return 'pi pi-times-circle';
      case 'success':
        return 'pi pi-check-circle';
      case 'info':
        return 'pi pi-info-circle';
      default:
        return 'pi pi-info-circle';
    }
  }

  next() {
    if (this.event.other_events.next_id !== null)
      this.router.navigate(['/events', this.event.other_events.next_id])
  }

  prev() {
    if (this.event.other_events.prev_id !== null)
      this.router.navigate(['/events', this.event.other_events.prev_id])
  }


  toggleSubscribe() {
    this.loading = true;
    let subscribe = this.event.can_unsubscribe;
    this.event.can_subscribe = !this.event.can_subscribe;
    ((subscribe) ? this.resPongService.subscribeEvent(this.event.id) :this.resPongService.unsubscribeEvent(this.event.id))
      .pipe(
        catchError((err) => {
          console.error(err);
          let msg = err?.status === 400 ? 'Azione non concessa.' : 'Si è verificato un errore. Riprova.';
          if (err?.error?.message) {
            msg = err.error.message;
          }
          return of({
            ...this.event,
            status_message: {type: (err?.status === 400 ? 'warn' : 'error'), text: msg,}
          });
        }),
        finalize(() => this.loading = false),
        tap((response: any) => {
          this.event = response;
          this.enrichEvent(this.event);
        })

      ).subscribe()
  }
}
