import {Component, inject, OnInit, ViewEncapsulation} from '@angular/core';
import {NavigationEnd, Router, RouterOutlet} from '@angular/router';
import {MenuModule} from 'primeng/menu';
import {Divider} from 'primeng/divider';
import {MenuComponent} from '../components/menu/menu.component';
import {TutorialComponent} from '../components/tutorial/tutorial.component';
import {environment} from '../environments/environment';
import {HotjarService} from '../service/hotjar.service';
import {filter} from 'rxjs';
import {ResPongService} from '../service/res-pong.service';

@Component({
  selector: 'res-pong-user-root',
  imports: [RouterOutlet, MenuComponent, MenuModule, Divider, TutorialComponent],
  templateUrl: './app.component.html',
  encapsulation: ViewEncapsulation.Emulated,
  styleUrl: './app.component.scss'
})
export class AppComponent implements OnInit {
  private router = inject(Router);
  private hotjarService = inject(HotjarService);

  year: string = new Date().getFullYear().toString();
  version: string = environment.version;
  build: string = environment.build;

  ngOnInit(): void {
    this.hotjarService.init();

  }

}
