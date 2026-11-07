# Font yang di-host sendiri

| Berkas | Keluarga | Bobot | Sumber |
|---|---|---|---|
| `inter-latin-var.woff2` | Inter (variabel, 400–600) | latin | Google Fonts (fonts.gstatic.com) |
| `inter-latin-ext-var.woff2` | Inter (variabel, 400–600) | latin-ext | Google Fonts |
| `fraunces-latin-600.woff2` | Fraunces 600 | latin | Google Fonts |
| `fraunces-latin-ext-600.woff2` | Fraunces 600 | latin-ext | Google Fonts |

Keduanya berlisensi **SIL Open Font License 1.1** (bebas dipakai, disematkan, dan didistribusikan;
lisensi lengkap tersedia di https://openfontlicense.org). Dilayani dari `public/fonts/` sehingga
aplikasi tidak bergantung pada CDN eksternal (tanpa npm/Vite — cukup `@font-face` di `public/css/app.css`).
