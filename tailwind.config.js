/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './frontend/**/*.php',
    './common/modules/**/*.php',
    './frontend/web/js/**/*.js',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        primary: '#642754',
        'primary-dark': '#58264a',
        brand: '#642754',
        accent: '#eb652c',
        'accent-dark': '#d85a24',
        'background-light': '#f8f4f8',
      },
      fontFamily: {
        display: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/container-queries'),
  ],
};
