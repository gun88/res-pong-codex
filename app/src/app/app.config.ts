import {ApplicationConfig, inject, LOCALE_ID, provideZoneChangeDetection} from '@angular/core';
import {provideRouter, Router, withHashLocation} from '@angular/router';

import {routes} from './app.routes';
import {provideAnimations} from '@angular/platform-browser/animations';
import {providePrimeNG} from 'primeng/config';
import Lara from '@primeng/themes/lara';
import {definePreset} from '@primeng/themes';
import {HttpInterceptorFn, provideHttpClient, withInterceptors} from '@angular/common/http';
import {tap} from 'rxjs';
import {registerLocaleData} from '@angular/common';
import localeIt from '@angular/common/locales/it';

registerLocaleData(localeIt);

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const isWp = req.url.includes('/?rest_route=') || req.url.includes('/wp-json/');
  const authReq = isWp ? req.clone({withCredentials: true}) : req
  const router = inject(Router);
  return next(authReq).pipe(
    tap({
      error: (err) => {
        if (err.status === 401 || err.status === 403) {
          router.navigate(['/login']);
        }
      }
    })
  );
};

export const appConfig: ApplicationConfig = {
  providers: [
    provideHttpClient(withInterceptors([authInterceptor])),
    provideZoneChangeDetection({eventCoalescing: true}),
    provideRouter(routes, withHashLocation()),
    {provide: LOCALE_ID, useValue: 'it'},
    providePrimeNG({
      theme: {
        preset: definePreset(Lara, {
          semantic: {
            primary: {
              50: '{blue.50}',
              100: '{blue.100}',
              200: '{blue.200}',
              300: '{blue.300}',
              400: '{blue.400}',
              500: '{blue.500}',
              600: '{blue.600}',
              700: '{blue.700}',
              800: '{blue.800}',
              900: '{blue.900}',
              950: '{blue.950}'
            }
          }
        }),
        options: {
          darkModeSelector: '.dark-mode'
        }
      }
    }),
    provideAnimations()
  ]
};
