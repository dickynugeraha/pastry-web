# Runbook Integrasi Pembayaran Midtrans dan Manual

> Status: **implementasi lokal selesai; kredensial Sandbox valid; uji end-to-end menunggu URL webhook HTTPS publik**  
> Project: Bunkue Pastry  
> Tujuan: panduan implementasi dan checklist operasional pembayaran.  
> Perintah pemicu di kemudian hari: **“Jalankan `MIDTRANS_PAYMENT_INTEGRATION_TASKS.md`.”**

## 1. Ruang Lingkup

Sistem pembayaran akan menyediakan dua kanal:

1. **Midtrans** — pembayaran diproses dan dikonfirmasi melalui Midtrans.
2. **Pembayaran manual** — pelanggan memilih tujuan pembayaran BCA, DANA, atau GOPAY, kemudian mengunggah bukti bayar untuk diperiksa admin.

Status keuangan, proses pesanan, dan pengiriman harus tetap dipisahkan:

- `payment_status`: `unpaid`, `paid`, `failed`, atau `refunded`.
- `status`: status proses/fulfillment pesanan yang sudah digunakan aplikasi.
- `shipping_status`: status pengiriman yang sudah digunakan aplikasi.

Pembayaran berstatus `paid` **tidak otomatis** mengubah pesanan menjadi diproses. Admin tetap mengendalikan proses pesanan, tetapi pesanan tidak boleh masuk ke tahap pemrosesan/selesai sebelum pembayaran sah.

## 2. Aturan Eksekusi untuk Agent

Saat dokumen ini diminta untuk dijalankan:

- [x] Baca `AGENTS.md` dan seluruh instruksi project yang aktif.
- [x] Jika `.ai/rules` tersedia, baca `.ai/rules/index.md`, semua rule yang sesuai dengan file dalam ruang lingkup, lalu cari keyword pembayaran, order, upload, security, test, dan route di direktori tersebut.
- [x] Aktifkan skill Laravel dan testing yang relevan sebelum mengubah kode.
- [x] Periksa working tree dan jangan menimpa perubahan milik pengguna yang tidak terkait.
- [x] Konfirmasi versi PHP/Laravel/dependency yang terpasang sebelum memakai API tertentu.
- [x] Periksa kembali dokumentasi resmi dan versi SDK Midtrans yang tersedia pada saat eksekusi.
- [x] Gunakan Laravel Boost untuk inspeksi schema, dokumentasi Laravel, log, dan URL project apabila tersedia.
- [x] Kerjakan tahapan secara berurutan dan perbarui checkbox dokumen ini setelah setiap tahap lolos verifikasi.
- [x] Jangan pernah menampilkan nilai key dari `.env` di terminal, log, test output, commit, atau jawaban akhir.
- [x] Jangan menjalankan deployment, migrasi production, atau transaksi uang sungguhan tanpa perintah eksplisit terpisah dari pengguna.

Permintaan untuk menjalankan dokumen ini memberikan izin untuk perubahan source code, migration, test, konfigurasi, dan penambahan dependency Midtrans yang tercantum di sini. Izin tersebut **tidak** mencakup deployment production atau transaksi live.

Jika key Midtrans belum tersedia, implementasi dan automated test tetap dapat dikerjakan dengan mock/fake. Hanya pengujian manual sandbox yang ditunda.

## 3. Konfigurasi Environment

### Lokasi

- Nilai rahasia dan nilai environment sebenarnya ditempatkan di **file `.env` pada root project**.
- Daftar nama variabel dan contoh non-rahasia ditempatkan di **`.env.example` pada root project**.
- Aplikasi membaca variabel tersebut melalui file konfigurasi, disarankan `config/payments.php`.
- Jangan memanggil `env()` langsung dari controller, service, model, request, atau Blade.
- Di production, isi key melalui environment/secret manager milik hosting, bukan dengan menyimpan `.env` di repository.

### Template `.env`

Server Key dan Client Key sengaja dibiarkan kosong:

