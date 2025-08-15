import {Component, Input} from '@angular/core';
import {Avatar} from "primeng/avatar";
import {Message} from 'primeng/message';
import {NgIf} from '@angular/common';

@Component({
  selector: 'res-pong-user-user-data',
  imports: [
    Avatar,
    Message,
    NgIf
  ],
  templateUrl: './user-data.component.html',
  styleUrl: './user-data.component.scss'
})
export class UserDataComponent {
  @Input() loading: boolean = false;
  @Input() user: any = null;
  @Input() error: string = '';
  @Input() title: string = '';
  @Input() disclaimer: string = '';

}
