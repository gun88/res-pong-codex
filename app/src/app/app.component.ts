import {Component, ViewEncapsulation} from '@angular/core';
import {RouterOutlet} from '@angular/router';
import {MenuModule} from 'primeng/menu';
import {Divider} from 'primeng/divider';
import {MenuComponent} from '../components/menu/menu.component';

@Component({
  selector: 'res-pong-user-root',
  imports: [RouterOutlet, MenuComponent, MenuModule, Divider],
  templateUrl: './app.component.html',
  encapsulation: ViewEncapsulation.Emulated,
  styleUrl: './app.component.scss'
})
export class AppComponent {
  year: string = new Date().getFullYear().toString();

}
