<?php

/*
Plugin Name: Leach! Post Thumbnail As URL
Plugin URI: https://github.com/LeachBook/leach-post-thumbnail-as-url
Description: Post Thumbnail As not image, but URL
Version: 1.0.0
Author: niwatolli3 <niwatolli3@gmail.com>
Author URI: https://blog.leach.tokyo/
License: GPL2
*/

/*  Copyright 2018 niwatolli3  (email : niwatolli3@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

add_action('add_meta_boxes', 'adding_custom_meta_boxes', 10, 2);

add_filter('post_thumbnail_html', 'my_post_image_html', 10, 3);

function my_post_image_html( $html, $post_id, $post_image_id ) {
    $html = '<a href="' . get_permalink( $post_id ) . '" title="' . esc_attr( get_the_title( $post_id ) ) . '">' . $html . '</a>';
    return $html;
}

function adding_custom_meta_boxes( $post_type, $post ) {
    add_meta_box(
        'thumbnail-url-box',
        __( 'Thumbnail URL' ),
        'render_thumbnail_url_box',
        'post',
        'normal',
        'default'
    );
}

function render_thumbnail_url_box($post){
    $thumbnail_url = get_post_meta( $post->ID, '_thumbnail_url', true );

    // Use nonce for verification
    wp_nonce_field( plugin_basename( __FILE__ ), 'blc_noncename' );

    echo '<input type="text" id="thumbnail_url"
    name="thumbnail_url"
    placeholder="put your thumbnail URL here"
    value="' . sanitize_text_field( $thumbnail_url ) . '"
    size="100%" />';
}

// https://stackoverflow.com/questions/7552238/save-data-of-custom-metabox-in-wordpress
function blc_save_postdata($post_id){

    // Verify this came from the our screen and with proper authorization,
    // because save_post can be triggered at other times
    if ( !wp_verify_nonce( $_POST['blc_noncename'], plugin_basename(__FILE__) )) {
        return $post_id;
    }

    // Verify if this is an auto save routine. If it is our form has not been submitted, so we dont want
    // to do anything
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
        return $post_id;


    // Check permissions to edit pages and/or posts
    if ( 'page' == $_POST['post_type'] ||  'post' == $_POST['post_type']) {
        if ( !current_user_can( 'edit_page', $post_id ) || !current_user_can( 'edit_post', $post_id ))
            return $post_id;
    }

    // OK, we're authenticated: we need to find and save the data
    $blc = $_POST['thumbnail_url'];

    // save data in INVISIBLE custom field (note the "_" prefixing the custom fields' name
    update_post_meta($post_id, '_thumbnail_url', $blc);

}
// On post save, save plugin's data
add_action('save_post', 'blc_save_postdata', 10);

// https://wordpress.stackexchange.com/questions/134014/how-do-i-change-modify-the-post-thumbnail-html-output
function modify_post_thumbnail_html($html, $post_id, $post_thumbnail_id, $size, $attr) {
    $id = get_post_thumbnail_id(); // gets the id of the current post_thumbnail (in the loop)
    $src = wp_get_attachment_image_src($id, $size); // gets the image url specific to the passed in size (aka. custom image size)
    $alt = get_the_title($id); // gets the post thumbnail title
//    $class = $attr['class']; // gets classes passed to the post thumbnail, defined here for easier function access

    // path of thumbnail URL
    $thumbnail_url = "";
    if($src[0] != "") {
        $thumbnail_url = $src[0];
    }
    $meta_url = get_post_meta($post_id, '_thumbnail_url', true);
    if(gettype($meta_url) != "unknown") {
        $thumbnail_url = $meta_url;
    }

    // Check to see if a 'retina' class exists in the array when calling "the_post_thumbnail()", if so output different <img/> html
    if ($thumbnail_url == "") {
        return "";
    }
    if (strpos($class, 'retina') !== false) {
        $html = '<img src="" alt="" data-src="' . $thumbnail_url . '" data-alt="' . $alt . '" class="' . $class . '" />';
    } else {
        $html = '<img src="' . $thumbnail_url. '" alt="' . $alt . /*'" class="' . $class .*/ '" />';
    }

    return $html;
}
add_filter('post_thumbnail_html', 'modify_post_thumbnail_html', 99, 5);
