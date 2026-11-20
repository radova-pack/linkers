<?php
// abort if this file is called directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'WPINC' ) || ! function_exists( 'add_action' ) ) {
    echo 'Hi there! I just a extended module, please back.';
    exit;
}

function linkers_run_browse_repository() {
    // ambil pilihan kategori dari URL kalau user ngeklik link
    $pilih_mode = isset( $_GET['mode'] ) ? sanitize_text_field( $_GET['mode'] ) : 'jenis_koleksi';
    $pilih_isi  = isset( $_GET['isi'] ) ? sanitize_text_field( $_GET['isi'] ) : '';

    ob_start();

    // tampilin menu pilihan kelompok (4 struktur data eprints)
    echo '<div>';
    echo '<strong>Jelajahi Berdasarkan: </strong>';
    echo '<a href="?mode=jenis_koleksi">Jenis Dokumen</a> | ';
    echo '<a href="?mode=topik_dokumen">Kategori / Topik</a> | ';
    echo '<a href="?mode=tahun_dokumen">Tahun Pembuatan</a> | ';
    echo '<a href="?mode=pembuat">Pembuat / Penulis</a>';
    echo '</div><hr>';

    // tampilin daftar rak/kelompok sesuai menu yang dipilih
    if ( $pilih_mode == 'pembuat' ) {
        // ngambil dokumen publik saja untuk ngumpulin nama penulisnya
        $semua_doc = get_posts( array(
            'post_type'      => 'linkers_doc',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_status_akses',
                    'value'   => array( 'public', 'publik' ),
                    'compare' => 'IN',
                ),
            ),
        ) );

        // kumpulin nama pembuat ke dalam array biar nggak dobel
        $daftar_pembuat = array();
        foreach ( $semua_doc as $doc ) {
            $nama = get_post_meta( $doc->ID, '_nama_pembuat', true );
            if ( ! empty( $nama ) && ! in_array( $nama, $daftar_pembuat ) ) {
                $daftar_pembuat[] = $nama;
            }
        }

        echo '<h3>Daftar Pembuat / Penulis:</h3>';
        if ( ! empty( $daftar_pembuat ) ) {
            echo '<ul>';
            foreach ( $daftar_pembuat as $pembuat ) {
                $link = '?mode=pembuat&isi=' . urlencode( $pembuat );
                echo '<li><a href="' . esc_url( $link ) . '">' . esc_html( $pembuat ) . '</a></li>';
            }
            echo '</ul>';
        } else {
            echo '<p>Belum ada data pembuat nih.</p>';
        }
    } else {
        // ngambil daftar kategori dari taxonomy (jenis_koleksi, topik_dokumen, atau tahun_dokumen)
        $daftar_kategori = get_terms( array(
            'taxonomy'   => $pilih_mode,
            'hide_empty' => false,
        ) );

        echo '<h3>Daftar Kategori:</h3>';
        if ( ! empty( $daftar_kategori ) && ! is_wp_error( $daftar_kategori ) ) {
            echo '<ul>';
            foreach ( $daftar_kategori as $kat ) {
                $link = '?mode=' . $pilih_mode . '&isi=' . $kat->slug;
                echo '<li><a href="' . esc_url( $link ) . '">' . esc_html( $kat->name ) . '</a> (' . $kat->count . ')</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>Belum ada kategori yang dibuat nih.</p>';
        }
    }

    // kalau user sudah ngeklik salah satu kategori/penulis, tampilkan dokumennya
    if ( ! empty( $pilih_isi ) ) {
        echo '<hr>';
        echo '<h3>Dokumen untuk: ' . esc_html( $pilih_isi ) . '</h3>';

        // siapkan aturan pencarian dasar (hanya dokumen yang sudah publish dan publik)
        $aturan = array(
            'post_type'      => 'linkers_doc',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'meta_query'     => array(
                array(
                    'key'     => '_status_akses',
                    'value'   => array( 'public', 'publik' ),
                    'compare' => 'IN',
                ),
            ),
        );

        // tambahin penyaring sesuai mode yang diklik
        if ( $pilih_mode == 'pembuat' ) {
            $aturan['meta_query'][] = array(
                'key'   => '_nama_pembuat',
                'value' => $pilih_isi,
            );
        } else {
            $aturan['tax_query'] = array(
                array(
                    'taxonomy' => $pilih_mode,
                    'field'    => 'slug',
                    'terms'    => $pilih_isi,
                ),
            );
        }

        $hasil = new WP_Query( $aturan );

        // cek apakah dokumennya ketemu
        if ( $hasil->have_posts() ) {
            echo '<ul>';
            while ( $hasil->have_posts() ) {
                $hasil->the_post();
                $doc_id     = get_the_ID();
                $penulis    = get_post_meta( $doc_id, '_nama_pembuat', true );
                $file_unduh = get_post_meta( $doc_id, '_tautan_file', true );

                // ambil nama jenis koleksi dan tahun pembuatan
                $terms_jenis = get_the_terms( $doc_id, 'jenis_koleksi' );
                $jenis       = ( ! empty( $terms_jenis ) && ! is_wp_error( $terms_jenis ) ) ? $terms_jenis[0]->name : 'Dokumen';

                $terms_tahun = get_the_terms( $doc_id, 'tahun_dokumen' );
                $tahun       = ( ! empty( $terms_tahun ) && ! is_wp_error( $terms_tahun ) ) ? $terms_tahun[0]->name : '-';

                echo '<li>';
                echo '<a href="' . esc_url( get_permalink() ) . '"><strong>' . esc_html( get_the_title() ) . '</strong></a><br>';
                echo '<small>Jenis: ' . esc_html( $jenis ) . ' | Penulis: ' . esc_html( $penulis ) . ' | Tahun: ' . esc_html( $tahun ) . '</small>';

                // tampilkan link unduh langsung jika file tersedia
                if ( ! empty( $file_unduh ) ) {
                    echo '<br><a href="' . esc_url( $file_unduh ) . '" target="_blank">Unduh Dokumen</a>';
                }

                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>Belum ada dokumen di bagian ini nih.</p>';
        }

        wp_reset_postdata();
    }

    return ob_get_clean();
}

// daftarkan shortcode [linkers_browse_repository]
add_shortcode( 'linkers_browse_repository', 'linkers_run_browse_repository' );