```dotenv
# Payment channel
MIDTRANS_ENABLED=false

# false = Sandbox/Development, true = Production
MIDTRANS_IS_PRODUCTION=false

# Isi di file .env lokal atau secret manager production. Jangan di-commit.
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=

MIDTRANS_PAYMENT_EXPIRY_MINUTES=60

# Manual payment destination (dummy awal)
MANUAL_PAYMENT_BCA_ACCOUNT=12345
MANUAL_PAYMENT_DANA_NUMBER=12345
MANUAL_PAYMENT_GOPAY_NUMBER=12345
MANUAL_PAYMENT_ACCOUNT_NAME="Bunkue Pastry"

# Bukti bayar disimpan secara privat
PAYMENT_PROOF_DISK=local
PAYMENT_PROOF_MAX_KB=5120
```

### Mode Development/Sandbox

```dotenv
MIDTRANS_ENABLED=true
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
```

- Gunakan kredensial **Sandbox** dari dashboard Midtrans.
- Snap menggunakan endpoint/script sandbox.
- Webhook lokal memerlukan URL HTTPS publik sementara (tunnel) menuju endpoint aplikasi.

### Mode Production

```dotenv
MIDTRANS_ENABLED=true
MIDTRANS_IS_PRODUCTION=true
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
```

- Gunakan kredensial **Production**, bukan key Sandbox.
- Simpan key pada secret manager/environment hosting.
- Notification URL harus HTTPS publik yang stabil.
- Pergantian ke production dilakukan melalui konfigurasi, bukan perubahan source code.

## 4. Keputusan Desain

### 4.1 Kanal dan metode pembayaran

Tambahkan enum atau value object sesuai konvensi project:

- `PaymentChannel::Midtrans` → `midtrans`
- `PaymentChannel::Manual` → `manual`
- `ManualPaymentMethod::Bca` → `bca`
- `ManualPaymentMethod::Dana` → `dana`
- `ManualPaymentMethod::Gopay` → `gopay`

Checkout wajib mengirim `payment_channel`. `manual_payment_method` wajib hanya ketika kanal `manual`, dan harus kosong ketika kanal `midtrans`.

### 4.2 Rekening/nomor pembayaran manual

Pada checkout dan halaman detail pesanan manual, tampilkan:

| Metode | Label tujuan | Nomor dummy awal |
|---|---|---:|
| BCA | Nomor rekening BCA | `12345` |
| DANA | Nomor DANA | `12345` |
| GOPAY | Nomor GOPAY | `12345` |

Nilai tersebut berasal dari konfigurasi environment, bukan hard-code di Blade. Saat order dibuat, simpan snapshot tujuan pembayaran pada order agar riwayat lama tidak berubah ketika nomor tujuan di konfigurasi diganti.

Contoh snapshot `manual_payment_details`:

```json
{
  "provider": "BCA",
  "destination_number": "12345",
  "account_name": "Bunkue Pastry"
}
```

### 4.3 Otoritas perubahan status pembayaran

| Sumber order | Pihak yang boleh menetapkan `paid` | Aturan |
|---|---|---|
| Manual | Admin | Melalui penerimaan bukti bayar atau override manual yang diaudit |
| Midtrans | Webhook/sinkronisasi Midtrans | Admin tidak boleh mengubah status secara manual |

Untuk order manual:

- Admin dapat mengubah `unpaid` menjadi `paid`.
- Admin dapat mengembalikan `paid` menjadi `unpaid` hanya jika order dan pengiriman masih pada tahap awal/pending.
- Override langsung tanpa menerima bukti bayar harus meminta catatan/alasan.
- Aksi admin harus dicatat pada audit log.

Untuk order Midtrans:

- Tombol/form edit status pembayaran manual tidak ditampilkan.
- Endpoint admin menolak perubahan manual sekalipun dipanggil langsung.
- Status hanya berubah melalui webhook tervalidasi atau sinkronisasi resmi ke Midtrans.

### 4.4 Bukti pembayaran

Bukti pembayaran harus:

- hanya dapat diunggah oleh pemilik order manual;
- hanya dapat diunggah saat order belum dibayar dan belum dibatalkan/diselesaikan;
- menerima `jpg`, `jpeg`, `png`, atau `pdf`;
- dibatasi maksimal 5 MB secara default melalui `PAYMENT_PROOF_MAX_KB`;
- divalidasi berdasarkan file/MIME, bukan hanya ekstensi;
- disimpan memakai nama file yang dibuat server, bukan nama asli pengguna;
- disimpan pada disk privat `local`, bukan `public`;
- diakses hanya melalui controller yang melakukan authorization.

