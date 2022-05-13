<?php
/**
 * Custom Taxonomies
 *
 * For registering all custom taxonomies
 * http://codex.wordpress.org/Function_Reference/register_post_type
 */

// Create taxonomies, and assign them to post types
function create_custom_taxonomy() {
	// Create custom article category taxonomy
	$labels = [
		'name'                       => __( 'Sections', 'taxonomy general name' ),
		'singular_name'              => __( 'Section', 'taxonomy singular name' ),
		'search_items'               => __( 'Search Sections' ),
		'popular_items'              => __( 'Popular Sections' ),
		'all_items'                  => __( 'All Sections' ),
		'edit_item'                  => __( 'Edit Section' ),
		'update_item'                => __( 'Update Section' ),
		'add_new_item'               => __( 'Add Section' ),
		'new_item_name'              => __( 'New Section' ),
		'separate_items_with_commas' => __( 'Separate sections with commas' ),
		'add_or_remove_items'        => __( 'Add or remove sections' ),
		'choose_from_most_used'      => __( 'Choose from most used sections' ),
		'menu_name'                  => __( 'Sections' ),
	];
	register_taxonomy(
		'category',
		'post',
		[
			'hierarchical' => true,
			'labels'       => $labels,
			'show_ui'      => true,
			'query_var'    => true,
			'rewrite'      => [ 'slug' => 'category' ],
			'show_in_rest' => true,
		]
	);

	$labels = [
		'name'                       => __( 'Flags', 'taxonomy general name' ),
		'singular_name'              => __( 'Flag', 'taxonomy singular name' ),
		'search_items'               => __( 'Search Flags' ),
		'popular_items'              => __( 'Popular Flags' ),
		'all_items'                  => __( 'All Flags' ),
		'edit_item'                  => __( 'Edit Flag' ),
		'update_item'                => __( 'Update Flag' ),
		'add_new_item'               => __( 'Add Flag' ),
		'new_item_name'              => __( 'New Flag' ),
		'separate_items_with_commas' => __( 'Separate Flags with commas' ),
		'add_or_remove_items'        => __( 'Add or remove Flags' ),
		'choose_from_most_used'      => __( 'Choose from most used Flags' ),
		'menu_name'                  => __( 'Flags' ),
	];
	register_taxonomy(
		'flag',
		[ 'post', 'opinion-piece' ],
		[
			'hierarchical' => true,
			'labels'       => $labels,
			'show_ui'      => true,
			'query_var'    => true,
			'rewrite'      => [ 'slug' => 'flag' ],
			'show_in_rest' => true,
		]
	);

	// Create custom article tag taxonomy
    $labels = getLabels();
    register_taxonomy(
		'post_tag',
		'post',
		[
			'hierarchical' => false,
			'labels'       => $labels,
			'show_ui'      => true,
			'query_var'    => true,
			'rewrite'      => [ 'slug' => 'post_tag' ],
			'show_in_rest' => true,
		]
	);

	// Create custom opinion piece tag taxonomy
    $labels = getLabels();
	register_taxonomy(
		'opinion-piece-tag',
		'opinion-piece',
		[
			'hierarchical' => false,
			'labels'       => $labels,
			'show_ui'      => true,
			'query_var'    => true,
			'rewrite'      => array( 'slug' => 'opinion-piece-tag' ),
			'show_in_rest' => true,
		]
	);
}

/**
 * @return array
 */
function getLabels(): array
{
    return [
        'name' => __('Tags', 'taxonomy general name'),
        'singular_name' => __('Tag', 'taxonomy singular name'),
        'search_items' => __('Search Tags'),
        'popular_items' => __('Popular Tags'),
        'all_items' => __('All Tags'),
        'edit_item' => __('Edit Tag'),
        'update_item' => __('Update Tag'),
        'add_new_item' => __('Add Tag'),
        'new_item_name' => __('New Tag'),
        'separate_items_with_commas' => __('Separate tags with commas'),
        'add_or_remove_items' => __('Add or remove tags'),
        'choose_from_most_used' => __('Choose from most used tags'),
        'menu_name' => __('Tags'),
    ];
}
add_action( 'init', 'create_custom_taxonomy', 0 );

