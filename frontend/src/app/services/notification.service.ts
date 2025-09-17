import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root',
})
export class NotificationService {
  // Success notification using custom toast implementation
  showSuccess(message: string, duration = 3000) {
    this.showToast(message, 'success', duration);
  }

  // Error notification
  showError(message: string, duration = 5000) {
    this.showToast(message, 'error', duration);
  }

  // Info notification
  showInfo(message: string, duration = 3000) {
    this.showToast(message, 'info', duration);
  }

  // Warning notification
  showWarning(message: string, duration = 4000) {
    this.showToast(message, 'warning', duration);
  }

  private showToast(
    message: string,
    type: 'success' | 'error' | 'info' | 'warning',
    duration: number,
  ) {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;

    // Add styles
    Object.assign(toast.style, {
      position: 'fixed',
      top: '20px',
      right: '20px',
      padding: '16px 24px',
      borderRadius: '8px',
      color: 'white',
      fontWeight: '500',
      zIndex: '10000',
      minWidth: '300px',
      maxWidth: '500px',
      wordWrap: 'break-word',
      boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
      transform: 'translateX(400px)',
      transition: 'transform 0.3s ease-in-out',
      cursor: 'pointer',
      fontFamily: 'Arial, sans-serif',
      fontSize: '14px',
      lineHeight: '1.4',
    });

    // Set background color based on type
    const colors = {
      success: '#4caf50',
      error: '#f44336',
      info: '#2196f3',
      warning: '#ff9800',
    };
    toast.style.backgroundColor = colors[type];

    // Add icon based on type
    const icons = {
      success: '✓',
      error: '✗',
      info: 'ℹ',
      warning: '⚠',
    };
    toast.textContent = `${icons[type]} ${message}`;

    // Add to body
    document.body.appendChild(toast);

    // Animate in
    setTimeout(() => {
      toast.style.transform = 'translateX(0)';
    }, 100);

    // Auto remove
    const removeToast = () => {
      toast.style.transform = 'translateX(400px)';
      setTimeout(() => {
        if (toast.parentNode) {
          toast.parentNode.removeChild(toast);
        }
      }, 300);
    };

    // Click to dismiss
    toast.addEventListener('click', removeToast);

    // Auto dismiss after duration
    setTimeout(removeToast, duration);
  }
}
