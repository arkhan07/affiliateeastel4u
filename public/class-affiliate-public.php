<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Affiliate_MLM_Public {

    public static function init() {}

    public static function enqueue_assets() {
        // ─── Tailwind CSS CDN ───────────────────────────────────────────
        wp_enqueue_script(
            'tailwindcss',
            'https://cdn.tailwindcss.com',
            [],
            null,
            false
        );

        // ─── Google Fonts ───────────────────────────────────────────────
        wp_enqueue_style(
            'affiliate-google-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap',
            [],
            null
        );

        // ─── Plugin CSS ─────────────────────────────────────────────────
        wp_enqueue_style(
            'affiliate-mlm-style',
            AFFILIATE_MLM_PLUGIN_URL . 'assets/css/affiliate-public.css',
            [ 'affiliate-google-fonts' ],
            AFFILIATE_MLM_VERSION
        );

        // ─── Plugin JS ──────────────────────────────────────────────────
        wp_enqueue_script(
            'affiliate-mlm-script',
            AFFILIATE_MLM_PLUGIN_URL . 'assets/js/affiliate-public.js',
            [ 'jquery' ],
            AFFILIATE_MLM_VERSION,
            true
        );

        wp_localize_script( 'affiliate-mlm-script', 'affiliateMLM', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'affiliate_mlm_nonce' ),
            'i18n'    => [
                'copied'         => esc_html__( 'Disalin!', 'affiliate-mlm-pro' ),
                'copy'           => esc_html__( 'Salin', 'affiliate-mlm-pro' ),
                'confirm_logout' => esc_html__( 'Adakah anda pasti mahu log keluar?', 'affiliate-mlm-pro' ),
                'processing'     => esc_html__( 'Memproses...', 'affiliate-mlm-pro' ),
                'error'          => esc_html__( 'Ralat berlaku. Cuba lagi.', 'affiliate-mlm-pro' ),
            ],
        ] );

        // DataTables for ref table
        if ( is_page() || is_singular() ) {
            wp_enqueue_style(
                'datatables',
                'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css',
                [],
                '1.13.6'
            );
            wp_enqueue_script(
                'datatables',
                'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js',
                [ 'jquery' ],
                '1.13.6',
                true
            );
        }

        // Tailwind config — must be inline after tailwindcss is loaded
        add_action( 'wp_head', [ __CLASS__, 'print_tailwind_config' ], 20 );
    }

    /**
     * Print Tailwind config inline – purple/violet primary theme.
     */
    public static function print_tailwind_config() {
        ?>
        <script>
        if (typeof tailwind !== 'undefined') {
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            primary:  { DEFAULT:'#7c3aed', 50:'#f5f3ff', 100:'#ede9fe', 200:'#ddd6fe', 300:'#c4b5fd', 400:'#a78bfa', 500:'#8b5cf6', 600:'#7c3aed', 700:'#6d28d9', 800:'#5b21b6', 900:'#4c1d95' },
                            surface:  { DEFAULT:'#1e1635', card:'#261d45', card2:'#1a1130', border:'#352960' },
                        },
                        fontFamily: {
                            sans:  ['Inter','Poppins','-apple-system','sans-serif'],
                            mono:  ['JetBrains Mono','monospace'],
                        },
                        animation: {
                            'fade-in':    'fadeIn .4s ease-out',
                            'slide-up':   'slideUp .4s ease-out',
                            'pulse-slow': 'pulse 3s infinite',
                        },
                        keyframes: {
                            fadeIn:  { '0%':{ opacity:'0' },'100%':{ opacity:'1' } },
                            slideUp: { '0%':{ opacity:'0', transform:'translateY(20px)' },'100%':{ opacity:'1', transform:'translateY(0)' } },
                        }
                    }
                }
            }
        }
        </script>
        <?php
    }
}
