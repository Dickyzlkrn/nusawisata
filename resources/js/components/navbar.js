/**
 * NusaWisata - Navbar Component
 * Mobile menu toggle, user dropdown, and scroll behavior
 */
document.addEventListener('DOMContentLoaded', function () {
  const navbar = document.querySelector('.navbar');
  const mobileToggle = document.getElementById('navMobileToggle');
  const mobileMenu = document.getElementById('navMobileMenu');
  const mobileClose = document.getElementById('navMobileClose');
  const userDropdownBtn = document.getElementById('userDropdownToggle') || document.getElementById('userDropdownBtn');
  const userDropdownMenu = document.getElementById('userDropdownMenu');

  // Sticky navbar on scroll
  if (navbar) {
    window.addEventListener('scroll', function () {
      if (window.scrollY > 20) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    });
  }

  // Mobile menu toggle
  if (mobileToggle && mobileMenu) {
    mobileToggle.addEventListener('click', function () {
      mobileMenu.classList.add('show');
      document.body.style.overflow = 'hidden';
    });
  }

  if (mobileClose && mobileMenu) {
    mobileClose.addEventListener('click', function () {
      mobileMenu.classList.remove('show');
      document.body.style.overflow = '';
    });
  }

  // Close mobile menu on link click
  if (mobileMenu) {
    mobileMenu.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        mobileMenu.classList.remove('show');
        document.body.style.overflow = '';
      });
    });
  }

  // User dropdown toggle
  if (userDropdownBtn && userDropdownMenu) {
    userDropdownBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      userDropdownMenu.classList.toggle('show');
    });

    document.addEventListener('click', function (e) {
      if (!userDropdownBtn.contains(e.target) && !userDropdownMenu.contains(e.target)) {
        userDropdownMenu.classList.remove('show');
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        userDropdownMenu.classList.remove('show');
        if (mobileMenu && mobileMenu.classList.contains('show')) {
          mobileMenu.classList.remove('show');
          document.body.style.overflow = '';
        }
      }
    });
  }
});
