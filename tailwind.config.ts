import type { Config } from 'tailwindcss'

export default {
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.js',
    './app/Livewire/**/*.php',
  ],
  theme: {
    extend: {
      animation: {
        pulse: 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
      },
    },
  },
  plugins: [],
} satisfies Config
