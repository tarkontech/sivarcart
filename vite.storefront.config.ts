import { fileURLToPath } from 'node:url'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import { defineConfig } from 'vite'

/**
 * The build of the store of SivarCart.
 *
 * The bundle travels with the package: the package serves it, so a merchant
 * that installs the package has the store without Node and without a build
 * step. The result lives in "resources/dist/storefront" and it is versioned
 * in the repository, the same way the bundle of the panel is.
 *
 * The store has its own build and its own folder: the bundle of the panel
 * lives in "resources/dist/admin", so the build of one never deletes the
 * bundle of the other.
 */
export default defineConfig({
  plugins: [react(), tailwindcss()],

  resolve: {
    alias: {
      '@storefront': fileURLToPath(new URL('./resources/js/storefront', import.meta.url)),
    },
  },

  build: {
    outDir: 'resources/dist/storefront',
    emptyOutDir: true,

    /*
    | The store is one bundle with its own stylesheet, so the root view of the
    | store links the two of them by hand.
    */
    cssCodeSplit: false,

    rollupOptions: {
      input: {
        storefront: fileURLToPath(new URL('./resources/js/storefront/storefront.tsx', import.meta.url)),
      },
      output: {
        entryFileNames: '[name].js',

        /*
        | The chunks carry a hash, so two pages with the same name do not
        | collide, and the store serves them from the same route as the
        | bundle.
        */
        chunkFileNames: '[name]-[hash].js',

        /*
        | The stylesheet of the store is named after the store, so the root
        | view of the store links it by hand.
        */
        assetFileNames: (asset) =>
            asset.names?.some((name) => name.endsWith('.css'))
                ? 'storefront.css'
                : '[name][extname]',
      },
    },
  },
})
