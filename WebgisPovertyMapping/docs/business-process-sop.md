# Business Process SOP - WebGIS Poverty Mapping

## Status Dokumen

Dokumen ini adalah acuan proses bisnis aktif. Untuk detail teknis, lihat `docs/codebase-navigation.md`.

## Aktor

### Administrator

Administrator mengatur master data, user, verifikasi, import, export, dan recalculate proximity. Administrator juga bertanggung jawab memastikan data yang tampil ke publik sudah `Terverifikasi`.

### Operator

Operator mengelola warga, kebutuhan, dan status bantuan dalam rumah ibadah yang ditugaskan. Data warga yang dibuat operator masuk `Pending` sampai Administrator melakukan Verifikasi.

### Publik

Publik dapat melihat peta dan papan kebutuhan tanpa melihat NIK, nama KK, alamat detail, catatan, atau koordinat rumah warga. Publik diperlakukan sebagai `viewer` read-only.

### Donatur

Donatur mengirim kontak dan kategori minat bantuan melalui papan kebutuhan publik. Saat ini flow Donatur masih berupa kontak umum, belum pledge ke kebutuhan spesifik.

## Alur Data Warga

1. Administrator atau Operator menambahkan warga.
2. Sistem menghitung Proximity dari titik warga ke rumah ibadah aktif.
3. Data yang dibuat Administrator langsung `Terverifikasi`.
4. Data yang dibuat Operator masuk `Pending`.
5. Administrator melakukan Verifikasi: approve menjadi `Terverifikasi` atau reject menjadi `Ditolak`.
6. Hanya warga `Terverifikasi` yang dapat masuk workflow bantuan/kebutuhan publik.

## Recalculate Proximity

Recalculate dapat mengubah rumah ibadah terdekat warga. Karena model saat ini `proximity-only`, assignment operator dapat berubah mengikuti hasil spasial terbaru setelah posisi/radius rumah ibadah diubah.

Untuk data kecil-menengah, recalculate langsung masih dapat dipahami dan dirawat. Jika jumlah warga/rumah ibadah menjadi besar, gunakan pendekatan batch atau queue agar proses tidak membuat request admin terasa lama.

## Status Bantuan dan Kebutuhan

Status bantuan warga bergerak dari `Belum Ditangani`, `Dalam Proses`, sampai `Sudah Ditangani`.

Status kebutuhan bergerak dari `Belum Terpenuhi`, `Dalam Proses`, sampai `Terpenuhi`.

## Catatan Audit

Setiap perubahan status bantuan atau kebutuhan sebaiknya memiliki Catatan agar riwayat mudah dipahami. Catatan menjawab pertanyaan sederhana: kenapa status ini berubah.

## Batas Flow Donatur Saat Ini

Donatur belum memilih kebutuhan spesifik. Admin membaca kontak donatur, lalu menindaklanjuti manual di luar sistem. Jika ingin closed-loop, perlu tabel donasi/pledge dan status konfirmasi.
