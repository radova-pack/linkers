<?php
/**
 * Linkers Custom Meta Box Configuration
 *
 * This file handles the registration, rendering, and saving operations for custom
 * meta boxes associated with the 'linkers_doc' custom post type. It provides
 * an administrative UI to input and manage repository document metadata,
 * including creator attribution, access status, and document file links.
 *
 * @package     Linkers
 * @subpackage  Formers
 * @link        metabox-dokumen.php
 * @version     1.0.0
 * @author      Achmad Baihaqi
 * @author      Muhammad Ridho Nur Islam
 * @copyright   2026 Muhammad Ridho Nur Islam. All rights reserved.
 * @license     MIT
 *
 * NOTICE OF LICENSE:
 * Distribution, modification, or redistribution of this source code, in whole
 * or in part, is strictly prohibited without prior written authorization from
 * the copyright holder.
 */

declare(strict_types=1);

// abort if this file is called directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'WPINC' ) || ! function_exists( 'add_action' ) ) {
    echo 'Hi there! I just a extended module, please back.';
    exit;
}

/**
 * Registers the custom meta box for the linkers document post type.
 *
 * Adds the 'Details' meta box to the WordPress admin editing screen for
 * 'linkers_doc' post types, linking it to the rendering callback function.
 *
 * @since  1.0.0
 * @author Muhammad Ridho Nur Islam
 * @return void
 */
function linkers_create_meta_box() {
    add_meta_box( 'box1', 'Details', 'linkers_former_dokumen', 'linkers_doc' );
}
add_action( 'add_meta_boxes', 'linkers_create_meta_box' );

/**
 * Renders the HTML form fields for the linkers document meta box.
 *
 * Retrieves existing metadata from the database and populates the input fields
 * allowing administrators to view and edit creator names, access status,
 * and download links.
 *
 * @since  1.0.0
 * @author Muhammad Ridho Nur Islam
 *
 * @param WP_Post $post The post object currently being edited.
 * @return void
 */
function linkers_former_dokumen( $post ) {

    linkers_former_nama_pembuat( $post );
    linkers_former_status_akses( $post );
    linkers_former_tautan_file( $post );
    linkers_former_subject( $post );

}

/**
 * Renders the Creator / Author Name input field.
 *
 * @since  1.0.0
 * @author Muhammad Ridho Nur Islam
 *
 * @param  WP_Post $post The post object currently being edited.
 * @return void
 */
function linkers_former_nama_pembuat( $post ) {

    $value = get_post_meta( $post->ID, '_nama_pembuat', true );

    echo '<label for="_nama_pembuat">Nama Pembuat / Penulis</label><br>';
    echo '<input type="text" id="_nama_pembuat" name="_nama_pembuat" value="' . esc_attr( $value ) . '"><br><br>';

}

/**
 * Renders the Access Status selection input field.
 *
 * @since  1.0.0
 * @author Muhammad Ridho Nur Islam
 *
 * @param  WP_Post $post The post object currently being edited.
 * @return void
 */
function linkers_former_status_akses( $post ) {

    $value = get_post_meta( $post->ID, '_status_akses', true );

    echo '<label for="_status_akses">Status Boleh Dilihat (Akses)</label><br>';
    echo '<select id="_status_akses" name="_status_akses">';
        echo '<option value="public" '  . selected( $value, 'public', false )  . '>Publik</option>';
        echo '<option value="private" ' . selected( $value, 'private', false ) . '>Rahasia</option>';
    echo '</select><br><br>';

}

/**
 * Renders the Document Download Link input field.
 *
 * @since  1.0.0
 * @author Muhammad Ridho Nur Islam
 *
 * @param  WP_Post $post The post object currently being edited.
 * @return void
 */
function linkers_former_tautan_file( $post ) {

    $value = get_post_meta( $post->ID, '_tautan_file', true );

    echo '<label for="_tautan_file">Tautan Unduhan Dokumen</label><br>';
    echo '<input type="url" id="_tautan_file" name="_tautan_file" value="' . esc_attr( $value ) . '"><br><br>';

}

function linkers_former_subject( $post ) {

    $value = get_post_meta( $post->ID, '_subject', true );

    echo '<label for="_subject">Subject</label><br>';
    echo '<input type="text" id="_subject" name="_subject" value="' . esc_attr( $value ) . '"><br><br>';

}

/**
 * Sanitizes and saves the custom metadata when a linkers document is saved.
 *
 * Intercepts the post saving process to securely sanitize and persist custom
 * input values into the WordPress postmeta table.
 *
 * @since  1.0.0
 * @author Muhammad Ridho Nur Islam
 *
 * @param int $post_id The ID of the post being saved.
 * @return void
 */
function linkers_update_meta_box( $post_id ) {

    if ( array_key_exists( '_nama_pembuat', $_POST ) ) {
        $value = sanitize_text_field( $_POST['_nama_pembuat'] );
        update_post_meta( $post_id, '_nama_pembuat', $value );
    }

    if ( array_key_exists( '_status_akses', $_POST ) ) {
        $value = sanitize_text_field( $_POST['_status_akses'] );
        update_post_meta( $post_id, '_status_akses', $value );
    }

    if ( array_key_exists( '_tautan_file', $_POST ) ) {
        $value = esc_url_raw( $_POST['_tautan_file'] );
        update_post_meta( $post_id, '_tautan_file', $value );
    }

    if ( array_key_exists( '_subject', $_POST ) ) {
        $value = esc_url_raw( $_POST['_subject'] );
        update_post_meta( $post_id, '_subject', $value );
    }
}
add_action( 'save_post', 'linkers_update_meta_box' );