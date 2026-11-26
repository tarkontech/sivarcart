import { fileURLToPath } from 'node:url'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import { defineConfig } from 'vite'

/**
 * The build of the panel of SivarCart Core.
 *
 * The bundle travels with the package: the package serves it, so a merchant
 * that installs the package has the panel without Node and without a build
 * step. The result lives in "resources/dist" and it is versioned in the
 * repository, the same way the stylesheet of the panel was before.
 */
export default defineConfig({
  plugins: [react(), tailwindcss()],

  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./resources/js/admin', import.meta.url)),
    },
  },

  build: {
    outDir: 'resources/dist',
    emptyOutDir: true,

    /*
    | The panel is one bundle with its own stylesheet, so the root view of the
    | panel links the two of them by hand.
    */
    cssCodeSplit: false,

    rollupOptions: {
      input: {
        admin: fileURLToPath(new URL('./resources/js/admin/admin.tsx', import.meta.url)),
      },
      output: {
        entryFileNames: '[name].js',

        /*
        | The chunks carry a hash, so two pages with the same name do not
        | collide, and the panel serves them from the same route as the
        | bundle.
        */
        chunkFileNames: '[name]-[hash].js',

        /*
        | The stylesheet of the panel is named after the panel, so the root
        | view of the panel links it by hand.
        */
        assetFileNames: (asset) =>
            asset.names?.some((name) => name.endsWith('.css'))
                ? 'admin.css'
                : '[name][extname]',
      },
    },
  },
})
