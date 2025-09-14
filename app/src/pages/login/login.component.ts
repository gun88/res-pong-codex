import {Component, inject, OnInit} from '@angular/core';
import {FormBuilder, ReactiveFormsModule, Validators} from '@angular/forms';
import {InputTextModule} from 'primeng/inputtext';
import {PasswordModule} from 'primeng/password';
import {ButtonModule} from 'primeng/button';
import {CheckboxModule} from 'primeng/checkbox';
import {Router, RouterModule} from '@angular/router';
import {ResPongService} from '../../service/res-pong.service';
import {catchError, finalize, of, tap} from 'rxjs';
import {Message} from 'primeng/message';
import {NgIf} from '@angular/common';
import {ProgressBar} from 'primeng/progressbar';
import {FloatLabel} from 'primeng/floatlabel';
import {SafeHtmlPipe} from 'primeng/menu';

@Component({
  selector: 'res-pong-login',
  standalone: true,
  templateUrl: './login.component.html',
  styleUrl: './login.component.scss',
  imports: [
    ReactiveFormsModule,
    InputTextModule,
    PasswordModule,
    ButtonModule,
    CheckboxModule,
    RouterModule,
    Message,
    NgIf,
    ProgressBar,
    FloatLabel,
    SafeHtmlPipe
  ]
})
export class LoginComponent implements OnInit {
  private resPongService = inject(ResPongService);
  private router = inject(Router);
  loading = false;
  form = inject(FormBuilder).group({
    username: ['', Validators.required],
    password: ['', Validators.required],
    remember: [true]
  });
  error: string = '';
  loginDisclaimer = '...';

  ngOnInit(): void {
    this.resPongService.getLoginDisclaimer()
      .pipe(
        tap(value => this.loginDisclaimer = value)
      )
      .subscribe()
  }
  onSubmit() {
    if (this.form.invalid || this.loading) return;

    this.error = ''
    this.loading = true;
    this.form.disable();
    const {username, password, remember} = this.form.value;
    this.resPongService.login(username as string, password as string, remember as boolean)
      .pipe(
        catchError((err) => {
          // Map HTTP errors to a uniform payload
          const msg =
            err?.status === 403 ? 'Utente disabilitato.' :
              err?.status === 401 ? 'Username o password non validi.' :
                'Si è verificato un errore. Riprova.';
          this.loading = false;
          this.form.enable();
          return of({success: false, error: msg, user: null} as any);
        }),
        finalize(() => {
          this.loading = false;
          this.form.enable();
        })
      )
      .subscribe((value: any) => {
        if (value?.success) {
          this.error = '';
          this.router.navigate(['/reservations']);
        } else {
          this.error = value?.error ?? 'Si è verificato un errore. Riprova.';
        }
      });
  }
}
