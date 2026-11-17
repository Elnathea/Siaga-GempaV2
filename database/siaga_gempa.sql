-- =============================================
-- SIAGA GEMPA - Database SQL
-- Jalankan file ini di phpMyAdmin atau MySQL CLI
-- =============================================

CREATE DATABASE IF NOT EXISTS `db_siaga_gempa`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `db_siaga_gempa`;

-- =============================================
-- Tabel: data_gempa
-- =============================================
DROP TABLE IF EXISTS `data_gempa`;
CREATE TABLE `data_gempa` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `magnitudo`      DECIMAL(4,1)   NOT NULL,
  `kedalaman`      INT            NOT NULL COMMENT 'km',
  `waktu`          DATETIME       NOT NULL,
  `wilayah`        VARCHAR(200)   NOT NULL,
  `latitude`       DECIMAL(10,6)  NOT NULL,
  `longitude`      DECIMAL(10,6)  NOT NULL,
  `status_tsunami` ENUM('Ya','Tidak','Potensi') NOT NULL DEFAULT 'Tidak',
  `created_at`     TIMESTAMP      DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `data_gempa` (`magnitudo`,`kedalaman`,`waktu`,`wilayah`,`latitude`,`longitude`,`status_tsunami`) VALUES
(6.2, 10,  '2024-11-10 03:14:22', 'Cianjur, Jawa Barat',                       -6.9147,  107.1448, 'Tidak'),
(5.8, 25,  '2024-11-09 17:52:08', 'Laut Banda, Maluku',                        -5.5200,  129.8200, 'Potensi'),
(4.9, 15,  '2024-11-08 11:20:45', 'Palu, Sulawesi Tengah',                     -0.9000,  119.8700, 'Tidak'),
(7.1, 30,  '2024-11-07 22:05:33', 'Halmahera Utara, Maluku Utara',              1.8600,  128.2400, 'Ya'),
(5.3, 50,  '2024-11-06 08:44:17', 'Nias, Sumatera Utara',                       1.0674,   97.5987, 'Tidak'),
(6.5, 12,  '2024-11-05 14:33:59', 'Lombok, Nusa Tenggara Barat',               -8.5500,  116.2000, 'Potensi'),
(4.5, 20,  '2024-11-04 06:10:11', 'Bandung, Jawa Barat',                       -6.9175,  107.6191, 'Tidak'),
(5.0, 35,  '2024-11-03 19:27:44', 'Aceh Besar, Aceh',                           5.5483,   95.3238, 'Tidak'),
(6.8, 18,  '2024-11-02 10:55:30', 'Poso, Sulawesi Tengah',                     -1.3900,  120.7500, 'Tidak'),
(4.7, 45,  '2024-11-01 05:38:22', 'Banjarnegara, Jawa Tengah',                 -7.3800,  109.7000, 'Tidak'),
(5.5, 60,  '2024-10-31 21:12:07', 'Flores, NTT',                               -8.7400,  121.6700, 'Tidak'),
(6.0, 10,  '2024-10-30 13:48:50', 'Ternate, Maluku Utara',                      0.7800,  127.3800, 'Potensi'),
(5.2, 22,  '2024-10-29 09:03:15', 'Sorong, Papua Barat',                       -0.8653,  131.2522, 'Tidak'),
(4.8, 30,  '2024-10-28 15:21:40', 'Tasikmalaya, Jawa Barat',                   -7.3268,  108.2244, 'Tidak'),
(7.4, 20,  '2024-10-27 02:17:55', 'Donggala, Sulawesi Tengah',                 -0.6765,  119.7395, 'Ya');

