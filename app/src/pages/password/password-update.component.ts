import {Component, inject, OnInit} from '@angular/core';
import {PasswordFormComponent} from '../../components/password/password-form.component';
import {ResPongService} from '../../service/res-pong.service';
import {ActivatedRoute} from '@angular/router';
import {catchError, tap} from 'rxjs';
import {NgIf} from '@angular/common';
import {Skeleton} from 'primeng/skeleton';
import {FormsModule} from '@angular/forms';

@Component({
  selector: 'res-pong-user-password-update',
  imports: [
    PasswordFormComponent,
    PasswordFormComponent,
    NgIf,
    Skeleton,
    FormsModule
  ],
  templateUrl: './password-update.component.html'
})
export class PasswordUpdateComponent implements OnInit {
  private resPongService = inject(ResPongService);
  private activatedRoute = inject(ActivatedRoute);

  username = ''
  loading = false

  ngOnInit(): void {
    this.loading = true;
    const token = this.activatedRoute.snapshot.queryParamMap.get('token') || '';
    this.resPongService.getUserDataByToken(token).pipe(
      catchError((error: any) => {
        console.error('Errore nel caricamento dati utente:', error);
        this.loading = false;
        return [];
      }),
      tap((user: any) => {
        this.username = user.username;
        this.loading = false;
      })
    ).subscribe()
  }


}
