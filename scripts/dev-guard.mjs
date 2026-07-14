// Guards `npm run dev` (Vite) when the Inertia frontend source is missing.
//
// This project's React source (`resources/js/app.tsx` + `Pages/`) is not on
// this machine — only the compiled `public/build/` assets are. If Vite starts
// without that source it writes `public/hot`, which makes Laravel try to load
// the missing entry from the dev server and the whole app 404s / goes blank.
//
// So: only allow Vite to run when the entry file actually exists. Otherwise
// print guidance and exit non-zero so the chained `&& vite` never runs.
// The moment you restore `resources/js/app.tsx`, `npm run dev` works normally.

import { existsSync, rmSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const entry = resolve(root, 'resources/js/app.tsx');

if (existsSync(entry)) {
  process.exit(0); // source present → let Vite start
}

// Safety: make sure a stale hot file isn't left lying around.
const hot = resolve(root, 'public/hot');
if (existsSync(hot)) rmSync(hot);

console.error(`
⛔  Vite dev server is disabled on this machine.

    The frontend source is missing:  resources/js/app.tsx (and Pages/) are not here.
    Only the compiled production build (public/build/) is present.

    ✅  The app already runs without Vite:
            php artisan serve --host=127.0.0.1 --port=8000   →  http://127.0.0.1:8000
        Admin catalog (products + variants):  http://127.0.0.1:8000/manage/products

    To re-enable Vite, restore the resources/js/ source, then run npm run dev again.
`);

process.exit(1);