-- =============================================
-- Tabel: zona_sesar
-- =============================================
DROP TABLE IF EXISTS `zona_sesar`;
CREATE TABLE `zona_sesar` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nama_sesar`       VARCHAR(150)  NOT NULL,
  `tingkat_risiko`   ENUM('Normal','Rendah','Tinggi','Ekstrem') NOT NULL,
  `kode_warna_hex`   CHAR(7)       NOT NULL,
  `deskripsi`        TEXT,
  `koordinat_jalur`  LONGTEXT      COMMENT 'JSON array of [lat,lng] pairs',
  `created_at`       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `zona_sesar` (`nama_sesar`,`tingkat_risiko`,`kode_warna_hex`,`deskripsi`,`koordinat_jalur`) VALUES
('Sesar Lembang',
 'Ekstrem', '#EF4444',
 'Sesar aktif di utara Bandung sepanjang ~29 km. Berpotensi menghasilkan gempa M 6.5-7.0 yang berdampak langsung pada Kota Bandung dengan populasi >2 juta jiwa.',
 '[[-6.820,107.520],[-6.808,107.560],[-6.796,107.600],[-6.784,107.640],[-6.772,107.680],[-6.760,107.720]]'),

('Sesar Semangko (Sumatra Fault)',
 'Ekstrem', '#EF4444',
 'Sesar geser aktif terpanjang di Indonesia, membentang ~1900 km dari Banda Aceh hingga Teluk Semangka, Lampung. Menyebabkan serangkaian gempa merusak di Sumatra.',
 '[[5.400,95.200],[4.000,96.500],[2.500,98.000],[1.000,99.500],[-0.500,100.800],[-2.000,101.700],[-3.500,102.600],[-4.800,103.800],[-5.500,105.000]]'),

('Sesar Palu-Koro',
 'Ekstrem', '#EF4444',
 'Sesar geser kiri yang melewati Kota Palu, Sulawesi Tengah. Bertanggung jawab atas gempa M 7.4 dan tsunami Palu 2018 yang menewaskan lebih dari 2.000 orang.',
 '[[-0.200,119.900],[-0.700,119.850],[-1.100,119.800],[-1.500,119.750],[-2.000,119.650],[-2.500,119.500]]'),

('Sesar Jayapura (Sorong)',
 'Tinggi', '#F97316',
 'Zona sesar transform Sorong-Jayapura di Papua. Aktifitas tektonik tinggi akibat pertemuan lempeng Australia dan Pasifik, menghasilkan gempa rutin berkekuatan >5 SR.',
 '[[-0.500,131.000],[-0.800,132.500],[-1.200,134.000],[-1.500,135.500],[-2.000,137.000],[-2.500,138.500],[-3.000,140.000]]'),

('Zona Subduksi Selatan Jawa',
 'Ekstrem', '#EF4444',
 'Zona subduksi aktif di selatan Pulau Jawa dimana Lempeng Indo-Australia menunjam di bawah Lempeng Eurasia. Sumber potensi megathrust dengan kekuatan >8.0 SR.',
 '[[-8.200,106.000],[-8.500,107.500],[-8.800,109.000],[-9.100,110.500],[-9.400,112.000],[-9.700,113.500],[-10.000,115.000],[-10.300,116.500]]'),

('Sesar Cimandiri',
 'Tinggi', '#F97316',
 'Sesar aktif yang melewati Sukabumi hingga Cianjur di Jawa Barat. Berkontribusi pada gempa Cianjur 2022 (M 5.6) yang menewaskan lebih dari 300 jiwa.',
 '[[-6.900,107.100],[-6.950,107.200],[-7.000,107.300],[-7.050,107.400],[-7.100,107.500]]'),

('Zona Subduksi Sumatra',
 'Ekstrem', '#EF4444',
 'Zona subduksi megathrust barat Sumatra. Sumber gempa mega Aceh 2004 (M 9.1) dan gempa Nias 2005 (M 8.6). Potensi megathrust segmen Mentawai sangat tinggi.',
 '[[5.000,94.000],[3.000,95.000],[1.000,96.500],[-1.000,97.500],[-3.000,99.000],[-5.000,100.500]]'),

('Sesar Baribis-Kendeng',
 'Tinggi', '#F97316',
 'Sesar naik aktif di utara Jawa Barat hingga Jawa Timur. Memanjang dari daerah Kuningan-Majalengka hingga Jawa Timur, mengancam kawasan padat di pesisir utara Jawa.',
 '[[-6.650,108.300],[-6.700,109.200],[-6.750,110.100],[-6.800,111.000],[-6.850,111.900]]'),

('Zona Sesar Kalimantan Utara',
 'Rendah', '#FACC15',
 'Aktivitas seismik relatif lebih rendah dibandingkan bagian Indonesia lainnya. Namun terdapat beberapa sesar minor di Kalimantan Utara yang perlu dipantau.',
 '[[4.000,116.500],[3.500,117.000],[3.000,117.500],[2.500,118.000],[2.000,118.500]]'),

('Zona Sumba-Flores',
 'Tinggi', '#F97316',
 'Kawasan di NTT dengan aktivitas seismik tinggi akibat interaksi lempeng di zona Banda Arc. Gempa Flores 1992 (M 7.8) menyebabkan tsunami dengan ketinggian >26 m.',
 '[[-8.500,119.000],[-8.700,120.000],[-8.900,121.000],[-9.000,122.000],[-8.800,123.000]]');

-- =============================================
-- Tabel: panduan_siaga
-- =============================================
DROP TABLE IF EXISTS `panduan_siaga`;
CREATE TABLE `panduan_siaga` (
  `id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kategori`  VARCHAR(100)  NOT NULL,
  `icon`      VARCHAR(10)   NOT NULL DEFAULT '📋',
  `urutan`    TINYINT       NOT NULL DEFAULT 0,
  `judul`     VARCHAR(200)  NOT NULL,
  `isi`       TEXT          NOT NULL,
  `created_at` TIMESTAMP   DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `panduan_siaga` (`kategori`,`icon`,`urutan`,`judul`,`isi`) VALUES
('Sebelum Gempa','🏠',1,'Siapkan Tas Siaga Bencana','Siapkan tas siaga berisi: air minum (1,5 liter/orang/hari untuk 3 hari), makanan tahan lama, obat-obatan pribadi, senter + baterai cadangan, peluit darurat, radio baterai, dokumen penting (fotokopi KTP, KK, buku tabungan) dalam plastik kedap air, pakaian ganti, uang tunai, ponsel + powerbank, dan kotak P3K.'),
('Sebelum Gempa','🏠',2,'Kenali Titik Kumpul & Jalur Evakuasi','Pelajari jalur evakuasi dari rumah, sekolah, dan tempat kerja. Tentukan titik kumpul keluarga di tempat terbuka jauh dari bangunan. Pastikan seluruh anggota keluarga mengetahui nomor telepon darurat BPBD setempat.'),
('Sebelum Gempa','🏠',3,'Amankan Perabot & Benda Berat','Ikat lemari, rak buku, dan peralatan besar ke dinding dengan sekrup. Pindahkan benda berat ke lantai bawah. Pastikan perabotan tidak menghalangi pintu keluar. Periksa instalasi gas dan listrik secara berkala.'),
('Sebelum Gempa','🏠',4,'Perkuat Struktur Bangunan','Pastikan rumah memenuhi standar tahan gempa. Periksa retakan pada dinding dan fondasi. Konsultasikan dengan ahli konstruksi untuk perkuatan rumah. Hindari membangun rumah di lereng curam atau tepi sungai.'),

('Saat Gempa','⚠️',1,'Drop, Cover, Hold On','Segera lakukan DROP (merunduk), COVER (berlindung di bawah meja kuat atau lindungi kepala-leher dengan tangan), HOLD ON (berpegangan kuat). Jangan berlari saat guncangan masih terjadi. Hindari jendela, dinding luar, dan benda yang dapat jatuh.'),
('Saat Gempa','⚠️',2,'Jika Berada di Dalam Ruangan','Tetap di dalam bangunan. Hindari pintu (risiko tertimpa kusen). Menjauh dari jendela, kaca, dan benda gantung. Jika tidak ada meja, berlindung di sudut ruangan sambil melindungi kepala. Jangan gunakan lift.'),
('Saat Gempa','⚠️',3,'Jika Berada di Luar Ruangan','Menjauh dari bangunan, pohon tinggi, tiang listrik, dan jembatan. Cari area terbuka yang aman. Jika di dalam kendaraan, menepi dengan aman, matikan mesin, dan tetap di dalam kendaraan hingga guncangan berhenti.'),
('Saat Gempa','⚠️',4,'Jika Tertimbun Reruntuhan','Jangan panik. Tutup mulut dan hidung dengan kain. Ketuk pipa atau dinding agar tim penyelamat mendengar. Jangan berteriak kecuali terdengar suara orang. Gunakan peluit jika tersedia. Hemat energi untuk bertahan.'),

('Setelah Gempa','🔍',1,'Keluar dengan Tertib & Waspada Susulan','Keluar dari bangunan dengan hati-hati setelah guncangan berhenti. Waspadai reruntuhan saat keluar. Periksa kondisi fisik diri dan orang sekitar. Gempa susulan bisa terjadi, tetap waspada dan jangan masuk kembali ke bangunan yang rusak.'),
('Setelah Gempa','🔍',2,'Periksa Kebocoran Gas & Kerusakan Listrik','Cium bau gas - jika ada, buka jendela dan segera keluar. Jangan nyalakan api atau saklar listrik sebelum yakin aman. Matikan sumber gas dan listrik dari panel utama. Laporkan kerusakan jaringan ke petugas terkait.'),
('Setelah Gempa','🔍',3,'Ikuti Informasi dari Sumber Resmi','Pantau informasi dari BMKG, BPBD, dan BNPB melalui radio, TV, atau aplikasi resmi. Jangan menyebarkan informasi yang belum terverifikasi. Patuhi instruksi evakuasi dari petugas berwenang.'),
('Setelah Gempa','🔍',4,'Bantu Korban & Laporkan Kerusakan','Berikan pertolongan pertama bagi yang membutuhkan. Hubungi layanan darurat (119, 117, 110) jika ada korban serius. Laporkan kerusakan bangunan ke BPBD setempat. Daftarkan diri di posko pengungsian jika harus mengungsi.'),

('Tas Siaga','🎒',1,'Daftar Isi Tas Siaga Lengkap','Tas siaga idealnya berbobot 10-15 kg dan mudah dibawa. Isi wajib: dokumen penting (dalam plastik zip), uang tunai, air minum 3L, makanan darurat 3 hari (biskuit, kornet, mie instan), obat-obatan rutin + P3K, senter + baterai, ponsel + charger + powerbank, radio kecil, peluit, masker, dan selimut darurat (space blanket).'),
('Tas Siaga','🎒',2,'Lokasi Penyimpanan & Pemeliharaan','Simpan tas siaga di tempat yang mudah dijangkau, dekat pintu keluar. Periksa dan perbarui isi tas setiap 6 bulan. Ganti air minum dan makanan yang mendekati tanggal kedaluwarsa. Pastikan semua anggota keluarga tahu lokasi tas siaga.'),

('Di Dalam Kendaraan','🚗',1,'Saat Gempa Melanda di Jalan','Menepi perlahan ke sisi jalan yang aman. Hindari berhenti di bawah jembatan, pohon tinggi, atau tiang listrik. Matikan mesin dan rem parkir. Nyalakan lampu hazard. Tetap di dalam kendaraan hingga guncangan berhenti. Dengarkan radio untuk informasi lanjutan.'),
('Di Dalam Kendaraan','🚗',2,'Setelah Gempa Saat Berkendara','Hindari jalan yang rusak, jembatan retak, dan daerah longsor. Patuhi arahan petugas lalu lintas. Berikan prioritas jalan bagi kendaraan darurat. Jangan melintas jalan yang tergenang karena potensi likuifaksi.'),

('Di Pesisir','🌊',1,'Ancaman Tsunami Pasca Gempa','Jika merasakan gempa kuat (>30 detik) di dekat pantai: segera berlari menuju daratan tinggi tanpa menunggu peringatan resmi. Prinsip: gempa kuat = tsunami mungkin terjadi. Jangan kembali ke pantai sebelum ada pernyataan aman dari BMKG. Ketinggian aman minimum: 20-30 meter di atas permukaan laut.'),
('Di Pesisir','🌊',2,'Kenali Tanda-Tanda Tsunami','Air laut surut tiba-tiba (unusual drawback), suara gemuruh dari laut, bau ikan yang kuat, hewan-hewan berlari menjauh dari pantai - semua ini adalah tanda peringatan dini tsunami. Segera evakuasi ke tempat tinggi.'),

('Di Luar Ruangan','🌳',1,'Saat Gempa di Area Terbuka','Menjauh dari bangunan, pohon tinggi, menara, dan tiang listrik. Berlindung di area terbuka yang lapang. Waspada longsor jika berada di lereng gunung atau tebing. Hindari tepi sungai karena risiko banjir bandang dan likuifaksi.'),
('Di Luar Ruangan','🌳',2,'Saat Gempa di Pusat Perbelanjaan','Jangan panik dan jangan berlari. Berlindung di bawah struktur yang kuat. Hindari eskalator dan lift. Ikuti petunjuk evakuasi yang tersedia. Jangan bergerombol di dekat pintu keluar karena risiko terinjak. Tunggu instruksi petugas keamanan.');

-- =============================================
-- Tabel: kontak_darurat
-- =============================================
DROP TABLE IF EXISTS `kontak_darurat`;
CREATE TABLE `kontak_darurat` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nama_instansi`  VARCHAR(150) NOT NULL,
  `kategori`       VARCHAR(80)  NOT NULL DEFAULT 'Darurat',
  `nomor_telepon`  VARCHAR(50)  NOT NULL,
  `icon`           VARCHAR(10)  NOT NULL DEFAULT '📞',
  `keterangan`     VARCHAR(300) NOT NULL,
  `created_at`     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `kontak_darurat` (`nama_instansi`,`kategori`,`nomor_telepon`,`icon`,`keterangan`) VALUES
