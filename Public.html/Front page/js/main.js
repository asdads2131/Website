// ========================================
// PROMPEE SHOP - FRONTEND JAVASCRIPT
// Navigation, email validation, purchase flow
// ========================================

// API Configuration
const API_BASE = window.location.origin;

// Payment Configuration
const CONFIG = {
  walletAddress: '0x72001bcb1f4758f60f43d5f7a0ad5bea4313bc3f',
  promoCode: '',
  currency: 'EUR',
  checkoutUrl: '/Payment_path/index.html',
};

// ========================================
// NAVIGATION - SCROLL EFFECT
// ========================================
const navbar = document.getElementById('navbar');

function handleScroll() {
  if (window.scrollY > 50) {
    navbar.classList.add('scrolled');
  } else {
    navbar.classList.remove('scrolled');
  }
}

window.addEventListener('scroll', handleScroll);
handleScroll(); // Check on load

// ========================================
// SMOOTH SCROLL FOR ANCHOR LINKS
// ========================================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function(e) {
    e.preventDefault();
    const targetId = this.getAttribute('href');
    if (targetId === '#') return;

    const target = document.querySelector(targetId);
    if (target) {
      target.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }
  });
});



// ========================================
// ANIMATION ON SCROLL (Intersection Observer)
// ========================================
const observerOptions = {
  threshold: 0.1,
  rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.style.animationPlayState = 'running';
      observer.unobserve(entry.target);
    }
  });
}, observerOptions);

// Observe all animated elements
document.querySelectorAll('.product-card, .section-header').forEach(el => {
  observer.observe(el);
});

// ========================================
// BACK TO TOP LINK
// ========================================
const backToTop = document.querySelector('.footer-links a');
if (backToTop) {
  backToTop.addEventListener('click', (e) => {
    e.preventDefault();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
}