Admin dapat melihat/unduh bukti. Pelanggan hanya dapat melihat bukti milik order-nya sendiri. PDF sebaiknya dikirim sebagai attachment; response file harus memakai MIME yang benar dan header `X-Content-Type-Options: nosniff`.

## 5. Perubahan Data yang Direncanakan

Sebelum membuat migration, periksa schema aktual dan data duplikat. Gunakan nama kolom/enum yang selaras dengan konvensi project.

### 5.1 Tabel `orders`

Pertahankan kolom Midtrans yang sudah ada. Tambahkan:

- `payment_channel` nullable string/index (`midtrans` atau `manual` selama masa transisi).
- `manual_payment_method` nullable string (`bca`, `dana`, `gopay`).
- `manual_payment_details` nullable JSON sebagai snapshot tujuan pembayaran.
- `payment_due_at` nullable timestamp.

Backfill data lama secara eksplisit berdasarkan bukti yang tersedia. Jangan menebak kanal pembayaran jika data lama ambigu; dokumentasikan keputusan migrasinya.

### 5.2 Tabel `payments`

Gunakan tabel ini sebagai riwayat transaksi/attempt, bukan hanya satu record final:

- tambahkan `provider` (`midtrans` atau `manual`);
- tambahkan `confirmed_by_user_id` nullable FK;
- tambahkan `confirmed_at` nullable timestamp;
- tambahkan `note` nullable text;
- pertahankan detail transaksi Midtrans yang sudah ada;
- evaluasi unique index untuk `transaction_id` setelah memastikan data existing tidak duplikat.

Pada model `Order`, sediakan relasi `payments()` (`HasMany`) dan relasi transaksi terbaru menggunakan pola latest-of-many. Jaga kompatibilitas relasi existing bila masih dipakai.

### 5.3 Tabel baru `payment_proofs`

Kolom yang direncanakan:

- `id`
- `order_id` FK, cascade on delete
- `uploaded_by_user_id` FK
- `manual_payment_method`
- `file_path`
- `original_name` nullable
- `mime_type`
- `size_bytes`
- `review_status`: `pending`, `accepted`, `rejected`, `superseded`
- `reviewed_by_user_id` nullable FK, null on delete
- `reviewed_at` nullable timestamp
- `rejection_reason` nullable text
- timestamps

Gunakan relasi one-to-many agar pelanggan dapat mengunggah ulang setelah bukti ditolak dan riwayat review tetap tersimpan.

### 5.4 Tabel baru `payment_status_logs`

Audit log append-only dengan kolom:

- `order_id`
- `from_status`
- `to_status`
- `source`: `admin_manual`, `manual_proof`, `midtrans_webhook`, `midtrans_sync`, atau `system`
- `changed_by_user_id` nullable FK
- `external_transaction_id` nullable
- `note` nullable text
- `context` nullable JSON
- `created_at`

Audit log tidak boleh menjadi sumber status aktif; sumber status aktif tetap `orders.payment_status`.

## 6. Endpoint yang Direncanakan

Sesuaikan middleware, controller style, route-model binding, policy, dan penamaan route dengan project.

### Pelanggan

- `POST /orders` — create order dengan kanal/metode pembayaran.
- `POST /orders/{order:order_code}/payment-proofs` — upload bukti untuk order manual.
- `GET /orders/{order:order_code}/payment-proofs/{paymentProof}` — lihat/unduh bukti milik sendiri dengan scoped binding.
- Endpoint retry pembuatan Snap token jika pembuatan session sebelumnya gagal.

### Admin

- `PATCH /admin/orders/{order:order_code}/manual-payment`
  - nama usulan: `admin.orders.manual-payment.update`
  - khusus order manual;
  - menangani override `paid`/`unpaid` dengan guard dan audit.
- `PATCH /admin/orders/{order:order_code}/payment-proofs/{paymentProof}`
  - action `accept` atau `reject`;
  - `rejection_reason` wajib untuk reject.
- `GET /admin/orders/{order:order_code}/payment-proofs/{paymentProof}` — preview/unduh oleh admin.

### Midtrans

- `POST /payments/midtrans/notification` — webhook publik tanpa session authentication.
- Endpoint sinkronisasi status Midtrans yang hanya dapat dipanggil pihak berwenang.

Webhook dikecualikan dari CSRF hanya pada path yang tepat. Jangan memberi pengecualian wildcard yang luas.

## 7. Alur Pembayaran Manual

### 7.1 Checkout

