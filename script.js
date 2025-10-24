/**
 * ========================================
 * Mobile Menu Toggle Functionality 📱
 * ========================================
 */
const menuToggle = document.getElementById('menu-toggle');
const mobileMenu = document.getElementById('mobile-menu');

if (menuToggle && mobileMenu) {
  menuToggle.addEventListener('click', () => {
    mobileMenu.classList.toggle('hidden'); // Assumes 'hidden' class sets display: none
    const isExpanded = !mobileMenu.classList.contains('hidden');
    menuToggle.setAttribute('aria-expanded', isExpanded);
    const icon = menuToggle.querySelector('i');
    if (icon) {
      icon.className = isExpanded ? 'fas fa-times' : 'fas fa-bars';
    }
  });
} else {
  console.warn("Mobile menu toggle elements not found.");
}

/**
 * ========================================
 * Scroll Animation with Intersection Observer ✨
 * ========================================
 */
const animatedElements = document.querySelectorAll('.animate-on-scroll');

if ('IntersectionObserver' in window) {
  const observer = new IntersectionObserver((entries, observerInstance) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observerInstance.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.1 // Start animation when 10% is visible
  });

  animatedElements.forEach(el => observer.observe(el));
} else {
  console.warn("IntersectionObserver not supported. Scroll animations disabled.");
  animatedElements.forEach(el => el.classList.add('visible'));
}

/**
 * ========================================
 * Optional: Sticky Header Effect ⬆️
 * ========================================
 */
// const header = document.getElementById('main-header');
// let lastScrollY = window.scrollY;
// if (header) {
//   window.addEventListener('scroll', () => {
//     if (window.scrollY > 50) {
//       header.classList.add('py-2', 'shadow-md'); // Shrink padding and add shadow
//     } else {
//       header.classList.remove('py-2', 'shadow-md');
//     }
//     lastScrollY = window.scrollY; // Update last scroll position
//   }, { passive: true });
// }