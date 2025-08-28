import {Component, EventEmitter, inject, Input, OnInit, Output} from '@angular/core';
import {FormBuilder, FormGroup, ReactiveFormsModule} from '@angular/forms';
import {ToggleSwitchModule} from 'primeng/toggleswitch';
import {ButtonModule} from 'primeng/button';
import {NgIf} from '@angular/common';
import {ProgressBar} from 'primeng/progressbar';
import {Message} from 'primeng/message';
import {Skeleton} from 'primeng/skeleton';
import {ResPongService} from '../../service/res-pong.service';
import {catchError, finalize, of, tap} from 'rxjs';

@Component({
  selector: 'res-pong-email-preferences',
  imports: [ReactiveFormsModule, ToggleSwitchModule, ButtonModule, NgIf, ProgressBar, Message, Skeleton],
  templateUrl: './email-preferences.component.html',
  styleUrl: './email-preferences.component.scss'
})
export class EmailPreferencesComponent implements OnInit {

  private resPongService = inject(ResPongService);
  private fb = inject(FormBuilder);
  form!: FormGroup;

  @Input() message: any = null;
  @Input() loading: boolean = false;
  @Input()
  set user(value: any) {
    if (!value?.flags) return
    this._user = value;
    this.form = this.fb.group({
      send_email_on_reservation: [this._user?.flags.send_email_on_reservation],
      send_email_on_deletion: [this._user?.flags.send_email_on_deletion]
    });
  }
  _user: any = null;

  ngOnInit(): void {
    this.form = this.fb.group({
      send_email_on_reservation: [this._user?.flags.send_email_on_reservation],
      send_email_on_deletion: [this._user?.flags.send_email_on_deletion]
    });
  }

  submit(): void {
    if (!this.form.valid) {
      return;
    }
    this.loading = true;
    const {send_email_on_reservation, send_email_on_deletion} = this.form.value;
    this._user.flags.send_email_on_reservation = send_email_on_reservation;
    this._user.flags.send_email_on_deletion = send_email_on_deletion;
    this.form.disable();

    this.resPongService.saveEmailPreferences(send_email_on_reservation, send_email_on_deletion)
      .pipe(
        catchError((err) => {
          let msg = 'Si è verificato un errore. Riprova.';
          if (err?.error?.message) {
            msg = err.error.message;
          }
          return of({success: false, error: msg});
        }),
        finalize(() => {
          this.loading = false;
          this.form.enable();
        }),

        tap((value: any) => {
          if (value?.success) {
            this.message = {
              text: 'Preferenze aggiornate correttamente..',
              severity: 'success',
            };
            this.resPongService.updateMemoryUser(this._user);
          } else {
            this.message = {
              text: value?.error ?? 'Si è verificato un errore. Riprova.',
              severity: 'error',
            };
          }
        })
      ).subscribe()
  }
}
