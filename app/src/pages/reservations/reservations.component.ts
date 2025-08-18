import {Component, inject, OnDestroy, OnInit} from '@angular/core';
import {ResPongService} from '../../service/res-pong.service';
import {ActivatedRoute, Router} from '@angular/router';
import {Button} from 'primeng/button';
import {ProgressBar} from 'primeng/progressbar';
import {NgIf, NgStyle} from '@angular/common';
import {CalendarComponent} from '../../components/calendar/calendar.component';
import {TimelineComponent} from '../../components/timeline/timeline.component';
import {Common} from '../../util/common';
import {of, Subject} from 'rxjs';
import {catchError, debounceTime, distinctUntilChanged, map, switchMap, takeUntil, tap} from 'rxjs/operators';
import {TutorialService, TutorialStep} from '../../service/tutorial.service';

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
  private activatedRoute = inject(ActivatedRoute);
  private resPongService = inject(ResPongService);
  private router = inject(Router);
  private tutorial = inject(TutorialService);

  private destroy$ = new Subject<void>();

  monthPointer!: number;
  loading = true;
  currentDate = new Date();
  title!: string;
  mode: 'calendar' | 'timeline' = (localStorage.getItem('res_pong_reservation_view_mode') || 'timeline') as 'calendar' | 'timeline';
  events: any = undefined;
  subTitle: string = '•••';
  private tutorialSteps: TutorialStep[] = [
    {selector: '.rp-reservations-title-box', text: 'In questa sezione trovi il mese corrente.'},
    {selector: '.rp-calendar-legend', text: 'Qui trovi la legenda delle disponibilità.'}
  ];

  ngOnInit(): void {
    this.tutorial.register('/reservations', this.tutorialSteps);
    this.activatedRoute.paramMap.pipe(
      map(params => params.has('index') ? Number(params.get('index')) : Common.getMonthIndexFromDate()),
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
            events, start, end, monthPointer: this.monthPointer,
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
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  public prev() {
    this.router.navigate(['/reservations', this.monthPointer - 1]);
  }

  public next() {
    this.router.navigate(['/reservations', this.monthPointer + 1]);
  }

  public today() {
    this.router.navigate(['/reservations', Common.getMonthIndexFromDate()]);
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
