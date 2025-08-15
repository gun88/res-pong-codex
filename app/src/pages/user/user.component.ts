import {Component, inject, OnInit} from '@angular/core';
import {ReactiveFormsModule} from '@angular/forms';
import {Router, RouterModule} from '@angular/router';
import {ResPongService} from '../../service/res-pong.service';
import {catchError, switchMap, tap} from 'rxjs';
import {ButtonModule} from 'primeng/button';
import {InputTextModule} from 'primeng/inputtext';
import {PasswordModule} from 'primeng/password';
import {CheckboxModule} from 'primeng/checkbox';
import {Divider} from 'primeng/divider';
import {UserDataComponent} from "../../components/user/user-data.component";
import {PasswordFormComponent} from "../../components/password/password-form.component";
import {BlockUI} from 'primeng/blockui';
import {ProgressSpinner} from 'primeng/progressspinner';

@Component({
    selector: 'res-pong-user-user',
  imports: [
    ReactiveFormsModule,
    ButtonModule,
    ReactiveFormsModule,
    InputTextModule,
    PasswordModule,
    CheckboxModule,
    RouterModule,
    Divider,
    PasswordFormComponent,
    UserDataComponent,
    UserDataComponent,
    PasswordFormComponent,
    BlockUI,
    ProgressSpinner
  ],
    templateUrl: './user.component.html',
    styleUrl: './user.component.scss'
})
export class UserComponent implements OnInit {
    private resPongService = inject(ResPongService);
    private router = inject(Router);
    loading = false;
    logoutLoading = false;
    error = '';
    user: any = {
        username: '',
        email: '',
        first_name: '',
        last_name: '',
        monogram: '...'
    };


    ngOnInit(): void {
        this.loading = true;
        this.resPongService.user$.pipe(
            tap((user: any) => this.user = user),
            tap(() => this.loading = false),
            switchMap(() => this.resPongService.getUserData()),
            tap((user: any) => {
                this.user = user;
            }),
            catchError((error: any) => {
                console.error('Errore nel caricamento dati utente:', error);
                this.error = 'Si è verificato un errore durante il caricamento dei dati utente.';
                this.loading = false;
                return [];
            })
        ).subscribe()

    }

    logout() {
        this.logoutLoading = true;
        this.resPongService.logOut().subscribe(
            () => {
                this.router.navigate(['/login']);
                this.logoutLoading = false;
            },
            error => {
                console.error(error);
                alert("Errore durante la logout. Riprova!")
                this.logoutLoading = false;
            }
        );
    }
}