1. Pelanggan memilih kanal `Pembayaran manual`.
2. Pelanggan memilih BCA, DANA, atau GOPAY.
3. UI menampilkan nama tujuan dan nomor dummy `12345` dari konfigurasi.
4. Server memvalidasi kanal dan metode; jangan percaya nilai rekening yang dikirim browser.
5. Dalam transaksi database, aplikasi membuat order `unpaid`, menyimpan snapshot tujuan, mengurangi stok sesuai flow existing, lalu mengosongkan cart.
6. Pelanggan diarahkan ke detail order dan instruksi upload bukti.

### 7.2 Upload bukti

1. Verifikasi bahwa user adalah pemilik order.
2. Verifikasi order berkanal manual, belum dibayar, belum dibatalkan/diselesaikan, dan metode cocok.
3. Validasi file menggunakan Form Request.
4. Simpan file pada disk privat, misalnya `payment-proofs/{order-id}/...`.
5. Buat record `payment_proofs` berstatus `pending`.
6. Tampilkan status “Menunggu verifikasi admin”.

Jangan mengubah order menjadi `paid` saat file baru diunggah.

### 7.3 Review admin

Review dilakukan dalam transaksi database dan memakai row lock pada order serta bukti:

- **Accept**:
  - pastikan proof masih `pending` dan order masih manual/unpaid;
  - ubah proof menjadi `accepted`;
  - ubah proof pending lain menjadi `superseded` bila relevan;
  - ubah order menjadi `paid` dan isi `paid_at`;
  - buat/perbarui record pembayaran manual;
  - buat audit log dengan admin pelaku.
- **Reject**:
  - ubah proof menjadi `rejected`;
  - simpan alasan penolakan;
  - order tetap `unpaid`;
  - pelanggan dapat mengunggah bukti baru.

### 7.4 Pembayaran lewat jalur lain

Jika pelanggan membayar manual tetapi bukti diberikan di luar website, admin tetap dapat memakai form override manual. Catatan wajib diisi dan audit log mencatat admin, waktu, status awal/akhir, serta alasan.

## 8. Alur Midtrans

### 8.1 Dependency dan service

- Rencanakan penggunaan SDK resmi `midtrans/midtrans-php` (seri `^2.6` saat dokumen ini dibuat).
- Periksa versi terbaru dan kompatibilitasnya ketika eksekusi.
- Bungkus SDK di service aplikasi, misalnya `MidtransService`; controller tidak memanggil SDK secara langsung.
- Jangan melakukan request jaringan ke Midtrans di dalam transaksi database.

### 8.2 Create order dan Snap session

1. Validasi checkout.
2. Dalam transaksi database, buat order `unpaid`, kurangi stok, dan kosongkan cart.
3. Generate `midtrans_order_id` yang unik dan stabil untuk attempt tersebut.
4. Setelah transaksi commit, minta Snap token melalui service.
5. Simpan token/session yang diterima.
6. Jika request Midtrans gagal, order tetap tercatat dan pelanggan mendapat opsi retry; jangan membuat order/stok kedua.
7. Tampilkan Snap dengan Client Key sesuai environment.

Callback JavaScript Snap hanya untuk pengalaman UI. Callback tidak boleh menjadi dasar terpercaya untuk menetapkan `paid`.

### 8.3 Webhook

Webhook harus:

- dapat diakses Midtrans melalui HTTPS publik;
- memverifikasi signature SHA-512 dari `order_id + status_code + gross_amount + ServerKey`;
- mencocokkan `gross_amount` dengan total order menggunakan representasi nilai yang konsisten;
- memvalidasi `order_id`, transaction status, fraud status, dan identitas transaksi;
- menyimpan raw notification dengan penyaringan data sensitif sesuai kebutuhan;
- memakai transaksi database dan row lock;
- idempotent untuk notification yang dikirim ulang;
- aman terhadap notification yang datang tidak berurutan;
- tidak pernah menurunkan status order yang sudah `paid` hanya karena attempt lama berstatus gagal;
- mengembalikan HTTP 2xx hanya setelah pemrosesan berhasil.

Mapping agregat yang direncanakan:

