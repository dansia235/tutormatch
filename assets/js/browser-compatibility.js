/**
 * Browser compatibility module
 * Provides polyfills and compatibility fixes for various browsers
 */

/**
 * Feature detection for required browser features
 * @returns {Object} Object containing feature detection results
 */
export function detectFeatures() {
  return {
    // JavaScript features
    arrow: (() => {
      try {
        eval('() => {}');
        return true;
      } catch (e) {
        return false;
      }
    })(),
    
    // ES6 features
    promises: typeof Promise !== 'undefined',
    fetch: typeof fetch !== 'undefined',
    customElements: 'customElements' in window,
    templateElements: 'content' in document.createElement('template'),
    
    // DOM APIs
    intersectionObserver: 'IntersectionObserver' in window,
    mutationObserver: 'MutationObserver' in window,
    resizeObserver: 'ResizeObserver' in window,
    
    // Web APIs
    localStorage: (() => {
      try {
        return 'localStorage' in window && window['localStorage'] !== null;
      } catch (e) {
        return false;
      }
    })(),
    sessionStorage: (() => {
      try {
        return 'sessionStorage' in window && window['sessionStorage'] !== null;
      } catch (e) {
        return false;
      }
    })(),
    
    // CSS features
    grid: (() => {
      const el = document.createElement('div');
      return el.style.grid !== undefined || 
             el.style.gridTemplate !== undefined || 
             el.style.gridTemplateColumns !== undefined;
    })(),
    flexbox: (() => {
      const el = document.createElement('div');
      return el.style.flexDirection !== undefined || 
             el.style.webkitFlexDirection !== undefined;
    })()
  };
}

/**
 * Load necessary polyfills based on feature detection
 * @param {Object} features - The result from detectFeatures()
 * @returns {Promise} Promise resolving when all polyfills are loaded
 */
export function loadPolyfills(features) {
  const polyfills = [];
  
  // Promises polyfill
  if (!features.promises) {
    polyfills.push(loadScript('https://cdn.jsdelivr.net/npm/promise-polyfill@8/dist/polyfill.min.js'));
  }
  
  // Fetch polyfill
  if (!features.fetch) {
    polyfills.push(loadScript('https://cdn.jsdelivr.net/npm/whatwg-fetch@3.6.2/dist/fetch.umd.min.js'));
  }
  
  // IntersectionObserver polyfill
  if (!features.intersectionObserver) {
    polyfills.push(loadScript('https://cdn.jsdelivr.net/npm/intersection-observer@0.12.2/intersection-observer.js'));
  }
  
  // ResizeObserver polyfill
  if (!features.resizeObserver) {
    polyfills.push(loadScript('https://cdn.jsdelivr.net/npm/resize-observer-polyfill@1.5.1/dist/ResizeObserver.min.js'));
  }
  
  // Return a promise that resolves when all polyfills are loaded
  return Promise.all(polyfills);
}

/**
 * Load a script dynamically
 * @param {String} src - The script URL
 * @returns {Promise} Promise that resolves when the script is loaded
 */
function loadScript(src) {
  return new Promise((resolve, reject) => {
    const script = document.createElement('script');
    script.src = src;
    script.onload = resolve;
    script.onerror = reject;
    document.head.appendChild(script);
  });
}

/**
 * Add CSS fixes for older browsers
 */
export function addCSSFixes() {
  const features = detectFeatures();
  
  // Add classes to html element for CSS targeting
  const html = document.documentElement;
  
  Object.entries(features).forEach(([feature, supported]) => {
    if (supported) {
      html.classList.add(`has-${feature}`);
    } else {
      html.classList.add(`no-${feature}`);
    }
  });
  
  // Add specific CSS fixes for flexbox in older browsers
  if (!features.flexbox) {
    const style = document.createElement('style');
    style.textContent = `
      /* Flexbox fallbacks */
      .flex { display: block; }
      .flex > * { display: inline-block; vertical-align: top; }
      .flex-col > * { display: block; }
    `;
    document.head.appendChild(style);
  }
  
  // Add specific CSS fixes for grid in older browsers
  if (!features.grid) {
    const style = document.createElement('style');
    style.textContent = `
      /* Grid fallbacks */
      .grid { display: block; }
      .md\\:grid-cols-2 > * { display: inline-block; width: calc(50% - 16px); margin: 8px; vertical-align: top; }
      .md\\:grid-cols-3 > * { display: inline-block; width: calc(33.333% - 16px); margin: 8px; vertical-align: top; }
      .md\\:grid-cols-4 > * { display: inline-block; width: calc(25% - 16px); margin: 8px; vertical-align: top; }
      @media (max-width: 768px) {
        .md\\:grid-cols-2 > *, .md\\:grid-cols-3 > *, .md\\:grid-cols-4 > * { 
          width: 100%; 
          margin: 8px 0;
        }
      }
    `;
    document.head.appendChild(style);
  }
}

/**
 * Initialize browser compatibility fixes
 */
export function initCompatibility() {
  const features = detectFeatures();
  
  // Add browser detection to html element
  const html = document.documentElement;
  const ua = navigator.userAgent;
  
  if (/MSIE|Trident/.test(ua)) {
    html.classList.add('ie');
  } else if (/Edge/.test(ua)) {
    html.classList.add('edge');
  } else if (/Chrome/.test(ua)) {
    html.classList.add('chrome');
  } else if (/Firefox/.test(ua)) {
    html.classList.add('firefox');
  } else if (/Safari/.test(ua)) {
    html.classList.add('safari');
  }
  
  // Load polyfills and apply CSS fixes
  loadPolyfills(features)
    .then(() => {
      addCSSFixes();
      
      // Dispatch event when compatibility fixes are complete
      const event = new CustomEvent('compatibility:complete', {
        detail: { features }
      });
      document.dispatchEvent(event);
    })
    .catch(error => {
      console.error('Error loading polyfills:', error);
    });
}