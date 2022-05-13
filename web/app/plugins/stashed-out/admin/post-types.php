<?php
/**
 * Custom Post Types
 *
 * For registering all custom post types
 * http://codex.wordpress.org/Function_Reference/register_post_type
 */

// Create custom post types
function create_custom_post_type() {
	// Create custom articles post type
	$labels = [
		'name'               => _x( 'Articles', 'post type general name' ),
		'singular_name'      => _x( 'Article', 'post type singular name' ),
		'menu_name'          => _x( 'Articles', 'admin menu' ),
		'name_admin_bar'     => _x( 'Article', 'add new on admin bar' ),
		'add_new'            => _x( 'Add New', 'Article' ),
		'add_new_item'       => __( 'Add New Article' ),
		'new_item'           => __( 'New Article' ),
		'edit_item'          => __( 'Edit Article' ),
		'view_item'          => __( 'View Article' ),
		'all_items'          => __( 'All Articles' ),
		'search_items'       => __( 'Search Articles' ),
		'parent_item_colon'  => __( 'Parent Articles:' ),
		'not_found'          => __( 'No Articles found.' ),
		'not_found_in_trash' => __( 'No Articles found in Trash.' ),
	];
	$args   = [
		'labels'             => $labels,
		'description'        => __( 'Articles news and reviews' ),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => [ 'slug' => 'article' ],
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => 5,
		'supports'           => [ 'comments', 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'post_tag' ],
		'show_in_rest'       => true,
	];
	register_post_type( 'post', $args );

	// Create custom opinion piece post type
	$labels = [
		'name'               => _x( 'Opinion Pieces', 'post type general name' ),
		'singular_name'      => _x( 'Opinion Piece', 'post type singular name' ),
		'menu_name'          => _x( 'Opinion Pieces', 'admin menu' ),
		'name_admin_bar'     => _x( 'Opinion Piece', 'add new on admin bar' ),
		'add_new'            => _x( 'Add New', 'Opinion Piece' ),
		'add_new_item'       => __( 'Add New Opinion Piece' ),
		'new_item'           => __( 'New Opinion Piece' ),
		'edit_item'          => __( 'Edit Opinion Piece' ),
		'view_item'          => __( 'View Opinion Piece' ),
		'all_items'          => __( 'All Opinion Pieces' ),
		'search_items'       => __( 'Search Opinion Pieces' ),
		'parent_item_colon'  => __( 'Parent Opinion Pieces:' ),
		'not_found'          => __( 'No Opinion Pieces found.' ),
		'not_found_in_trash' => __( 'No Opinion Pieces found in Trash.' ),
	];
	$args   = [
		'labels'             => $labels,
		'description'        => __( 'Opinion Pieces and reviews' ),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => [ 'slug' => 'opinionista' ],
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => 5,
		'supports'           => [ 'comments', 'title', 'author', 'excerpt', 'editor', 'thumbnail', 'custom-fields', 'revisions', 'post_tag' ],
		'show_in_rest'       => true,
	];
	register_post_type( 'opinion-piece', $args );
}
add_action( 'init', 'create_custom_post_type' );
