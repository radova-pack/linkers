<?php

// abort if this file is called directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'WPINC' ) || ! function_exists( 'add_action' ) ) {
    echo 'Hi there! I just a extended module, please back.';
    exit;
}

function linkers_run_uploads_document() {
    // variabel buat nampung pesan
    $pesan_error    = '';
    $pesan_sukses   = '';
    $judul_dokumen  = '';
    $nama_penulis   = '';
    $tahun_pub      = '';

    // Proses saat tombol upload ditekan (nama disesuaikan dengan name di button)
    if ( isset( $_POST['tombol_upload_dokumen'] ) ) {
        // cek keamanan nonce wp
        // || sama kayak or fungsinya gabungin dua kondisi jadi kalau misalnya true nilainya keduanya bakal true
        if ( ! isset( $_POST['linkers_upload_nonce'] ) || ! wp_verify_nonce( $_POST['linkers_upload_nonce'], 'linkers_aksi_upload' ) ) {
            $pesan_error = 'Keamanan form tidak valid, coba muat halaman.';
        } else {
            // nangkep input teks dari user yang di atas dah dibuat
            $judul_dokumen  = isset( $_POST['judul_dokumen'] ) ? sanitize_text_field( $_POST['judul_dokumen'] ) : '';
            $nama_penulis   = isset( $_POST['nama_penulis'] ) ? sanitize_text_field( $_POST['nama_penulis'] ) : '';
            $tahun_pub      = isset( $_POST['tahun_publikasi'] ) ? sanitize_text_field( $_POST['tahun_publikasi'] ) : '';

            // sistem meriksa kelengkapan data yang wajib diisi
            if ( empty( $judul_dokumen ) || empty( $nama_penulis ) || empty( $tahun_pub ) || empty( $_FILES['file_dokumen']['name'] ) ) {
                $pesan_error = 'Gagal upload kawan! judul dokumen, nama penulis, tahun publikasi, dan file dokumen wajib di isi semua.';
            } else {
                // ngambil info format file sama ukuran file
                // strtolower buat ngubah seluruh huruf kapital di dalam teks menjadi huruf kecil semua
                $nama_file      = $_FILES['file_dokumen']['name'];
                $ukuran_file    = $_FILES['file_dokumen']['size'];
                $ekstensi       = strtolower( pathinfo( $nama_file, PATHINFO_EXTENSION ) );
                $format_boleh   = array( 'pdf', 'doc', 'docx' );
                $maks_ukuran    = 5 * 1024 * 1024;

                if ( ! in_array( $ekstensi, $format_boleh ) ) {
                    $pesan_error = 'Format file tidak sesuai kawan! Hanya boleh upload dengan format file PDF, DOC atau DOCX.';
                // elseif buat nambahin perkondisian baru kalau kondisi if sebelumnya bernilai false
                } elseif ( $ukuran_file > $maks_ukuran ) {
                    $pesan_error = 'Ukuran file terlalu besar kawan! Maksimal ukuran file adalah 5 MB.';
                } else {
                    // ngambil file bawaan wp buat nanganin proses upload fisik
                    // require_once untuk menyisipkan data dan menjalankan kode dari file php lain dalam file yang lagi jalan (filenya cuman dimuat 1 kali)
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                    require_once ABSPATH . 'wp-admin/includes/media.php';
                    require_once ABSPATH . 'wp-admin/includes/image.php';

                    // upload file dokumen ke folder wp-content/uploads
                    $upload_hasil = wp_handle_upload( $_FILES['file_dokumen'], array( 'test_form' => false ) );

                    if ( isset( $upload_hasil['error'] ) ) {
                        $pesan_error = 'Terjadi kesalahan saat mengupload file: ' . $upload_hasil['error'];
                    } else {
                        // catat tanggal upload saat ini
                        $tanggal_upload = current_time( 'Y-m-d H:i:s' );

                        // sistem menyimpan dokumen ke CPT linkers_doc (status pending agar dicek admin dulu)
                        $data_dokumen = array(
                            'post_title'  => $judul_dokumen,
                            'post_type'   => 'linkers_doc',
                            'post_status' => 'pending',
                            'post_date'   => $tanggal_upload,
                        );

                        $doc_id = wp_insert_post( $data_dokumen );

                        if ( ! is_wp_error( $doc_id ) && $doc_id > 0 ) {
                            // simpan metadata penulis, tautan file, format file, dan status akses
                            update_post_meta( $doc_id, '_nama_pembuat', $nama_penulis );
                            update_post_meta( $doc_id, '_tautan_file', esc_url_raw( $upload_hasil['url'] ) );
                            update_post_meta( $doc_id, '_format_file', $ekstensi );
                            update_post_meta( $doc_id, '_tanggal_upload', $tanggal_upload );
                            update_post_meta( $doc_id, '_status_akses', 'public' );

                            // simpan tahun publikasi ke taxonomy tahun_dokumen
                            wp_set_object_terms( $doc_id, $tahun_pub, 'tahun_dokumen' );

                            // Tampilan pesan upload dokumen berhasil
                            $pesan_sukses = 'Upload dokumen berhasil! Dokumen "' . esc_html( $judul_dokumen ) . '" telah disimpan pada tanggal ' . esc_html( $tanggal_upload ) . ' dan menunggu persetujuan admin.';

                            // Kosongkan kembali isian form setelah berhasil
                            $judul_dokumen = '';
                            $nama_penulis  = '';
                            $tahun_pub     = '';
                        } else {
                            $pesan_error = 'Gagal menyimpan data dokumen ke database repository.';
                        }
                    }
                }
            }
        }
    }

    ob_start();
    ?>

    <!-- Tampilan Pesan Kesalahan -->
    <?php if ( ! empty( $pesan_error ) ) : ?>
        <div style="border: 1px solid red; padding: 10px; margin-bottom: 15px; color: red;">
            <strong>Peringatan:</strong> <?php echo esc_html( $pesan_error ); ?>
        </div>
    <?php endif; ?>

    <!-- Tampilan Pesan Berhasil -->
    <?php if ( ! empty( $pesan_sukses ) ) : ?>
        <div style="border: 1px solid green; padding: 10px; margin-bottom: 15px; color: green;">
            <strong>Berhasil:</strong> <?php echo esc_html( $pesan_sukses ); ?>
        </div>
    <?php endif; ?>

    <!-- Sistem Menampilkan Form untuk Upload Dokumen -->
    <form method="POST" action="" enctype="multipart/form-data">
        <?php wp_nonce_field( 'linkers_aksi_upload', 'linkers_upload_nonce' ); ?>

        <div>
            <label for="judul_dokumen">Judul Dokumen (Wajib):</label><br>
            <input type="text" id="judul_dokumen" name="judul_dokumen" value="<?php echo esc_attr( $judul_dokumen ); ?>" placeholder="Masukkan judul dokumen...">
        </div>
        <br>

        <div>
            <label for="nama_penulis">Nama Penulis (Wajib):</label><br>
            <input type="text" id="nama_penulis" name="nama_penulis" value="<?php echo esc_attr( $nama_penulis ); ?>" placeholder="Masukkan nama penulis...">
        </div>
        <br>

        <div>
            <label for="tahun_publikasi">Tahun Publikasi (Wajib):</label><br>
            <input type="date" id="tahun_publikasi" name="tahun_publikasi" value="<?php echo esc_attr( $tahun_pub ); ?>">
        </div>
        <br>

        <div>
            <label for="file_dokumen">File Dokumen (PDF, DOC, DOCX - Maks. 5MB):</label><br>
            <input type="file" id="file_dokumen" name="file_dokumen" accept=".pdf,.doc,.docx">
        </div>
        <br>

        <button type="submit" name="tombol_upload_dokumen" value="1">Upload Dokumen</button>
    </form>

    <?php
    return ob_get_clean();
}

// daftarin shortcode [linkers_uploads_document]
add_shortcode( 'linkers_uploads_document', 'linkers_run_uploads_document' );