<?php

// abort if this file is called directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'WPINC' ) || ! function_exists( 'add_action' ) ) {
    echo 'Hi there! I just a extended module, please back.';
    exit;
}

function linkers_run_search_repository() {
    // tangkap input kata kunci dan filter dari URL (method GET)
    $kata_kunci   = isset( $_GET['kata_kunci'] ) ? sanitize_text_field( $_GET['kata_kunci'] ) : '';
    $filter_jenis = isset( $_GET['filter_jenis'] ) ? sanitize_text_field( $_GET['filter_jenis'] ) : '';
    $filter_topik = isset( $_GET['filter_topik'] ) ? sanitize_text_field( $_GET['filter_topik'] ) : '';
    $filter_tahun = isset( $_GET['filter_tahun'] ) ? sanitize_text_field( $_GET['filter_tahun'] ) : '';

    // ambil daftar pilihan untuk dropdown filter dari taksonomi
    $daftar_jenis = get_terms( array(
        'taxonomy'   => 'jenis_koleksi',
        'hide_empty' => false,
    ) );

    $daftar_topik = get_terms( array(
        'taxonomy'   => 'topik_dokumen',
        'hide_empty' => false,
    ) );

    $daftar_tahun = get_terms( array(
        'taxonomy'   => 'tahun_dokumen',
        'hide_empty' => false,
    ) );

    ob_start();
    ?>

    <!-- Form Pencarian dan Penyaringan Dokumen -->
    <form method="GET" action="">
        <div>
            <label for="kata_kunci">Kata Kunci (Judul / Isi):</label><br>
            <input type="text" id="kata_kunci" name="kata_kunci" value="<?php echo esc_attr( $kata_kunci ); ?>" placeholder="Ketik judul atau kata kunci...">
        </div>
        <br>

        <div>
            <label for="filter_jenis">Jenis Koleksi:</label><br>
            <select id="filter_jenis" name="filter_jenis">
                <option value="">-- Semua Jenis --</option>
                <?php if ( ! empty( $daftar_jenis ) && ! is_wp_error( $daftar_jenis ) ) : ?>
                    <?php foreach ( $daftar_jenis as $jenis ) : ?>
                        <option value="<?php echo esc_attr( $jenis->slug ); ?>" <?php selected( $filter_jenis, $jenis->slug ); ?>>
                            <?php echo esc_html( $jenis->name ); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <br>

        <div>
            <label for="filter_topik">Topik / Subjek:</label><br>
            <select id="filter_topik" name="filter_topik">
                <option value="">-- Semua Topik --</option>
                <?php if ( ! empty( $daftar_topik ) && ! is_wp_error( $daftar_topik ) ) : ?>
                    <?php foreach ( $daftar_topik as $topik ) : ?>
                        <option value="<?php echo esc_attr( $topik->slug ); ?>" <?php selected( $filter_topik, $topik->slug ); ?>>
                            <?php echo esc_html( $topik->name ); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <br>

        <div>
            <label for="filter_tahun">Tahun Pembuatan:</label><br>
            <select id="filter_tahun" name="filter_tahun">
                <option value="">-- Semua Tahun --</option>
                <?php if ( ! empty( $daftar_tahun ) && ! is_wp_error( $daftar_tahun ) ) : ?>
                    <?php foreach ( $daftar_tahun as $tahun ) : ?>
                        <option value="<?php echo esc_attr( $tahun->slug ); ?>" <?php selected( $filter_tahun, $tahun->slug ); ?>>
                            <?php echo esc_html( $tahun->name ); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <br>

        <button type="submit">Cari Dokumen</button>
        <a href="<?php echo esc_url( get_permalink() ); ?>">Reset</a>
    </form>

    <hr>

    <?php
    // siapkan aturan pencarian dasar (hanya dokumen publish dan berstatus publik)
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

    // masukkan kata kunci jika user mengetik pencarian
    if ( ! empty( $kata_kunci ) ) {
        $aturan['s'] = $kata_kunci;
    }

    // siapkan penampung filter taksonomi
    $tax_query = array( 'relation' => 'AND' );

    if ( ! empty( $filter_jenis ) ) {
        $tax_query[] = array(
            'taxonomy' => 'jenis_koleksi',
            'field'    => 'slug',
            'terms'    => $filter_jenis,
        );
    }

    if ( ! empty( $filter_topik ) ) {
        $tax_query[] = array(
            'taxonomy' => 'topik_dokumen',
            'field'    => 'slug',
            'terms'    => $filter_topik,
        );
    }

    if ( ! empty( $filter_tahun ) ) {
        $tax_query[] = array(
            'taxonomy' => 'tahun_dokumen',
            'field'    => 'slug',
            'terms'    => $filter_tahun,
        );
    }

    // pasang tax_query ke aturan pencarian jika ada filter yang dipilih
    if ( count( $tax_query ) > 1 ) {
        $aturan['tax_query'] = $tax_query;
    }

    $hasil_cari = new WP_Query( $aturan );

    echo '<h3>Hasil Pencarian Dokumen:</h3>';

    // decision: cek apakah dokumen ditemukan
    if ( $hasil_cari->have_posts() ) {
        // cabang YA: tampilkan daftar hasil pencarian
        echo '<ul>';
        while ( $hasil_cari->have_posts() ) {
            $hasil_cari->the_post();
            $doc_id     = get_the_ID();
            $penulis    = get_post_meta( $doc_id, '_nama_pembuat', true );
            $file_unduh = get_post_meta( $doc_id, '_tautan_file', true );

            // ambil nama jenis koleksi
            $terms_jenis = get_the_terms( $doc_id, 'jenis_koleksi' );
            $jenis       = ( ! empty( $terms_jenis ) && ! is_wp_error( $terms_jenis ) ) ? $terms_jenis[0]->name : 'Dokumen';

            // ambil nama topik atau subjek
            $terms_subjek = get_the_terms( $doc_id, 'topik_dokumen' );
            if ( ! empty( $terms_subjek ) && ! is_wp_error( $terms_subjek ) ) {
                $daftar_subjek = wp_list_pluck( $terms_subjek, 'name' );
                $subjek        = implode( ', ', $daftar_subjek );
            } else {
                $subjek = '-';
            }

            // ambil tahun pembuatan
            $terms_tahun = get_the_terms( $doc_id, 'tahun_dokumen' );
            $tahun       = ( ! empty( $terms_tahun ) && ! is_wp_error( $terms_tahun ) ) ? $terms_tahun[0]->name : '-';

            echo '<li>';
            echo '<a href="' . esc_url( get_permalink() ) . '"><strong>' . esc_html( get_the_title() ) . '</strong></a><br>';
            echo '<small>Jenis: ' . esc_html( $jenis ) . ' | Subjek: ' . esc_html( $subjek ) . ' | Penulis: ' . esc_html( $penulis ) . ' | Tahun: ' . esc_html( $tahun ) . '</small>';

            // tampilkan tautan unduh file jika tersedia
            if ( ! empty( $file_unduh ) ) {
                echo '<br><a href="' . esc_url( $file_unduh ) . '" target="_blank">Unduh Dokumen</a>';
            }

            echo '</li>';
        }
        echo '</ul>';
    } else {
        // cabang TIDAK: tampilkan pesan tidak ditemukan
        echo '<p>Dokumen yang kamu cari nggak ketemu nih.</p>';
    }

    wp_reset_postdata();
    return ob_get_clean();
}

// daftarkan shortcode [linkers_search_repository]
add_shortcode( 'linkers_search_repository', 'linkers_run_search_repository' );