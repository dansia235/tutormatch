/**
 * Performance optimizations for the tutoring application
 * This module includes various optimizations for better frontend performance
 */

/**
 * Debounce function to limit the rate at which a function can fire
 * 
 * @param {Function} func - The function to debounce
 * @param {Number} wait - The time to wait in milliseconds
 * @param {Boolean} immediate - Whether to trigger the function immediately
 * @returns {Function} - The debounced function
 */
export function debounce(func, wait = 300, immediate = false) {
  let timeout;
  return function(...args) {
    const context = this;
    const later = function() {
      timeout = null;
      if (!immediate) func.apply(context, args);
    };
    const callNow = immediate && !timeout;
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
    if (callNow) func.apply(context, args);
  };
}

/**
 * Throttle function to ensure a function doesn't execute more than once per specified time
 * 
 * @param {Function} func - The function to throttle
 * @param {Number} limit - The time limit in milliseconds
 * @returns {Function} - The throttled function
 */
export function throttle(func, limit = 300) {
  let inThrottle;
  return function(...args) {
    const context = this;
    if (!inThrottle) {
      func.apply(context, args);
      inThrottle = true;
      setTimeout(() => inThrottle = false, limit);
    }
  };
}

/**
 * Lazy load images when they come into the viewport
 */
export function lazyLoadImages() {
  // Use Intersection Observer API for better performance
  if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          const src = img.getAttribute('data-src');
          
          if (src) {
            img.setAttribute('src', src);
            img.removeAttribute('data-src');
            img.classList.remove('lazy-load');
            img.classList.add('lazy-loaded');
          }
          
          // Stop observing once loaded
          observer.unobserve(img);
        }
      });
    });
    
    // Observe all images with data-src attribute
    document.querySelectorAll('img[data-src]').forEach(img => {
      imageObserver.observe(img);
    });
  } else {
    // Fallback for older browsers
    // This is less performant but provides support for all browsers
    const lazyloadHandler = function() {
      let lazyloadThrottleTimeout;
      
      if (lazyloadThrottleTimeout) {
        clearTimeout(lazyloadThrottleTimeout);
      }
      
      lazyloadThrottleTimeout = setTimeout(() => {
        const scrollTop = window.pageYOffset;
        
        document.querySelectorAll('img[data-src]').forEach(img => {
          if (img.offsetTop < (window.innerHeight + scrollTop)) {
            const src = img.getAttribute('data-src');
            
            if (src) {
              img.setAttribute('src', src);
              img.removeAttribute('data-src');
              img.classList.remove('lazy-load');
              img.classList.add('lazy-loaded');
            }
          }
        });
        
        if (document.querySelectorAll('img[data-src]').length === 0) {
          document.removeEventListener('scroll', lazyloadHandler);
          window.removeEventListener('resize', lazyloadHandler);
          window.removeEventListener('orientationChange', lazyloadHandler);
        }
      }, 20);
    };
    
    document.addEventListener('scroll', lazyloadHandler);
    window.addEventListener('resize', lazyloadHandler);
    window.addEventListener('orientationChange', lazyloadHandler);
  }
}

/**
 * Optimize chart rendering
 */
export function optimizeCharts() {
  // Use requestAnimationFrame for smoother chart updates
  window.requestAnimationFrame = window.requestAnimationFrame || 
                               window.mozRequestAnimationFrame ||
                               window.webkitRequestAnimationFrame || 
                               window.msRequestAnimationFrame;
  
  // Throttle chart resize events
  const chartResizeHandler = throttle(function() {
    // Update all chart instances
    if (window.Chart && window.Chart.instances) {
      Object.values(window.Chart.instances).forEach(chart => {
        chart.resize();
      });
    }
  }, 100);
  
  // Listen for resize events
  window.addEventListener('resize', chartResizeHandler);
}

/**
 * Initialize all performance optimizations
 */
export function initPerformanceOptimizations() {
  // Lazy load images
  lazyLoadImages();
  
  // Optimize charts
  optimizeCharts();
  
  // Add passive event listeners where appropriate
  // This improves scrolling performance
  const passiveEvents = ['touchstart', 'touchmove', 'wheel', 'mousewheel'];
  const supportsPassive = false;
  
  try {
    window.addEventListener('test', null, Object.defineProperty({}, 'passive', {
      get: function () { 
        supportsPassive = true; 
        return true;
      } 
    }));
  } catch(e) {}
  
  if (supportsPassive) {
    const passiveOption = { passive: true };
    passiveEvents.forEach(eventName => {
      window.addEventListener(eventName, e => {}, passiveOption);
    });
  }
  
  // Prefetch important assets/pages
  if ('requestIdleCallback' in window) {
    requestIdleCallback(() => {
      const prefetchLinks = document.querySelectorAll('a[data-prefetch]');
      prefetchLinks.forEach(link => {
        const url = link.getAttribute('href');
        if (url) {
          const prefetchLink = document.createElement('link');
          prefetchLink.rel = 'prefetch';
          prefetchLink.href = url;
          document.head.appendChild(prefetchLink);
        }
      });
    });
  }
}

// Initialize optimizations when the DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initPerformanceOptimizations);
} else {
  initPerformanceOptimizations();
}