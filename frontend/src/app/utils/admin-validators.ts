import { AbstractControl, ValidationErrors, ValidatorFn } from '@angular/forms';

export class AdminValidators {
  static userRole(): ValidatorFn {
    return (control: AbstractControl): ValidationErrors | null => {
      const value = control.value;
      const validRoles = ['user', 'moderator', 'admin'];

      if (!value || validRoles.includes(value.toLowerCase())) {
        return null;
      }

      return { invalidRole: { value } };
    };
  }

  static abbreviationStatus(): ValidatorFn {
    return (control: AbstractControl): ValidationErrors | null => {
      const value = control.value;
      const validStatuses = ['pending', 'approved', 'rejected'];

      if (!value || validStatuses.includes(value.toLowerCase())) {
        return null;
      }

      return { invalidStatus: { value } };
    };
  }

  static positiveNumber(): ValidatorFn {
    return (control: AbstractControl): ValidationErrors | null => {
      const value = control.value;

      if (value === null || value === undefined || value === '') {
        return null;
      }

      const numValue = Number(value);
      if (isNaN(numValue) || numValue < 0) {
        return { positiveNumber: { value } };
      }

      return null;
    };
  }

  static emailList(): ValidatorFn {
    return (control: AbstractControl): ValidationErrors | null => {
      const value = control.value;

      if (!value || value.trim() === '') {
        return null;
      }

      const emails = value.split(',').map((email: string) => email.trim());
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      for (const email of emails) {
        if (email && !emailRegex.test(email)) {
          return { invalidEmailList: { invalidEmail: email } };
        }
      }

      return null;
    };
  }
}
