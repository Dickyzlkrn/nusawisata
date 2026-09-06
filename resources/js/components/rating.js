/**
 * NusaWisata - Rating Component
 * Star rating interactive widget
 */
document.addEventListener('DOMContentLoaded', function () {
  // Rating form submission
  const ratingForm = document.getElementById('ratingForm');
  if (ratingForm) {
    ratingForm.addEventListener('submit', function (e) {
      const selectedRating = ratingForm.querySelector('input[name="rating"]:checked');
      if (!selectedRating) {
        e.preventDefault();
        if (window.NusaAlert) {
          window.NusaAlert.showWarning('Perhatian', 'Silakan pilih rating bintang terlebih dahulu.');
        } else {
          alert('Silakan pilih rating bintang terlebih dahulu.');
        }
      }
    });
  }
});
