import {Component, inject, OnInit, ViewChild} from '@angular/core';
import {Stepper, StepperModule} from 'primeng/stepper';
import {ButtonModule} from 'primeng/button';
import {PasswordFormComponent} from '../../components/password/password-form.component';
import {UserDataComponent} from '../../components/user/user-data.component';
import {NgIf} from '@angular/common';
import {ResPongService} from '../../service/res-pong.service';
import {catchError, delay, tap} from 'rxjs';
import {ActivatedRoute, Router} from '@angular/router';

@Component({
  selector: 'res-pong-user-password-first-access',
  imports: [StepperModule, ButtonModule, PasswordFormComponent, PasswordFormComponent, UserDataComponent, NgIf],
  templateUrl: './password-first-access.component.html'
})
export class PasswordFirstAccessComponent implements OnInit {
  @ViewChild('stepper') stepper!: Stepper;
  @ViewChild(PasswordFormComponent) passwordForm!: PasswordFormComponent;
  private resPongService = inject(ResPongService);
  private activatedRoute = inject(ActivatedRoute);
  private router = inject(Router);
  userLoading: boolean = false;
  userError = '';
  user: any = {
    username: '',
    email: '',
    first_name: '',
    last_name: '',
    monogram: '...'
  };
  passwordFormButtonEnabled: boolean = false;

  ngOnInit(): void {
    this.userLoading = true;
    const token = this.activatedRoute.snapshot.queryParamMap.get('token') || '';
    this.resPongService.getUserDataByToken(token).pipe(
      catchError((error: any) => {
        console.error('Errore nel caricamento dati utente:', error);
        if (error?.error?.message) {
          this.userError = error.error.message;
        } else {
          this.userError = 'Si è verificato un errore durante il caricamento dei dati utente.';
        }
        this.userLoading = false;
        return [];
      }),
      tap((user: any) => {
        this.user = user;
        this.userLoading = false;
      })
    ).subscribe()
  }

  goToLogin() {
    this.router.navigate(['/login']);
  }

  submitPasswordFormAndActivateCallback(number: number) {
    this.passwordForm.submitForm()
      .pipe(
        delay(750),
        tap((value: any) => {
          if (value?.success) {
            this.stepper.updateValue(number);
          }
        }))
      .subscribe();
  }

  passwordFormStateChanged($event: boolean) {
    this.passwordFormButtonEnabled = $event;
  }
}
