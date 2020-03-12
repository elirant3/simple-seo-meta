<?php

/*
Plugin Name: Simple Seo Metadata
Plugin URI: https://github.com/elirant3/simple-seo-meta
Description: The plugin add meta box containing the inputs SEO title, Meta keywords, Meta description to all type of posts. You can specify different values to posts.
Version: 1.0
Author: Eliran Biton
Author URI: https://github.com/elirant3
License: A "Slug" license name e.g. GPL2
Text Domain: simple-seo-meta
*/

/**
 * Add the meta fields to all post types.
 */

if ( ! function_exists( 'sism_add_seo_meta_box' ) ) {
	add_action( 'add_meta_boxes', 'sism_add_seo_meta_box' );
	function sism_add_seo_meta_box() {
		$post_types = get_post_types( [ 'public' => true ] );
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'seometa_meta_box',
				'SEO Meta Box',
				'addSeoMetaFields',
				$post_type,
				'normal',
				'high'
			);
		}
	}
}

/**
 * Meta fields callback.
 */
if ( ! function_exists( 'sism_addSeoMetaFields' ) ) {
	function sism_addSeoMetaFields() {
		global $post;
		$data['seoTitle']           = '';
		$data['seoSlug']            = '';
		$data['seoMetaDescription'] = '';
		$data['seoMetaKeywords']    = '';
		if ( is_object( $post ) && isset( $post->ID ) ) {
			$postID                     = esc_attr( $post->ID );
			$data['seoTitle']           = get_post_meta( $postID, 'seoTitle', true );
			$data['seoSlug']            = get_post_meta( $postID, 'seoSlug', true );
			$data['seoMetaDescription'] = get_post_meta( $postID, 'seoMetaDescription', true );
			$data['seoMetaKeywords']    = get_post_meta( $postID, 'seoMetaKeywords', true );
		}

		wp_nonce_field( 'seo_meta_tags', 'seo_meta_tags' );
		?>
        <div class="form-group">
            <label for="seoTitle"><?= _x( 'SEO Title', 'simple-seo-meta' ) ?></label><br>
            <input type="text" name="seoTitle" id="seoTitle" value="<?= $data['seoTitle']; ?>">
        </div>

        <div class="form-group">
            <label for="seoMetaKeywords"><?= _x( 'Meta keywords', 'simple-seo-meta' ) ?></label><br>
            <textarea name="seoMetaKeywords"
                      id="seoMetaKeywords"><?= $data['seoMetaKeywords']; ?></textarea>
        </div>

        <div class="form-group">
            <label for="seoMetaDescription"><?= _x( 'Meta Description', 'simple-seo-meta' ) ?></label><br>
            <textarea name="seoMetaDescription"
                      id="seoMetaDescription"><?= $data['seoMetaDescription']; ?></textarea>
        </div>
		<?php
	}
}

/**
 * Save Meta fields data.
 */
if ( ! function_exists( 'sism_saveSeoMetaBox' ) ) {
	add_action( 'save_post', 'sism_saveSeoMetaBox' );
	function sism_saveSeoMetaBox( $post_id ) {
		$data = [];

		if ( ! isset( $_POST['seo_meta_tags'] ) ) {
			return;
		}

		/*verify nonce*/
		if ( ! wp_verify_nonce( $_POST['seo_meta_tags'], 'seo_meta_tags' ) ) {
			return;
		}

		/*verify its not autosave*/
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		/*validate user permission*/
		if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		if ( isset( $_POST['seoTitle'] ) && ! empty( $_POST['seoTitle'] ) ) {
			$data['seoTitle'] = sanitize_text_field( $_POST['seoTitle'] );
		} else {
			$data['seoTitle'] = '';
		}

		if ( isset( $_POST['seoSlug'] ) && ! empty( $_POST['seoSlug'] ) ) {
			$data['seoSlug'] = sanitize_text_field( $_POST['seoSlug'] );
		} else {
			$data['seoSlug'] = '';
		}

		if ( isset( $_POST['seoMetaDescription'] ) && ! empty( $_POST['seoMetaDescription'] ) ) {
			$data['seoMetaDescription'] = sanitize_text_field( $_POST['seoMetaDescription'] );
		} else {
			$data['seoMetaDescription'] = '';
		}

		if ( isset( $_POST['seoMetaKeywords'] ) && ! empty( $_POST['seoMetaKeywords'] ) ) {
			$data['seoMetaKeywords'] = sanitize_text_field( $_POST['seoMetaKeywords'] );
		} else {
			$data['seoMetaKeywords'] = '';
		}

		/*string the seo meta as post meta*/
		update_post_meta( $post_id, 'seoTitle', $data['seoTitle'] );
		update_post_meta( $post_id, 'seoSlug', $data['seoSlug'] );
		update_post_meta( $post_id, 'seoMetaDescription', $data['seoMetaDescription'] );
		update_post_meta( $post_id, 'seoMetaKeywords', $data['seoMetaKeywords'] );

	}
}

if ( ! function_exists( 'sism_showSeoMetaTags' ) ) {
	add_filter( 'wp_title', 'sism_showSeoMetaTags' );
	function sism_showSeoMetaTags( $title ) {
		global $post;
		$title = _x( get_bloginfo( 'name' ) . ' – ' . get_bloginfo( 'description' ), 'simple-seo-meta' );
		if ( is_object( $post ) && isset( $post->ID ) ) {
			$seoTitle = get_post_meta( $post->ID, 'seoTitle', true );
			if ( ! empty( $seoTitle ) ) {
				$title = _x( $seoTitle, 'simple-seo-meta' );
			}
		}

		return sprintf( "%s", $title );
	}
}

if ( ! function_exists( 'sism_showSeoMetaDescription' ) ) {
	add_action( 'wp_head', 'sism_showSeoMetaDescription' );
	function sism_showSeoMetaDescription() {
		global $post;
		$description     = '';
		$seoMetaKeywords = '';
		if ( is_object( $post ) && isset( $post->ID ) ) {
			$seoMetaDescription = get_post_meta( $post->ID, 'seoMetaDescription', true );
			$seoMetaKeywords    = get_post_meta( $post->ID, 'seoMetaKeywords', true );
			if ( ! empty( $seoMetaDescription ) ) {
				$description = $seoMetaDescription;
			}
		}

		/* translators: %s: "$description" */
		$description = sprintf( _x( '%s', 'simple-seo-meta' ), $description );
		echo '<meta name="description" content="' . esc_attr( $description ) . '">';
		echo '<meta name="keywords" content="' . esc_attr( $seoMetaKeywords ) . '">';
	}
}

add_action( 'admin_head', function () {
	?>
    <style>
        .form-group {
            margin-bottom: 15px;
        }

        .form-group input, .form-group textarea {
            min-height: 40px;
            width: 100%;
        }

        .form-group textarea {
            min-height: 80px;
        }
    </style>
	<?php
} );
