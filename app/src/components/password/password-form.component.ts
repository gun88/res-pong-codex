import {Component, EventEmitter, inject, Input, OnDestroy, OnInit, Output} from '@angular/core';
import {ButtonDirective} from "primeng/button";
import {AbstractControl, FormBuilder, FormsModule, ReactiveFormsModule, Validators} from "@angular/forms";
import {Message} from "primeng/message";
import {NgIf} from "@angular/common";
import {ProgressBar} from "primeng/progressbar";
import {ResPongService} from '../../service/res-pong.service';
import {ActivatedRoute, RouterLink} from '@angular/router';
import {catchError, finalize, of, Subscription, tap} from 'rxjs';
import {Password} from 'primeng/password';
import {FloatLabel} from 'primeng/floatlabel';
import {Divider} from 'primeng/divider';
import {InputText} from 'primeng/inputtext';

@Component({
  selector: 'res-pong-user-password-form',
  imports: [
    ButtonDirective,
    FormsModule,
    Message,
    NgIf,
    ProgressBar,
    ReactiveFormsModule,
    RouterLink,
    Password,
    FloatLabel,
    Divider,
    InputText
  ],
  templateUrl: './password-form.component.html'
})
export class PasswordFormComponent implements OnInit, OnDestroy {
  private resPongService = inject(ResPongService);
  private activatedRoute = inject(ActivatedRoute);
  private formBuilder = inject(FormBuilder);
  private subs = new Subscription();

  loading = false;

  form: any;

  error: string = '';
  token: string = '';
  successMessage: string = '';
  @Input() showLoginLink = false;
  @Input() title = '';
  @Input() username = '';
  @Input() showSubmitButton = true;
  @Output() stateChanged = new EventEmitter<boolean>();

  ngOnInit(): void {
    this.form = this.formBuilder.group(
      {
        username: [{value: this.username, disabled: true}, [Validators.required]],
        newPassword: ['', [Validators.required, Validators.minLength(6)]],
        confirmPassword: ['', [Validators.required, Validators.minLength(6)]]
      },
      {validators: this.matchPasswords('newPassword', 'confirmPassword')}
    );

    this.subs.add(
      this.form.statusChanges.subscribe(() => this.emitState())
    );
    this.emitState();
    this.token = this.activatedRoute.snapshot.queryParamMap.get('token') || '';
  }

  ngOnDestroy(): void {
    this.subs.unsubscribe();
  }

  private emitState() {
    const enabled = !this.loading && this.form.valid;
    this.stateChanged.emit(enabled);
  }

  private matchPasswords(a: string, b: string) {
    return (group: AbstractControl) => {
      const first = group.get(a)?.value;
      const second = group.get(b)?.value;
      if (!first || !second) {
        return null;
      }
      return first === second ? null : {passwordsMismatch: true};
    };
  }

  onSubmit() {
    this.submitForm().subscribe();
  }

  public submitForm() {
    let observable: any;
    if (this.form.invalid) {
      observable = of({success: false, error: 'Dati non validi'})
    } else {

      this.loading = true;
      this.error = '';
      this.successMessage = '';
      this.form.disable();
      this.emitState();
      const {newPassword, confirmPassword} = this.form.value;

      observable = this.resPongService.updatePassword(newPassword as string, confirmPassword as string, this.token)
        .pipe(
          catchError((err) => {
            let msg = err?.status === 400 ? 'Password non valida.' : 'Si è verificato un errore. Riprova.';
            if (err?.error?.message) {
              msg = err.error.message;
            }
            return of({success: false, error: msg});
          }),
          finalize(() => {
            if (this.username) {
              this.form.get('username')?.setValue(this.username);
            }
            this.loading = false;
            this.form.enable();
            this.emitState();
          })
        )
    }

    return observable.pipe(
      tap((value: any) => {
        if (value?.success) {
          this.successMessage = 'Password reimpostata correttamente.';
          this.form.reset();
        } else {
          this.error = value?.error ?? 'Si è verificato un errore. Riprova.';
        }
      })
    )
  }
}
