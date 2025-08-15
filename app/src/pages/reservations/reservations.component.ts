import {Component, inject, OnDestroy, OnInit} from '@angular/core';
import {ResPongService} from '../../service/res-pong.service';
import {Router} from '@angular/router';
import {Button} from 'primeng/button';
import {ProgressBar} from 'primeng/progressbar';
import {NgIf, NgStyle} from '@angular/common';
import {CalendarComponent} from '../../components/calendar/calendar.component';
import {TimelineComponent} from '../../components/timeline/timeline.component';
import {Common} from '../../util/common';
import {BehaviorSubject, of, Subject} from 'rxjs';
import {catchError, debounceTime, distinctUntilChanged, map, switchMap, takeUntil, tap} from 'rxjs/operators';

@Component({
  selector: 'res-pong-user-reservations',
  imports: [
    Button,
    ProgressBar,
    NgStyle,
    CalendarComponent,
    TimelineComponent,
    NgIf
  ],
  standalone: true,
  templateUrl: './reservations.component.html',
  styleUrl: './reservations.component.scss'
})
export class ReservationsComponent implements OnInit, OnDestroy {
  private resPongService = inject(ResPongService);
  private router = inject(Router);

  private destroy$ = new Subject<void>();
  private monthPointer$ = new BehaviorSubject<number>(Common.getMonthIndexFromDate());

  monthPointer: number = this.monthPointer$.value;
  loading = false;
  currentDate = new Date();
  title = Common.formatMonthYear(this.monthPointer);
  mode: 'calendar' | 'timeline' = (localStorage.getItem('res_pong_reservation_view_mode') || 'calendar') as 'calendar' | 'timeline';
  events: any = undefined;
  subTitle: string = '•••';

  ngOnInit(): void {
    this.monthPointer$.pipe(
      tap(ptr => {
        this.monthPointer = ptr;
        this.currentDate = Common.getFirstDayOfTheMonth(ptr);
        this.title = Common.formatMonthYear(ptr);
        this.subTitle = '•••'
      }),
      distinctUntilChanged(),
      debounceTime(550),
      map(ptr => Common.getMonthStartEnd(ptr)),
      tap(() => this.loading = true),
      switchMap(({start, end}) =>
        this.resPongService.getEvents(start, end).pipe(
          catchError(() => {
            // swallow error and return empty list
            return of([] as any[]);
          }),
          tap((events: any[]) => {
            events.forEach((event: any) => {
              Common.fixNumber(event, 'id')
              Common.fixNumber(event, 'players_count')
              Common.fixNumber(event, 'max_players')
              Common.fixNumber(event, 'group_id')
              Common.fixNumber(event, 'booked')
              Common.fixNumber(event, 'enabled')
              event.time = Common.extractTime(event.start_datetime);
              event.count = event.max_players ? (event.players_count + '/' + event.max_players) : undefined;
            })
          }),
          map((events: any[]) => ({
            events, start, end, monthPointer: this.monthPointer$.value,
          }))
        )
      ),
      takeUntil(this.destroy$)
    ).subscribe((events: any) => {
      this.loading = false;
      this.events = events;
      try {
        let datePrefix = Common.getDatePrefix(this.monthPointer);
        this.subTitle = (events.events.filter((e: any) => e.start_datetime.startsWith(datePrefix))).length.toString() + ' eventi';
      } catch (e) {
        this.subTitle = '•••'
      }
    });

    // trigger iniziale (BehaviorSubject ha già il valore iniziale)
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  public prev() {
    this.monthPointer$.next(this.monthPointer - 1);
  }

  public next() {
    this.monthPointer$.next(this.monthPointer + 1);
  }

  public today() {
    this.monthPointer$.next(Common.getMonthIndexFromDate());
  }

  public month() {
    localStorage.setItem('res_pong_reservation_view_mode', 'calendar');
    this.mode = 'calendar';
  }

  public list() {
    localStorage.setItem('res_pong_reservation_view_mode', 'timeline');
    this.mode = 'timeline';
  }

  onSelection(event: any) {
    this.router.navigate(['/events', event.id]);
  }
}
