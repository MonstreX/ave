import { defineConfig } from 'vite';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
  root: path.resolve(__dirname),
  base: './',
  publicDir: false,

  build: {
    outDir: 'dist/.' +
    'еб',
    emptyOutDir: true,
    cssCodeSplit: false,
    // Disable chunk size warnings since we want large bundles
    chunkSizeWarningLimit: Infinity,

    rollupOptions: {
      input: {
        app: path.resolve(__dirname, 'resources/js/app.js'),
        editors: path.resolve(__dirname, 'resources/js/editors.js'),
      },

      output: {
        entryFileNames: 'js/[name].js',
        // Prevent chunk creation entirely - return null for all modules
        manualChunks: () => null,

        // Assets organization:
        // - CSS: css/app.css
        // - Fonts: assets/fonts/ (including SVG fonts)
        // - Images: assets/images/ (via copy plugin)
        assetFileNames: ({ name }) => {
          const ext = path.extname(name || '').toLowerCase();
          if (ext === '.css') return 'css/app.css';
          // Don't create chunk files - they should be inlined
          if (ext === '.js') return null;
          // Font files processed by Vite
          if (['.woff', '.woff2', '.eot', '.ttf', '.otf'].includes(ext)) {
            return `assets/fonts/[name][extname]`;
          }
          // SVG from CSS (e.g., font SVG) goes to fonts
          if (ext === '.svg' && name && name.includes('voyager')) {
            return `assets/fonts/[name][extname]`;
          }
          // Other images to assets/
          return `assets/[name][extname]`;
        },
      },
    },
  },

  plugins: [
    // Use official Vite plugin for static asset copying
    // Copy fonts and images to dist/assets/ with structure preserved
    viteStaticCopy({
      targets: [
        {
          src: 'resources/assets/fonts',
          dest: 'assets',
        },
        {
          src: 'resources/assets/images',
          dest: 'assets',
        },
      ],
    }),
  ],
});
