import {Component, inject, OnInit} from '@angular/core';
import {ButtonDirective} from 'primeng/button';
import {FormBuilder, FormsModule, ReactiveFormsModule, Validators} from '@angular/forms';
import {InputText} from 'primeng/inputtext';
import {Message} from 'primeng/message';
import {NgIf} from '@angular/common';
import {ResPongService} from '../../service/res-pong.service';
import {catchError, finalize, of} from 'rxjs';
import {ProgressBar} from 'primeng/progressbar';
import {Router, RouterLink} from '@angular/router';
import {FloatLabel} from 'primeng/floatlabel';

@Component({
  selector: 'res-pong-user-password-recovery',
  imports: [
    ButtonDirective,
    FormsModule,
    InputText,
    Message,
    NgIf,
    ReactiveFormsModule,
    ProgressBar,
    RouterLink,
    FloatLabel
  ],
  templateUrl: './password-recovery.component.html'
})
export class PasswordRecoveryComponent implements OnInit {
  private resPongService = inject(ResPongService);
  private router = inject(Router);
  loading = false;

  form = inject(FormBuilder).group({
    email: ['', [Validators.required, Validators.email]]
  });

  error: string = '';
  successMessage: string = '';
  private updatePageUrl!: string;

  ngOnInit(): void {
    // Crea il path interno Angular
    const path = this.router.serializeUrl(
      this.router.createUrlTree(['/password-update'],)
    );

    // Per hash routing: dominio + '/#' + path senza slash iniziale
    this.updatePageUrl = window.location.origin + '/#' + path.replace(/^\//, '');
  }

  onSubmit() {
    if (this.form.invalid || this.loading) return;

    this.loading = true;
    this.error = '';
    this.successMessage = '';
    this.form.disable();

    const {email} = this.form.value;

    this.resPongService.recoverPassword(email as string, this.updatePageUrl)
      .pipe(
        catchError((err) => {
          const msg = err?.status === 400
            ? 'Email non valida.'
            : 'Si è verificato un errore. Riprova.';
          return of({success: false, error: msg});
        }),
        finalize(() => {
          this.loading = false;
          this.form.enable();
        })
      )
      .subscribe((value: any) => {
        if (value?.success) {
          this.successMessage = 'Richiesta Inviata. Se l’indirizzo è registrato, riceverai un’email con le istruzioni per il reset.';
          this.form.reset();
        } else {
          this.error = value?.error ?? 'Si è verificato un errore. Riprova.';
        }
      });
  }
}
