/**
 * NusaWisata - Notifications & Confirmation Dialogs
 * SweetAlert2 Clean Integration
 */

// Custom Swal instance with NusaWisata theme classes
function getNusaSwal(isDanger = false) {
  if (typeof Swal === 'undefined') {
    console.warn('SweetAlert2 library not loaded.');
    return null;
  }

  return Swal.mixin({
    customClass: {
      popup: 'nusa-swal-popup',
      title: 'nusa-swal-title',
      htmlContainer: 'nusa-swal-text',
      confirmButton: isDanger ? 'nusa-swal-confirm-danger-btn' : 'nusa-swal-confirm-btn',
      cancelButton: 'nusa-swal-cancel-btn',
      actions: 'nusa-swal-actions',
    },
    buttonsStyling: false,
    reverseButtons: true,
  });
}

export const NusaAlert = {
  /**
   * Show clean success alert
   */
  showSuccess(title = 'Berhasil', text = '', timer = 2500) {
    const swal = getNusaSwal();
    if (!swal) return Promise.resolve();

    return swal.fire({
      icon: 'success',
      title: title,
      text: text,
      showConfirmButton: !timer,
      confirmButtonText: 'Lanjut',
      timer: timer || undefined,
      timerProgressBar: !!timer,
    });
  },

  /**
   * Show error alert with human-friendly message
   */
  showError(title = 'Terjadi Kesalahan', text = '') {
    const swal = getNusaSwal(true);
    if (!swal) return Promise.resolve();

    return swal.fire({
      icon: 'error',
      title: title,
      text: text || 'Silakan periksa kembali data Anda atau coba beberapa saat lagi.',
      confirmButtonText: 'Tutup',
    });
  },

  /**
   * Show warning alert
   */
  showWarning(title = 'Perhatian', text = '') {
    const swal = getNusaSwal();
    if (!swal) return Promise.resolve();

    return swal.fire({
      icon: 'warning',
      title: title,
      text: text,
      confirmButtonText: 'Mengerti',
    });
  },

  /**
   * Show confirmation dialog for actions
   */
  showConfirm({
    title = 'Konfirmasi Aksi',
    text = 'Apakah Anda yakin ingin melanjutkan?',
    confirmText = 'Lanjutkan',
    cancelText = 'Batal',
    isDanger = false,
  } = {}) {
    const swal = getNusaSwal(isDanger);
    if (!swal) {
      const ok = window.confirm(title + '\n' + text);
      return Promise.resolve({ isConfirmed: ok });
    }

    return swal.fire({
      icon: isDanger ? 'warning' : 'question',
      title: title,
      text: text,
      showCancelButton: true,
      confirmButtonText: confirmText,
      cancelButtonText: cancelText,
      focusCancel: isDanger,
    });
  },
};

// Expose globally for inline scripts or other modules
window.NusaAlert = NusaAlert;

document.addEventListener('DOMContentLoaded', function () {
  // 1. Automatic Flash Messages Detection (from Laravel Session)
  const flashSuccess = document.getElementById('flashSuccessMessage');
  if (flashSuccess && flashSuccess.dataset.message) {
    NusaAlert.showSuccess('Berhasil', flashSuccess.dataset.message);
  }

  const flashError = document.getElementById('flashErrorMessage');
  if (flashError && flashError.dataset.message) {
    NusaAlert.showError('Perhatian', flashError.dataset.message);
  }

  const flashErrors = document.getElementById('flashValidationErrors');
  if (flashErrors && flashErrors.dataset.errors) {
    try {
      const errList = JSON.parse(flashErrors.dataset.errors);
      if (Array.isArray(errList) && errList.length > 0) {
        NusaAlert.showError('Validasi Gagal', errList.join('\n'));
      }
    } catch (e) {
      // Fallback
    }
  }

  // 2. Global Logout Confirmation Handler
  document.addEventListener('click', function (e) {
    const trigger = e.target.closest('.logout-trigger');
    if (trigger) {
      e.preventDefault();
      const form = trigger.closest('form');
      if (!form) return;

      NusaAlert.showConfirm({
        title: 'Keluar dari NusaWisata?',
        text: 'Anda harus masuk kembali untuk melihat rekomendasi personal.',
        confirmText: 'Keluar',
        cancelText: 'Batal',
        isDanger: true,
      }).then(function (result) {
        if (result.isConfirmed) {
          trigger.disabled = true;
          trigger.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Keluar...';
          form.submit();
        }
      });
    }
  });

  // 3. Declarative Action Confirmation for Forms (.confirm-action)
  document.addEventListener('submit', function (e) {
    const form = e.target;
    if (form && form.classList.contains('confirm-action')) {
      if (form.dataset.confirmed === 'true') {
        return; // Allow submission after confirmation
      }

      e.preventDefault();
      const title = form.dataset.confirmTitle || 'Apakah Anda yakin?';
      const text = form.dataset.confirmText || 'Aksi ini akan diproses oleh sistem.';
      const confirmText = form.dataset.confirmBtn || 'Ya, Lanjutkan';
      const isDanger = form.dataset.isDanger === 'true';

      NusaAlert.showConfirm({
        title: title,
        text: text,
        confirmText: confirmText,
        cancelText: 'Batal',
        isDanger: isDanger,
      }).then(function (result) {
        if (result.isConfirmed) {
          form.dataset.confirmed = 'true';
          const submitBtn = form.querySelector('button[type="submit"]');
          if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';
          }
          form.submit();
        }
      });
    }
  });

  // 4. Password Visibility Toggle (.password-toggle-btn)
  document.addEventListener('click', function (e) {
    const toggleBtn = e.target.closest('.password-toggle-btn');
    if (!toggleBtn) return;

    e.preventDefault();
    const targetId = toggleBtn.getAttribute('data-target');
    const input = document.getElementById(targetId);
    if (!input) return;

    const icon = toggleBtn.querySelector('i');
    if (input.type === 'password') {
      input.type = 'text';
      if (icon) {
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      }
    } else {
      input.type = 'password';
      if (icon) {
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    }
  });

  // 5. Submit Button Loading & Double-Submit Prevention
  document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!form || form.classList.contains('confirm-action')) return;
    if (form.classList.contains('no-submit-loading')) return;

    // Check HTML5 validation first
    if (form.checkValidity && !form.checkValidity()) return;

    const submitBtn = form.querySelector('button[type="submit"]:not([disabled])');
    if (!submitBtn) return;

    const loadingText = submitBtn.dataset.loadingText;
    if (loadingText) {
      setTimeout(function () {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin" style="margin-right: 6px;"></i> ' + loadingText;
      }, 0);
    }
  });
});
