import {Component, ViewEncapsulation} from '@angular/core';
import {RouterOutlet} from '@angular/router';
import {MenuModule} from 'primeng/menu';
import {Divider} from 'primeng/divider';
import {MenuComponent} from '../components/menu/menu.component';
import {TutorialComponent} from '../components/tutorial/tutorial.component';
import {environment} from '../environments/environment';

@Component({
  selector: 'res-pong-user-root',
  imports: [RouterOutlet, MenuComponent, MenuModule, Divider, TutorialComponent],
  templateUrl: './app.component.html',
  encapsulation: ViewEncapsulation.Emulated,
  styleUrl: './app.component.scss'
})
export class AppComponent {
  year: string = new Date().getFullYear().toString();
  version: string = environment.version;
  build: string = environment.build;

}
