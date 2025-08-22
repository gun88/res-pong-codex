import {inject, Injectable} from '@angular/core';
import Hotjar from '@hotjar/browser';
import {ResPongService} from './res-pong.service';
import {filter, tap} from 'rxjs';
import {NavigationEnd, Router} from '@angular/router';

@Injectable({providedIn: 'root'})
export class HotjarService {
  private router = inject(Router);

  private resPongService = inject(ResPongService);
  private initPromise: Promise<void> | null = null;
  private enabled: boolean = false;

  public init(): Promise<void> {
    if (this.initPromise) return this.initPromise; // già in corso o già fatto

    this.initPromise = new Promise<void>((resolve, reject) => {
      this.resPongService.getConfigurations().subscribe({
        next: (cfg: any) => {
          try {
            if (!cfg.hotjar_id) {
              this.enabled = false;
              resolve();
              return;
            }
            Hotjar.init(cfg.hotjar_id, cfg.hotjar_version);

            this.router.events
              .pipe(filter(e => e instanceof NavigationEnd))
              .subscribe((e: NavigationEnd) => this.stateChange(e.urlAfterRedirects));


            this.resPongService.user$.pipe(
              tap((user: any) => this.identify(user))
            ).subscribe()

            this.enabled = true;
            resolve();
          } catch (e) {
            reject(e);
          }
        },
        error: (err) => reject(err)
      });
    });

    return this.initPromise;
  }

  public async stateChange(url: string): Promise<void> {
    if (!this.enabled) return;
    await this.init();
    Hotjar.stateChange(url);
  }

  public async identify(user: any): Promise<void> {
    if (!this.enabled) return;
    await this.init();
    Hotjar.identify(user?.id, user);
  }
}
