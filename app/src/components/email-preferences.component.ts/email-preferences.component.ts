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
  @Output()
  savePreferences = new EventEmitter<{
    send_email_on_reservation: boolean;
    send_email_on_deletion: boolean
  }>();

  @Input() message: any = null;
  @Input() loading: boolean = false;
  @Input() user: any = null;

  ngOnInit(): void {
    this.form = this.fb.group({
      send_email_on_reservation: [false],
      send_email_on_deletion: [false]
    });
  }

  submit(): void {
    if (!this.form.valid) {
      return;
    }
    this.loading = true;
    this.form.disable();
    const {send_email_on_reservation, send_email_on_deletion} = this.form.value;

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
              text: 'Password reimpostata correttamente.',
              severity: 'success',
            };
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
