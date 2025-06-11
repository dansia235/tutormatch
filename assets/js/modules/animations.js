/**
 * Animations module
 */

// Fade in elements on scroll
const animateOnScroll = () => {
  const elements = document.querySelectorAll('.fade-in');
  
  elements.forEach(element => {
    const elementPosition = element.getBoundingClientRect().top;
    const windowHeight = window.innerHeight;
    
    if (elementPosition < windowHeight - 100) {
      element.classList.add('show');
    }
  });
};

// Initialize animations
document.addEventListener('DOMContentLoaded', () => {
  // Run animations on page load
  animateOnScroll();
  
  // Run animations on scroll
  window.addEventListener('scroll', animateOnScroll);
});