- `settlement` → `paid`.
- `capture` + fraud `accept` (jika berlaku) → `paid`.
- `pending` → tetap agregat `unpaid`, detail tetap dicatat di `payments.transaction_status`.
- `deny`, `cancel`, `expire`, `failure` → `failed` hanya jika tidak ada attempt sukses.
- `refund`/`partial_refund` → tangani sesuai kebijakan refund yang ditentukan saat implementasi; jangan menyederhanakan partial refund tanpa keputusan bisnis.

### 8.4 Sinkronisasi dan expiry

- Sediakan sinkronisasi status melalui API Midtrans untuk kasus notification terlambat/hilang.
- Buat scheduled reconciliation untuk order Midtrans yang belum final.
- Scheduler memakai `withoutOverlapping()` dan `onOneServer()` bila aplikasi berjalan multi-instance.
- Snap session yang kedaluwarsa dapat memerlukan pemeriksaan aktif karena expiry halaman tidak selalu menghasilkan webhook.

## 9. Stok, Pembatalan, dan Refund

- Ikuti flow existing: stok dikurangi ketika order dibuat, baik manual maupun Midtrans.
- Restock hanya sekali ketika order dibatalkan secara sah; implementasi harus idempotent.
- Order manual melewati `payment_due_at` hanya ditandai overdue untuk admin, tidak dibatalkan otomatis karena pembayaran mungkin sudah dilakukan di luar sistem.
- Order Midtrans yang terverifikasi expired/cancelled dapat dibatalkan dan direstock sesuai kebijakan existing.
- Order `paid` tidak boleh dibatalkan langsung sebelum proses refund/reversal yang eksplisit.
- Perubahan status order ke tahap pemrosesan/selesai harus ditolak jika `payment_status` belum `paid`.

## 10. Tampilan yang Direncanakan

### Checkout pelanggan

- Pilihan kanal: Midtrans atau pembayaran manual.
- Ketika manual dipilih, tampilkan pilihan BCA/DANA/GOPAY beserta nomor tujuan `12345`.
- Ringkasan total dan peringatan untuk membayar sesuai nominal order.
- Field manual tidak dikirim/dianggap valid untuk kanal Midtrans.

### Detail order pelanggan

- Badge kanal, metode, dan status pembayaran.
- Untuk manual: tujuan transfer dari snapshot order, deadline, form upload, riwayat/status bukti, dan alasan penolakan.
- Untuk Midtrans: tombol bayar/retry yang sesuai status session.

### Detail order admin

- Informasi kanal dan transaksi.
- Preview/unduh bukti manual.
- Tombol accept/reject bukti.
- Form override manual terpisah dari form status order dan hanya tampil untuk order manual.
- Riwayat perubahan status pembayaran dan identitas admin pelaku.
- Untuk Midtrans, tampilkan informasi transaksi dan aksi sinkronisasi, tanpa form override manual.

## 11. Laporan

Perbaiki seluruh laporan agar pendapatan tidak dihitung hanya berdasarkan order yang tidak dibatalkan. Kriteria pendapatan harus `payment_status = paid`, termasuk:

- total revenue;
- grafik revenue;
- average order value;
- top products berdasarkan penjualan yang sudah dibayar.

Ekspor/laporan order perlu memuat kanal pembayaran, metode, status pembayaran, `paid_at`, dan admin konfirmasi jika manual.

## 12. Tahapan Implementasi

### Tahap 0 — Preflight

- [x] Periksa versi PHP, Laravel, PHPUnit, dependency Composer, dan package frontend.
- [x] Periksa routes, controllers, services, models, migrations, factories, tests, views, dan schema aktual.
- [x] Periksa data existing sebelum menambahkan constraint/index.
- [x] Cari seluruh pemakaian `payment_status`, `paid_at`, relasi `payment`, total revenue, cancellation, dan restock.
- [x] Cari perubahan user yang belum di-commit dan hindari konflik.
- [x] Cari dokumentasi Laravel yang version-specific sebelum menulis implementasi.

### Tahap 1 — Konfigurasi dan domain model

- [x] Tambahkan template environment di `.env.example` dengan key Midtrans tetap kosong.
- [x] Buat `config/payments.php` untuk Midtrans, tujuan manual, disk, dan batas upload.
- [x] Tambahkan enum/value object kanal dan metode sesuai konvensi project.
- [x] Buat migration perubahan `orders` dan `payments`.
- [x] Buat model, migration, factory untuk `payment_proofs`.
- [x] Buat model dan migration `payment_status_logs`.
- [x] Tambahkan cast dan relasi Eloquent.
- [x] Tambahkan factory state: manual/Midtrans, paid/unpaid, serta proof pending/accepted/rejected.
- [x] Jalankan migration dan test hanya pada environment development/test yang disetujui.

