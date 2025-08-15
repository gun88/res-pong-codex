import {Component, EventEmitter, HostListener, inject, Input, OnInit, Output, ViewChild} from '@angular/core';
import {DatePicker} from 'primeng/datepicker';
import {PrimeNG} from 'primeng/config';
import {Common} from '../../util/common';
import {FormsModule} from '@angular/forms';
import {NgForOf, NgIf} from '@angular/common';
import {Dialog} from 'primeng/dialog';

@Component({
  selector: 'res-pong-user-calendar',
  imports: [
    DatePicker,
    FormsModule,
    NgIf,
    NgForOf,
    Dialog
  ],
  standalone: true,
  templateUrl: './calendar.component.html',
  styleUrl: './calendar.component.scss'
})
export class CalendarComponent implements OnInit {
  private primengConfig = inject(PrimeNG);
  private fullNames = ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];
  private shortNames = ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'];
  private minNames = ['D', 'L', 'M', 'M', 'G', 'V', 'S'];
  private _events!: any;
  @ViewChild('rpc') datePicker!: DatePicker;
  _currentDate: any;
  dayMap: any = {};
  @Output() onDaySelected = new EventEmitter<any[]>();
  showDialog: boolean = false;
  selectedDay: any;

  @Input() set events(value: any) {
    this._events = value;
    this.loadData();
  }

  @Input() set currentDate(value: any) {
    this._currentDate = value;
  }

  ngOnInit(): void {
    this.primengConfig.setTranslation({
      dayNames: this.fullNames,
      dayNamesShort: this.shortNames,
      dayNamesMin: this.minNames,
      monthNames: ["Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno", "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"],
      monthNamesShort: ["Gen", "Feb", "Mar", "Apr", "Mag", "Giu", "Lug", "Ago", "Set", "Ott", "Nov", "Dic"],
      today: 'Oggi',
      clear: 'Cancella',
      dateFormat: 'dd/mm/yy',
      weekHeader: 'Set'
    });
    this.updateDayNames(window.innerWidth);
  }

  @HostListener('window:resize', ['$event'])
  onResize(event: any) {
    this.updateDayNames(event.target.innerWidth);
  }

  private updateDayNames(width: number) {
    if (width >= 768) {
      this.primengConfig.setTranslation({dayNamesMin: this.fullNames});
    } else if (width >= 480) {
      this.primengConfig.setTranslation({dayNamesMin: this.shortNames});
    } else {
      this.primengConfig.setTranslation({dayNamesMin: this.minNames});
    }
  }


  private loadData() {
    if (!this._events?.monthPointer) return
    let {month, year} = Common.getMonthYearFromIndex(this._events.monthPointer);
    this._currentDate = new Date(year, month, 1);
    this.dayMap = this.mapEventsByDay(this._events.events);
    if (this.datePicker)
      this.datePicker.updateUI()

  }


  private mapEventsByDay(events: any[]): any {
    const map: any = {};

    for (const event of events) {
      const day = parseInt(event.start_datetime.substring(8, 10), 10); // prende il giorno
      const month = parseInt(event.start_datetime.substring(5, 7), 10) - 1; // prende il giorno
      if (!map[month]) map[month] = [];
      if (!map[month][day]) map[month][day] = [];
      map[month][day].push(event) // sovrascrive se ci sono più eventi nello stesso giorno
    }

    for (const month in map) {
      for (const day in map[month]) {
        map[month][day].sort((a: any, b: any) => {
          const aTime = Common.extractTime(a.start_datetime);
          const bTime = Common.extractTime(b.start_datetime);
          return aTime.localeCompare(bTime);
        });
      }
    }

    return map;
  }

  onSelectDay(date: any) {
    if (
      !this.dayMap[date.month] ||
      !this.dayMap[date.month][date.day]) {
      return
    }
    this.selectedDay = this.dayMap[date.month][date.day];
    if (this.selectedDay.length === 0) {
      return;
    }
    if (this.selectedDay.length === 1) {
      this.onDaySelected.emit(this.selectedDay[0]);
    }

    this.showDialog = true;

  }

  onSelectEvent(e: any) {
    this.onDaySelected.emit(e);
    this.showDialog = false;
    this.selectedDay = undefined;

  }
}
