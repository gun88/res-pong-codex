import {inject, Injectable} from '@angular/core';
import Hotjar from '@hotjar/browser';
import {ResPongService} from './res-pong.service';

@Injectable({providedIn: 'root'})
export class HotjarService {
  private resPongService = inject(ResPongService);
  private initialized = false;

  public stateChange(url: string) {
    if (this.initialized) {
      Hotjar.stateChange(url);
    } else {
      this.resPongService.getConfigurations().subscribe((configurations: any) => {
        Hotjar.init(configurations.hotjar_id, configurations.hotjar_version);
        this.initialized = true;
        Hotjar.stateChange(url);
      })
    }
  }
}