### Tahap 2 — Checkout manual

- [x] Tambahkan pilihan kanal dan BCA/DANA/GOPAY pada checkout.
- [x] Tampilkan tujuan `12345` dari config.
- [x] Tambahkan conditional validation melalui Form Request.
- [x] Simpan snapshot rekening/nomor pada order.
- [x] Pastikan checkout manual tidak memanggil Midtrans.
- [x] Tambahkan informasi pembayaran manual pada detail order.

### Tahap 3 — Upload dan akses bukti pembayaran

- [x] Buat Form Request upload dengan rules file/MIME/size.
- [x] Tambahkan policy/authorization pemilik order.
- [x] Simpan file ke private disk dengan nama hasil generate server.
- [x] Buat controller upload dan controller authorized download/preview.
- [x] Tambahkan UI upload dan status review untuk pelanggan.
- [x] Pastikan route binding mencegah proof dari order lain diakses lewat URL silang.

### Tahap 4 — Review admin, override, dan guard

- [x] Buat Form Request review proof.
- [x] Implementasikan accept/reject secara atomik dengan row lock.
- [x] Buat Form Request/controller override status pembayaran manual.
- [x] Wajibkan catatan untuk override tanpa proof.
- [x] Tolak override pada order Midtrans di server, bukan hanya melalui UI.
- [x] Tambahkan audit log untuk seluruh perubahan pembayaran.
- [x] Tambahkan guard agar order belum dibayar tidak diproses/diselesaikan.
- [x] Tambahkan guard pembatalan order yang sudah dibayar.
- [x] Tambahkan tampilan proof dan kontrol manual pada admin detail.

### Tahap 5 — Laporan

- [x] Ubah revenue, chart, AOV, dan top products agar hanya menghitung order `paid`.
- [x] Perbarui ekspor dan tampilan laporan dengan data pembayaran.
- [x] Sesuaikan test admin/report existing yang saat ini membuat order default `unpaid`.

### Tahap 6 — Midtrans backend

- [x] Konfirmasi SDK dan versi resmi pada waktu eksekusi.
- [x] Tambahkan dependency SDK resmi setelah memastikan kompatibilitas.
- [x] Buat `MidtransService` dan binding/interface yang mudah dimock.
- [x] Implementasikan pembuatan Snap token setelah database commit.
- [x] Implementasikan retry tanpa membuat order atau mengurangi stok ulang.
- [x] Implementasikan webhook dengan signature, amount validation, lock, audit, dan idempotency.
- [x] Implementasikan status mapping tanpa regresi dari sukses ke gagal.
- [x] Implementasikan sinkronisasi status.
- [x] Tambahkan scheduled reconciliation.

### Tahap 7 — Midtrans frontend

- [x] Muat Snap script Sandbox/Production berdasarkan konfigurasi server yang aman.
- [x] Gunakan Client Key sesuai environment; jangan pernah kirim Server Key ke browser.
- [x] Tambahkan tombol bayar/retry dan state UI.
- [x] Perlakukan callback browser hanya sebagai navigasi/status sementara.
- [x] Setelah callback, refresh status dari backend yang telah disinkronisasi.

### Tahap 8 — Automated tests

- [x] Checkout manual sukses untuk BCA, DANA, dan GOPAY.
- [x] Halaman menampilkan tujuan dummy `12345` dan order menyimpan snapshot.
- [x] Validasi kombinasi kanal/metode menolak input yang tidak sesuai.
- [x] Checkout manual tidak melakukan network call ke Midtrans.
- [x] Owner dapat upload file valid; non-owner ditolak.
- [x] Ekstensi, MIME, dan ukuran file yang tidak valid ditolak.
- [x] File proof tersimpan privat dan URL langsung tidak tersedia.
- [x] Proof dari order lain tidak dapat diakses melalui nested route.
- [x] Admin dapat accept/reject; customer tidak dapat menjalankan review.
- [x] Reject wajib beralasan dan memungkinkan resubmission.
- [x] Accept atomik mengubah proof/order/payment/audit dengan benar.
- [x] Override manual tercatat; override Midtrans ditolak.
- [x] Order unpaid tidak dapat diproses/diselesaikan.
- [x] Webhook invalid signature atau amount ditolak.
- [x] Webhook sukses, duplikat, dan out-of-order bersifat idempotent.
- [x] Attempt Midtrans gagal tidak menurunkan order yang sudah dibayar.
- [x] Report hanya menghitung order paid.
- [x] Semua dependency eksternal dimock/fake; test tidak memanggil jaringan nyata.

