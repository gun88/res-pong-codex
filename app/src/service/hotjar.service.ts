import {Injectable} from '@angular/core';
import Hotjar from '@hotjar/browser';
import {environment} from '../environments/environment';

@Injectable({providedIn: 'root'})
export class HotjarService {
  init() {
    const hotjarSiteId = environment.hotjarSiteId
    const hotjarVersion = environment.hotjarVersion
    Hotjar.init(hotjarSiteId, hotjarVersion);
  }

  stateChange(url: string) {
    Hotjar.stateChange(url); // utile per SPA se necessario
  }
}
