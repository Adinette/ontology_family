/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        forest: {
          50: '#f0fdf4',
          100: '#dcfce7',
          500: '#22c55e',
          600: '#16a34a',
          700: '#15803d',
          800: '#1e563d', // Brand sidebar color
          900: '#143c2b',
          950: '#0b241a'
        },
        sage: {
          50: '#f4f9f6',
          100: '#e5f2ec',
          200: '#cbe4d8',
          300: '#a3cfbe',
          400: '#75b39e',
          500: '#4e9780'
        }
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
      }
    },
  },
  plugins: [],
}