function custom_fields_for_articles() {
	$user = wp_get_current_user();
	if ( isset( $user->roles[0] ) ) {
		if ( in_array( $user->roles[0], [ 'editor', 'administrator' ] ) ) {
			add_filter( 'views_edit-article', 'meta_articles_content_type_view', 10, 1 );
			function meta_articles_content_type_view( $views ) {
				$flag = ($_REQUEST['flag'] ?? '');
				return build_flag_content_type_views( $views, 'post', $flag );
			}

			add_filter( 'views_edit-opinion-piece', 'meta_opinion_content_type_view', 10, 1 );
			function meta_opinion_content_type_view( $views ) {
				$flag = ($_REQUEST['flag'] ?? '');
				return build_flag_content_type_views( $views, 'opinion-piece', $flag );
			}

			function custom_get_term_post_count_by_type( $term, $taxonomy, $type ) {
				$args       = [
					'posts_per_page' => -1,
					'post_status'    => [ 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'rejected' ],
					'post_type'      => $type,
					'tax_query'      => [
						[
							'taxonomy' => $taxonomy,
							'field'    => 'slug',
							'terms'    => $term,
						],
					],
					'fields'         => 'ids',
				];
				$term_posts = get_posts( $args );
				return count( $term_posts );
			}

			add_action( 'article_tag_add_form_fields', 'add_featured_tag_field', 10, 2 );
			add_action( 'opinion-piece_tag_add_form_fields', 'add_featured_tag_field', 10, 2 );
			function add_featured_tag_field( $taxonomy ) {
				global $feature;
				?>
				<div class="form-field term-group">
					<label for="featuret-group"><?php _e( 'Featured Tags' ); ?></label>
					<input type="checkbox" class="content_type" name="is_feature" value="feature"/>
				</div>
				<?php
			}

			add_action( 'created_article_tag', 'save_featured_tag_meta', 10, 2 );
			add_action( 'created_opinion-piece_tag', 'save_featured_tag_meta', 10, 2 );
			function save_featured_tag_meta( $term_id ) {
				if ( isset( $_POST['is_feature'] ) ) {
					$group = sanitize_title( $_POST['is_feature'] );
					add_term_meta( $term_id, 'is_feature', $group );
				}
			}

			add_action( 'article_tag_edit_form_fields', 'edit_featured_tag_field', 10, 2 );
			add_action( 'opinion-piece_tag_edit_form_fields', 'edit_featured_tag_field', 10, 2 );
			function edit_featured_tag_field( $term, $taxonomy ) {
				global $feature;
				$feature = get_term_meta( $term->term_id, 'is_feature' );
				?>
				<tr class="form-field term-group-wrap">
					<th scope="row"><label for="feature-group"><?php _e( 'Feature Tags' ); ?></label></th>
					<td>
					<input type="checkbox" class="content_type" name="is_feature" value="feature" <?php echo ( isset( $feature[0] ) == 'feature' ? 'checked' : '' ); ?> /><br>
					</td>
				</tr>
				<?php
			}

			add_action( 'edited_article_tag', 'update_featured_tag_field_meta', 10, 2 );
			add_action( 'edited_opinion-piece_tag', 'update_featured_tag_field_meta', 10, 2 );
			function update_featured_tag_field_meta( $term_id ) {
				$group = sanitize_title( $_POST['is_feature'] );
				update_term_meta( $term_id, 'is_feature', $group );
			}

			add_filter( 'manage_edit-article_tag_columns', 'manage_edit_article_featured_tag_columns' );
			add_filter( 'manage_edit-opinion-piece_tag_columns', 'manage_edit_article_featured_tag_columns' );
			function manage_edit_article_featured_tag_columns( $tag_columns ) {
				$tag_columns['feature'] = 'Featured';
				return $tag_columns;
			}

			add_filter( 'manage_article_tag_custom_column', 'manage_article_featured_tag_custom_columns', 10, 3 );
			add_filter( 'manage_opinion-piece_tag_custom_column', 'manage_article_featured_tag_custom_columns', 10, 3 );
			function manage_article_featured_tag_custom_columns( $out, $tag_columns, $term ) {
				global $feature;
				$tag_filter = ( ! empty( $_GET['is_feature'] ) ) ? $tag_filter = $_GET['is_feature'] : '';
				$feature    = get_term_meta( $term, 'is_feature' );
				switch ( $tag_columns ) {
					case 'feature':
						$out .= '<input type="checkbox" class="content_type" data-post-id="' . $term . '" data-tag-filter="' . $tag_filter . '" data-content-type="is_feature_tag" name="is_feature_tag" value="feature" ' . ( isset( $feature[0] ) == 'feature' ? 'checked' : '' ) . '/><br>';
				}
				return $out;
			}

			add_filter( 'bulk_actions-edit-article', 'register_stashed_bulk_actions' );
			add_filter( 'bulk_actions-edit-opinion-piece', 'register_stashed_bulk_actions' );
			function register_stashed_bulk_actions( $bulk_actions ) {
				if ( isset( $_GET['flag'] ) ) {
					if ( $_GET['flag'] == 'featured' ) {
						$bulk_actions['unfeature'] = __( 'Remove as Featured' );
					} elseif ( $_GET['flag'] == 'sponsor' ) {
						$bulk_actions['unsponsor'] = __( 'Remove as Sponsored' );
					}
				} else {
					$bulk_actions['feature']         = __( 'Mark as Featured' );
					$bulk_actions['sponsor']         = __( 'Mark as Sponsored' );
				}
				return $bulk_actions;
			}

			add_filter( 'handle_bulk_actions-edit-article', 'stashed_bulk_action_handler', 10, 3 );
			add_filter( 'handle_bulk_actions-edit-opinion-piece', 'stashed_bulk_action_handler', 10, 3 );
			function stashed_bulk_action_handler( $redirect_to, $do_action, $post_ids ) {
				if ( ! in_array( $do_action, [ 'feature', 'unfeature', 'sponsor', 'unsponsor' ] ) ) {
					return $redirect_to;
				}
				foreach ( $post_ids as $post_id ) {
					if ( $do_action == 'unfeature' ) {
						wp_remove_object_terms( $post_id, 'featured', 'flag' );
					}
					if ( $do_action == 'sponsor' ) {
						wp_set_object_terms( $post_id, 'sponsor', 'flag' );
					}
					if ( $do_action == 'unsponsor' ) {
						wp_remove_object_terms( $post_id, 'sponsor', 'flag' );
					}
				}
				return $redirect_to;
			}

			add_filter( 'bulk_actions-edit-article_tag', 'register_stashed_bulk_actions_tag' );
			add_filter( 'bulk_actions-edit-opinion-piece_tag', 'register_stashed_bulk_actions_tag' );
			function register_stashed_bulk_actions_tag( $bulk_actions ) {
				if ( isset( $_GET['is_feature'] ) ) {
					$bulk_actions['unfeature'] = __( 'Unfeature', 'Unfeature' );
				} else {
					$bulk_actions['feature'] = __( 'Feature', 'Feature' );
				}
				return $bulk_actions;
			}

			add_filter( 'handle_bulk_actions-edit-article_tag', 'stashed_bulk_action_handler_tag', 10, 3 );
			add_filter( 'handle_bulk_actions-edit-opinion-piece_tag', 'stashed_bulk_action_handler_tag', 10, 3 );
			function stashed_bulk_action_handler_tag( $redirect_to, $do_action, $post_ids ) {
				if ( $do_action !== 'feature' && $do_action !== 'unfeature' ) {
					return $redirect_to;
				}
				foreach ( $post_ids as $post_id ) {
					if ( $do_action == 'feature' ) {
						update_term_meta( $post_id, 'is_feature', 'feature' );
					}
					if ( $do_action == 'unfeature' ) {
						delete_term_meta( $post_id, 'is_feature' );
					}
				}
				return $redirect_to;
			}

			add_action( 'after-article_tag-table', 'display_tag_filter' );
			add_action( 'after-opinion-piece_tag-table', 'display_tag_filter' );
			function display_tag_filter( $taxonomy ) {
				echo "<div id='featured-tag-bottom-section' style='font-size: 14px;'><a href='" . admin_url( 'edit-tags.php?taxonomy=post_tag&post_type=post' ) . "'>All</a>  |
               <a href='" . admin_url( 'edit-tags.php?taxonomy=post_tag&post_type=post&is_feature=feature' ) . "'>Featured Tags</a></div>";
				echo "<div id='featured-tag-top-section' style='display:none; font-size: 14px;'><a href='" . admin_url( 'edit-tags.php?taxonomy=post_tag&post_type=post' ) . "'>All</a>  |
               <a href='" . admin_url( 'edit-tags.php?taxonomy=post_tag&post_type=post&is_feature=feature' ) . "'>Featured Tags</a></div>";
			}

			add_action( 'after-article_tag-table', 'display_tag_filter' );
			add_action( 'after-opinion-piece_tag-table', 'display_tag_filter_opinion_pieces' );
			function display_tag_filter_opinion_pieces( $taxonomy ) {
				echo "<div id='featured-tag-bottom-section' style='font-size: 14px;'><a href='" . admin_url( 'edit-tags.php?taxonomy=opinion-piece_tag&post_type=opinion-piece' ) . "'>All</a>  |
               <a href='" . admin_url( 'edit-tags.php?taxonomy=opinion-piece_tag&post_type=opinion-piece&is_feature=feature' ) . "'>Featured Tags</a></div>";
				echo "<div id='featured-tag-top-section' style='display:none; font-size: 14px;'><a href='" . admin_url( 'edit-tags.php?taxonomy=opinion-piece_tag&post_type=opinion-piece' ) . "'>All</a>  |
               <a href='" . admin_url( 'edit-tags.php?taxonomy=opinion-piece_tag&post_type=opinion-piece&is_feature=feature' ) . "'>Featured Tags</a></div>";
			}

			add_filter( 'get_terms_args', 'display_tag_filter_list' );
			function display_tag_filter_list( $args ) {
				if ( isset( $_GET['is_feature'] ) != '' ) {
					$args['meta_key']   = 'is_feature';
					$args['meta_value'] = $_GET['is_feature'];
				}
				return $args;
			}

			add_action( 'admin_print_scripts', 'admin_tag_filter_redirect_url_script' );
			function admin_tag_filter_redirect_url_script() {
				echo "<script type='text/javascript'>";
				echo 'var tagFilterRedirectUrl = ' . wp_json_encode( admin_url( 'edit-tags.php?taxonomy=post_tag&post_type=post&is_feature=feature' ) ) . ';';
				echo '</script>';
			}

			add_action( 'admin_print_scripts', 'admin_tag_filter_redirect_url_script_opinion_piece' );
			function admin_tag_filter_redirect_url_script_opinion_piece() {
				echo "<script type='text/javascript'>";
				echo 'var tagFilterRedirectUrl = ' . wp_json_encode( admin_url( 'edit-tags.php?taxonomy=opinion_piece_tag&post_type=opinion_piece&is_feature=feature' ) ) . ';';
				echo '</script>';
			}

			/*Custom Filter based on Section Taxonomy Values */
			add_action( 'restrict_manage_posts', 'custom_filter_post_type_by_taxonomy' );
			function custom_filter_post_type_by_taxonomy() {
				global $type_now;
				$post_type = 'post';
				$taxonomy  = 'category';
				if ( $type_now === $post_type ) {
					$selected         = $_GET[$taxonomy] ?? '';
					$section_taxonomy = get_taxonomy( $taxonomy );
					wp_dropdown_categories(
						[
							'show_option_all' => __( "Show All {$section_taxonomy->label}" ),
							'taxonomy'        => $taxonomy,
							'name'            => $taxonomy,
							'orderby'         => 'name',
							'selected'        => $selected,
							'show_count'      => true,
							'hide_empty'      => true,
						]
					);
				}
			}

			add_filter( 'parse_query', 'custom_convert_id_to_term_query' );
			function custom_convert_id_to_term_query( $query ) {
				global $page_now;
				$post_type = 'post';
				$taxonomy  = 'section';
				$q_vars    = &$query->query_vars;
				if ( $page_now == 'edit.php' && isset( $q_vars['post_type'] ) && $q_vars['post_type'] == $post_type && isset( $q_vars[ $taxonomy ] ) && is_numeric( $q_vars[ $taxonomy ] ) && $q_vars[ $taxonomy ] != 0 ) {
					$term                = get_term_by( 'id', $q_vars[ $taxonomy ], $taxonomy );
					$q_vars[ $taxonomy ] = $term->slug;
				}
			}
			/*End of Custom Filter based on Section Taxonomy Values */

			/*Start of Adding Custom taxonomy Images*/
			$section_taxonomy = 'category';
			add_action( $section_taxonomy . '_add_form_fields', 'add_category_taxonomy_image' );
			add_action( $section_taxonomy . '_edit_form_fields', 'edit_category_taxonomy_image' );

			// Function to add category/taxonomy image
			function add_category_taxonomy_image( $taxonomy ) {
				?>
				<div class="form-field">
					<label for="tag-image">Image</label>
					<input type="text" name="tag-image" id="tag-image" value="" />
					<p class="description">Click on the text box to add taxonomy/category image.</p>
				</div>
				<?php
				after_script_function();
			}

			// Function to edit category/taxonomy image
			function edit_category_taxonomy_image( $taxonomy ) {
				?>
				<tr class="form-field">
					<th scope="row" valign="top"><label for="tag-image">Image</label></th>
					<td>
					<div class="ajax-loading-image"></div>
					<?php
					if ( get_option( '_category_image' . $taxonomy->term_id ) != '' ) {
						?>
						<img class="taxonomy-image" src="<?php echo get_option( '_category_image' . $taxonomy->term_id ); ?>" width="100"  height="100"/>
						<?php
					}
					?>
					<br />
					<input type="text" name="tag-image" id="tag-image" value="<?php echo get_option( '_category_image' . $taxonomy->term_id ); ?>" /><p class="description">Click on the text box to add taxonomy/category image.</p>
					<span id="delete-link"><a href="#" class="eti_remove_image_button" data-taxonomy-id="<?php echo $taxonomy->term_id; ?>"><?php echo 'Remove image'; ?></a></span>
					</td>
				</tr>
				<?php
				after_script_function();
			}

			function after_script_function() {
				?>
				<script type="text/javascript">
					jQuery(document).ready(function()
					{
                        let fileInput = '';
                        jQuery('#tag-image').live('click',function()
						{
							fileInput = jQuery('#tag-image');
							tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
							return false;
						});

						window.original_send_to_editor = window.send_to_editor;
						window.send_to_editor = function(html)
						{
                            let file_url;
                            if (fileInput) {
                                file_url = jQuery('img', html).attr('src');
                                if (!file_url) {
                                    file_url = jQuery(html).attr('src');
                                }
                                jQuery(fileInput).val(file_url);
                                tb_remove();
                            } else {
                                window.original_send_to_editor(html);
                            }
						};
					});
				</script>
				<?php
			}

			// edit_$taxonomy
			add_action( 'edited_section', 'category_image_save' );
			add_action( 'create_section', 'category_image_save' );
			function section_image_save( $term_id ) {
				if ( isset( $_POST['tag-image'] ) ) {
					update_option( '_category_image' . $term_id, $_POST['tag-image'] );
				}
			}
			/*End of Custom taxonomy Images*/
		}
	}
}
add_action( 'admin_init', 'custom_fields_for_articles', 1 );

function build_flag_content_type_views( $views, $post_type, $flag ) {
	$slug_views = [
		[
			'label' => 'Featured - Home Page',
			'flag'  => 'featured',
		],
		[
			'label' => 'Featured - Running',
			'flag'  => 'featured-running',
		],
		[
			'label' => 'Featured - Web Development',
			'flag'  => 'featured-web-development',
		],
		[
			'label' => 'Sponsor',
			'flag'  => 'sponsor',
		],
	];

	foreach ( $slug_views as $slug_view ) {
		$views[ $slug_view['label'] ] = sprintf(
			'<a href="%1$s" class="%2$s">%3$s <span class="count">(%4$s)</span></a>',
			esc_url( admin_url( 'edit.php?flag=' . $slug_view['flag'] . '&post_type=' . $post_type ) ),
			$slug_view['flag'] == $flag ? 'current' : '',
			__( $slug_view['label'], 'flag' ),
			custom_get_term_post_count_by_type( $slug_view['flag'], 'flag', 'post' )
		);
	}
	return $views;
}
