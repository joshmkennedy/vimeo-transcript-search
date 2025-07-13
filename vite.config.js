import { dirname } from 'path';
import { writeFile } from 'fs';
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  plugins: [
    react(),
    tailwindcss(),
    {
      name: "assetFile",
      config(config) {
        //@ts-ignore
        const { host, port } = config.server.hmr
        const env = process.env.NODE_ENV ?? "development"
        const url = `http://${host}:${port}`

        const fileName = `${dirname(config.build.outDir)}/asset-info.json`
        const contents = JSON.stringify({ url, env })
        writeFile(fileName, contents, { encoding: "utf-8" }, (err) => {
          if (err) {
            console.log(err)
          }
        })
      }
    },

  ],
  build: {
	manifest: true,
    outDir: 'assets/build',
    rollupOptions: {
      input: {
        admin: 'assets/src/admin.ts',
      },
    },
  },
  server: {
    hmr: {
      port: 1234,
      host: "localhost",
    },
    cors: true,
    strictPort: true,
    port: 1234,
  }
});
