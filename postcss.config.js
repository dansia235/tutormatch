module.exports = {
  plugins: {
    'tailwindcss': {},
    'autoprefixer': {},
    ...(process.env.NODE_ENV === 'production' ? {
      'cssnano': {
        preset: ['default', {
          discardComments: {
            removeAll: true
          },
          mergeLonghand: true,
          colormin: true,
          reduceIdents: false, // Avoid breaking @keyframe animations
          zindex: false // Avoid changing z-index values
        }]
      }
    } : {})
  }
};