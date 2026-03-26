=== Affiliate MLM Pro ===
Contributors: yourname
Tags: affiliate, mlm, woocommerce, referral, commission
Requires at least: 6.0
Tested up to: 6.5
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later

Plugin WordPress Affiliate MLM lengkap dengan WooCommerce, Elementor, dan REST API.

== Deskripsi ==

Affiliate MLM Pro ialah plugin affiliate marketing berjenjang (multi-level) untuk WordPress yang
menyokong WooCommerce, Elementor, dan REST API. Dilengkapi dengan sistem komisen automatik,
pengeluaran (withdrawal), dashboard member, landing page, dan banyak lagi.

== Ciri-ciri ==

* Sistem link affiliate (?ref=username)
* MLM sehingga 3 level
* Komisen pendaftaran & pembelian (WooCommerce)
* Dashboard member lengkap
* Leaderboard
* Pengeluaran (Bank/E-Wallet)
* Landing page per affiliate (/affiliate/username)
* Integrasi WhatsApp
* QR Code affiliate
* Elementor Dynamic Tags
* REST API (/wp-json/affiliate/v1/)
* Sokongan i18n (ms_MY, id_ID, en_US)

== Pemasangan ==

1. Upload folder 'affiliate-mlm-plugin' ke /wp-content/plugins/
   ATAU upload fail .zip melalui Plugins > Add New > Upload Plugin

2. Aktifkan plugin melalui menu 'Plugins' dalam WordPress

3. Plugin akan mencipta tabel database secara automatik semasa pengaktifan

4. Pergi ke Affiliate MLM > Settings untuk tetapkan kadar komisen dan lain-lain

5. Tambah shortcode ke halaman WordPress:
   [affiliate_register]   — Borang pendaftaran affiliate
   [affiliate_dashboard]  — Dashboard member
   [affiliate_leaderboard] — Papan pemimpin
   [affiliate_withdraw_form] — Borang pengeluaran
   [affiliate_ref_table]  — Jadual referral

== Shortcode Tersedia ==

   [affiliate_register]
   [affiliate_dashboard]
   [affiliate_total_hits]
   [affiliate_total_member]
   [affiliate_vip_member]
   [affiliate_free_member]
   [affiliate_active_today]
   [affiliate_leaderboard limit="10"]
   [affiliate_link]
   [affiliate_qr_code]
   [affiliate_ref_table]
   [affiliate_withdraw_form]

== REST API ==

Base URL: /wp-json/affiliate/v1/

   GET  /member-stats       — Statistik member semasa
   GET  /commissions        — Senarai komisen
   GET  /leaderboard        — Papan pemimpin
   GET  /downline           — Pokok downline
   POST /withdraw/request   — Hantar permohonan pengeluaran

== Landing Page ==

URL format: https://yoursite.com/affiliate/username
(Pergi ke Settings > Permalinks dan klik Save selepas install)

== Keperluan ==

   WordPress 6.0+
   PHP 7.4+
   MySQL 5.7+ / MariaDB 10.3+
   WooCommerce 7.0+ (untuk komisen pembelian)
   Elementor (opsional, untuk Dynamic Tags)

== Tetapan Pilihan ==

   Semua tetapan tersedia di: WordPress Admin > Affiliate MLM > Settings

== Changelog ==

= 1.0.0 =
* Pelancaran awal
* Sistem affiliate link dengan cookie tracking
* MLM 3 level
* Integrasi WooCommerce
* Sistem withdrawal
* REST API
* Sokongan Elementor Dynamic Tags
* Borang pendaftaran dengan captcha dan honeypot
* i18n ready (ms_MY, id_ID)