Jalankan test tersempit setelah setiap perubahan, lalu suite terkait:

```powershell
php artisan test --compact tests/Feature/Admin/EcommerceAdminTest.php
php artisan test --compact
```

Jika file PHP berubah, format sebelum finalisasi:

```powershell
vendor/bin/pint --dirty --format agent
```

Jika asset frontend berubah, jalankan build yang digunakan project:

```powershell
npm run build
```

### Tahap 9 — Verifikasi Sandbox

- [x] Isi Server Key dan Client Key Sandbox hanya di `.env` lokal. (Kredensial diperbarui pengguna; probe API read-only berhasil.)
- [x] Set `MIDTRANS_ENABLED=true` dan `MIDTRANS_IS_PRODUCTION=false`.
- [x] Bersihkan/rebuild config cache sesuai environment.
- [ ] Gunakan URL HTTPS publik/tunnel untuk webhook lokal.
- [ ] Daftarkan notification URL di dashboard Sandbox.
- [ ] Uji pembayaran sukses, pending, gagal/deny, expire, cancel, dan notification duplikat.
- [ ] Uji bahwa order manual tetap berfungsi saat Midtrans tidak tersedia.
- [ ] Uji BCA/DANA/GOPAY manual, upload, reject, upload ulang, dan accept.
- [ ] Pastikan log tidak berisi Server Key, signature sensitif, atau isi dokumen proof.

### Tahap 10 — Checklist Production

Tahap ini hanya dijalankan setelah ada perintah eksplisit untuk production:

- [ ] Backup database dan siapkan rollback migration/deployment.
- [ ] Set key Production di secret manager.
- [ ] Set `MIDTRANS_IS_PRODUCTION=true`.
- [ ] Ganti nomor dummy BCA/DANA/GOPAY dengan tujuan pembayaran bisnis yang valid.
- [ ] Verifikasi account name dan instruksi pembayaran.
- [ ] Pastikan domain HTTPS, notification URL, queue, scheduler, dan storage privat siap.
- [ ] Pastikan worker/scheduler aktif dan monitoring error tersedia.
- [ ] Jalankan migration menggunakan proses deployment production yang disetujui.
- [ ] Lakukan smoke test dengan nilai minimum hanya setelah disetujui pengguna.
- [ ] Siapkan prosedur refund, rekonsiliasi, dan respons kegagalan webhook.

## 13. Definition of Done

Integrasi dianggap selesai ketika:

- [x] Pelanggan dapat memilih Midtrans atau pembayaran manual.
- [x] BCA, DANA, dan GOPAY tampil dari config dan menggunakan `12345` sampai diganti.
- [x] Bukti bayar disimpan privat, dapat ditinjau admin, dan tidak dapat diakses tanpa izin.
- [x] Accept/reject proof beserta resubmission bekerja dan tercatat di audit log.
- [x] Admin hanya dapat mengubah status pembayaran manual; status Midtrans dilindungi.
- [x] Semua transisi status, pembatalan, restock, dan retry bersifat aman/idempotent.
- [x] Laporan hanya menghitung pembayaran yang benar-benar `paid`.
- [x] Automated tests terkait lulus tanpa request jaringan nyata.
- [x] Formatter dan frontend build (bila relevan) lulus.
- [ ] Sandbox checklist lulus sebelum persiapan production.
- [x] Tidak ada credential atau file bukti pembayaran yang terekspos ke repository/public storage.

## 14. Referensi Resmi

- SDK Midtrans PHP: <https://github.com/Midtrans/midtrans-php>
- HTTP notification/webhook: <https://docs.midtrans.com/docs/https-notification-webhooks>
- Snap advanced/multiple attempts: <https://docs.midtrans.com/docs/snap-advanced-feature>
- Expire Snap session: <https://docs.midtrans.com/reference/expire-a-snap-session>

Referensi harus diperiksa kembali saat eksekusi karena API, package, dan rekomendasi keamanan dapat berubah.