('BMKG (Badan Meteorologi, Klimatologi, dan Geofisika)', 'Informasi Gempa', '(021) 650-5650 / 196', '🌊', 'Pusat informasi gempa bumi, tsunami, dan cuaca ekstrem. Tersedia 24 jam. Website: bmkg.go.id'),
('BNPB (Badan Nasional Penanggulangan Bencana)',         'Koordinasi Bencana', '117',                '🏛️', 'Hotline nasional penanggulangan bencana. Koordinasi penanganan darurat skala nasional. Tersedia 24 jam.'),
('Basarnas (Badan SAR Nasional)',                        'Pencarian & Penyelamatan', '115',           '🚁', 'Layanan pencarian dan penyelamatan (SAR) nasional. Hubungi jika ada korban yang perlu diselamatkan.'),
('PMI (Palang Merah Indonesia)',                         'Medis & Kemanusiaan', '021-7992325',        '🏥', 'Bantuan medis, darah, dan kemanusiaan pasca bencana. PMI siap membantu di seluruh Indonesia.'),
('Polri (Kepolisian Republik Indonesia)',                'Keamanan & Ketertiban', '110',              '👮', 'Layanan kepolisian 24 jam. Laporkan situasi darurat, gangguan keamanan, atau minta bantuan evakuasi.'),
('Pemadam Kebakaran (Damkar)',                           'Kebakaran & Penyelamatan', '113',            '🚒', 'Layanan pemadaman kebakaran dan penyelamatan. Pasca gempa sering terjadi kebakaran akibat korsleting.'),
('Ambulans / IGD Nasional',                              'Medis Darurat', '119',                      '🚑', 'Layanan ambulans dan gawat darurat medis nasional. Prioritas untuk korban luka, pingsan, dan kritis.'),
('PLN (Perusahaan Listrik Negara)',                      'Kelistrikan', '123',                         '⚡', 'Laporkan kerusakan jaringan listrik pasca gempa. Penting untuk mencegah kebakaran akibat korsleting.'),
('Gas Negara / PGN',                                    'Gas & Energi', '1500-645',                   '🔥', 'Laporkan kebocoran gas segera. Matikan sumber gas, buka ventilasi, dan hubungi nomor ini.'),
('BPBD (Badan Penanggulangan Bencana Daerah)',           'Penanggulangan Daerah', '(sesuai daerah)',   '🏢', 'Hubungi BPBD di kabupaten/kota Anda untuk koordinasi evakuasi dan bantuan bencana lokal. Cari nomor BPBD setempat.'),
('SAR Provinsi Jawa Barat',                              'SAR Regional', '(022) 7103-115',             '🔍', 'Layanan SAR wilayah Jawa Barat termasuk Bandung, Cianjur, Sukabumi, dan sekitarnya.'),
('SAR Provinsi Sulawesi Tengah',                         'SAR Regional', '(0451) 421-114',             '🔍', 'Layanan SAR wilayah Sulawesi Tengah termasuk Palu, Donggala, dan Poso.'),
('Siaga Bencana BNPB (WhatsApp)',                        'Digital & Media Sosial', '0817-779-115',      '💬', 'Nomor WhatsApp resmi BNPB untuk laporan dan informasi bencana. Aktif pada masa tanggap darurat.');
