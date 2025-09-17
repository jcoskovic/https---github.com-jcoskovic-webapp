import { Injectable } from '@angular/core';
import { Subject } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class ModalService {
  private loginModalSubject = new Subject<void>();
  private registerModalSubject = new Subject<void>();

  loginModal$ = this.loginModalSubject.asObservable();
  registerModal$ = this.registerModalSubject.asObservable();

  openLoginModal() {
    this.loginModalSubject.next();
  }

  openRegisterModal() {
    this.registerModalSubject.next();
  }
}