## 15. Log Eksekusi

Isi bagian ini ketika implementasi benar-benar dijalankan:

| Tanggal | Tahap | Perubahan | Verifikasi | Catatan |
|---|---|---|---|---|
| 2026-10-24 | 0–8 | Checkout manual/Midtrans, proof privat, review dan audit admin, Snap retry, webhook terverifikasi, rekonsiliasi, guard stok/status, laporan paid | Migration development; 106 tests / 448 assertions lulus; Pint dan Vite build lulus | Tahap 9 belum selesai; production belum dijalankan |
| 2026-10-24 | 9 | Kedua set key dari gambar disimpan hanya dalam `.env`; mode Sandbox aktif | GET status Sandbox: HTTP 401, transport berhasil; tidak membuat transaksi | Salin ulang Server Key dan Client Key Sandbox dari dashboard. Tidak ada transaksi live. |
| 2026-10-24 | 9 | Kredensial Sandbox diperbarui pengguna; key pada `.env.example` dipindahkan ke `.env` dan contoh dikosongkan | HTTP berhasil; service menerima respons tidak ada transaksi untuk probe; scan credential repository bersih | Autentikasi Sandbox valid. Tidak membuat transaksi pembayaran. Uji end-to-end tetap menunggu webhook HTTPS publik. |

### Konfigurasi yang sudah diterapkan

- `MIDTRANS_IS_PRODUCTION=false` memilih `MIDTRANS_SANDBOX_MERCHANT_ID`, `MIDTRANS_SANDBOX_SERVER_KEY`, dan `MIDTRANS_SANDBOX_CLIENT_KEY`.
- `MIDTRANS_IS_PRODUCTION=true` memilih pasangan `MIDTRANS_PRODUCTION_*`. Isi secret ini pada environment hosting sebelum mengaktifkan production.
- Variabel generik `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, dan `MIDTRANS_MERCHANT_ID` tetap didukung jika variabel berprefix kosong. Semua contoh di `.env.example` tetap tanpa credential.
- Endpoint notification: `POST /payments/midtrans/notification`. Daftarkan URL HTTPS publik yang menuju path tersebut pada dashboard Midtrans; alamat localhost tidak dapat menerima notification dari Midtrans.
- Jalankan scheduler Laravel pada hosting; `payments:reconcile` dijadwalkan setiap 5 menit, memakai `withoutOverlapping()` dan `onOneServer()`. Gunakan cache lock yang dibagi antar instance.
- `PAYMENT_PROOF_DISK=local` menggunakan `storage/app/private`; endpoint proof selalu memeriksa authorization. Jangan mengarahkan disk tersebut ke folder publik.

### Keputusan implementasi dan batas verifikasi

- Dua order lama tidak memiliki bukti kanal pembayaran, sehingga `payment_channel` tetap `null`. Migration hanya backfill data dengan `midtrans_order_id`; tidak menebak manual untuk data lama.
- Webhook memeriksa signature dan nominal, lalu mengambil status melalui API Midtrans sebelum mengubah database. Payload tersimpan hanya field allowlist; Server Key dan signature tidak disimpan pada riwayat transaksi.
- `refund` penuh terverifikasi menjadi `refunded`. `partial_refund` tetap `paid`, jumlah refund dicatat pada detail transaksi/audit dan ditampilkan kepada admin. Laporan adalah **pendapatan bruto pembayaran paid**, bukan laporan pendapatan neto setelah refund parsial. Refund tetap dilakukan melalui dashboard/proses bisnis; implementasi ini tidak mengirim permintaan refund uang.
- Pembatalan/restock Midtrans hanya melalui status terminal terverifikasi setelah jatuh tempo, atau pembatalan sesi Snap yang dikonfirmasi server. Jika sesi/token hilang setelah timeout dan status belum tersedia, stok tetap dicadangkan untuk rekonsiliasi; aplikasi tidak menebak bahwa pembayaran gagal.
- Tujuan manual tetap dummy `12345` sesuai panduan. Ganti dengan tujuan bisnis yang benar sebelum menerima pembayaran pelanggan.
- Halaman pelanggan/admin diuji melalui feature tests. Tidak ada sesi browser yang tersedia untuk inspeksi visual interaktif. Kredensial Sandbox sudah valid; pengujian pembayaran sampai webhook masih menunggu endpoint HTTPS publik.
