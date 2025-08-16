import {Component, EventEmitter, Input, Output} from '@angular/core';
import {NgForOf, NgIf} from '@angular/common';
import {Timeline} from 'primeng/timeline';
import {Common} from '../../util/common';
import {Skeleton} from 'primeng/skeleton';
import {ToggleSwitch} from 'primeng/toggleswitch';
import {FormsModule} from '@angular/forms';
import {Message} from 'primeng/message';

@Component({
  selector: 'res-pong-user-timeline',
  imports: [
    Timeline,
    NgIf,
    Skeleton,
    NgForOf,
    ToggleSwitch,
    FormsModule,
    Message
  ],
  standalone: true,
  templateUrl: './timeline.component.html',
  styleUrl: './timeline.component.scss'
})
export class TimelineComponent {
  @Output() onDaySelected = new EventEmitter<any[]>();
  private originalData: any = undefined;
  days!: any;
  monthPointerChangeWorked: boolean = false;
  _events!: any;
  _monthPointer!: number;
  @Input() loading!: boolean;
  showAll = localStorage.getItem('res_pong_timeline_show_all') == 'false';

  @Input()
  set monthPointer(monthPointer: number) {
    this.monthPointerChangeWorked = false;
    this._monthPointer = monthPointer;
    this._events = {}
    this.days = []
    this.prepareStructure();
  }

  @Input()
  set events(value: any) {
    setTimeout(() => {
      this.originalData = value;
      this.prepareData()
    })
  }

  onSelectEvent(e: any) {
    this.onDaySelected.emit(e);

  }

  private prepareData() {
    if (!this.originalData) return;
    this._events = this.mapEventsByDay(this.originalData.events);
    this.prepareStructure(this.showAll ? undefined : Object.keys(this._events));
    this.monthPointerChangeWorked = true;
  }


  private mapEventsByDay(events: any[]): any {
    const map: any = {};

    for (const event of events) {
      const day = (event.start_datetime.substring(0, 10)); // prende il giorno
      if (!map[day]) map[day] = [];
      map[day].push(event) // sovrascrive se ci sono più eventi nello stesso giorno
    }

    for (const day in map) {
      map[day].sort((a: any, b: any) => {
        const aTime = Common.extractTime(a.start_datetime);
        const bTime = Common.extractTime(b.start_datetime);
        return aTime.localeCompare(bTime);
      });
    }

    return map;
  }

  private prepareStructure(keys?: string[]) {
    let days = Common.getDaysOfMonth(this._monthPointer);
    if (keys) {
      days = days.filter((day: any) => keys.includes(day.date))
    }
    days.forEach((day: any) => day.dayName = ["LUN", "MAR", "MER", "GIO", "VEN", "SAB", "DOM"][day.dayIndex])
    this.days = days;
  }

  showAllToggle() {
    localStorage.setItem('res_pong_timeline_show_all', JSON.stringify(this.showAll));
    this.prepareData()
  }
}
