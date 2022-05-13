<?php
require_once 'library/multi-post-thumbnails.php';

if ( class_exists( 'MultiPostThumbnails' ) ) {
	new MultiPostThumbnails(
		array(
			'label'     => 'Banner Image',
			'id'        => 'feature-image-2',
			'post_type' => 'post',
		)
	);
};

function update_default_user_status() {
    $user_ids = get_users( array( 'fields' => 'ID' ) );
    foreach ( $user_ids as $user_id ) {
        if ( ! add_user_meta( $user_id, 'stashed-status-user', true, true ) ) {
            update_user_meta( $user_id, 'stashed-status-user', true );
        }
    }
}

function get_mapped_user_meta( $user_id ): array
{
    $user_meta = get_user_meta( $user_id );
    return array_map(
        function( $a ) {
            return $a[0];
        },
        ( is_array( $user_meta ) ? $user_meta : array() )
    );
}

function get_post_categories( $post_id, $term_name = 'category', $return_slug = false ): array
{
    $post_categories = array();
    $fallback        = true;
    if ( class_exists( 'WPSEO_Primary_Term' ) ) {
        // Show the post's 'Primary' category, if this Yoast feature is available, & one is set
        $wpseo_primary_term = new WPSEO_Primary_Term( $term_name, $post_id );
        $wpseo_primary_term = $wpseo_primary_term->get_primary_term();
        $term               = get_term( $wpseo_primary_term );
        if ( ! is_wp_error( $term ) ) {
            // Yoast Primary category
            $post_categories[] = (string) ( $return_slug ) ? $term->slug : $term->name;
            $fallback          = false;
        }
    }
    if ( $fallback ) {
        // Default to first category (not Yoast) if an error is returned
        $categories = get_the_terms( $post_id, $term_name );
        if ( ! empty( $categories ) && is_array( $categories ) ) {
            foreach ( $categories as $category ) {
                $post_categories[] = (string) ( $return_slug ) ? $category->slug : $category->name;
            }
        }
    }
    return $post_categories;
}

function build_linked_categories( $post_categories ): array
{
    $cat_array = [];
    foreach ( $post_categories as $single_post_category ) {
        if ( ! empty( $single_post_category ) ) {
            $category = get_term_by( 'name', $single_post_category, 'category' );
            if ( ! empty( $category ) ) {
                $term_link = get_term_link( $category );
                if ( ! empty( $term_link ) ) {
                    $cat_array[] = '<a href="' . $term_link . '">' . $single_post_category . '</a>';
                } else {
                    $cat_array[] = $single_post_category;
                }
            }
        }
    }
    return $cat_array;
}

function get_open_graph_twitter_card_description( $key = '' ) {
    $description = '';
    if ( $key === '_yoast_wpseo_twitter-description' || $key === '_yoast_wpseo_opengraph-description' ) {
        $description = get_post_meta( get_the_ID(), $key, true );
    }
    if ( empty( $description ) ) {
        $description = wp_strip_all_tags( get_the_excerpt(), true );
    }
    return $description;
}

function get_title_with_custom_section_label( $key = '' ) {
    if ( $key === '_yoast_wpseo_opengraph-title' || $key === '_yoast_wpseo_twitter-title' ) {
        $title = get_post_meta( get_the_ID(), $key, true );
    }
    if ( empty( $title ) ) {
        $title                = get_the_title();
        $custom_section_label = get_post_meta( get_the_ID(), 'stashed_custom_section_label', true );
        $title                = ( ! empty( $custom_section_label ) ) ? $custom_section_label . ': ' . $title : $title;
    }
    return $title;
}

function get_custom_label( $post_id, $post_type = 'post', $is_sponsor = false, $link_required = false, $return_label = false ) {
    $label = '';
    if ( $is_sponsor ) {
        $label = 'SPONSORED CONTENT';
    } elseif ( $post_type == 'opinion-piece' ) {
        $label = 'OPINIONISTA';
    } else {
        if ( is_object( $post_id ) ) {
            $post_id = $post_id->ID;
        }
        $get_feed_title = get_post_meta( $post_id, 'stashed_custom_section_label' );
        if ( ! empty( $get_feed_title ) && ! empty( $get_feed_title[0] ) ) {
            $label = $get_feed_title[0];
        } else {
            $post_categories = get_post_categories( $post_id);
            if ( ! empty( $post_categories ) ) {
                if ( $link_required == true ) {
                    $post_categories = build_linked_categories( $post_categories );
                }
                $label = implode( ', ', $post_categories );
            }
        }
    }
    if ( ! empty( $label ) ) {
        if ( ! $return_label ) {
            echo '<h4>' . $label . '</h4>';
        } else {
            return $label;
        }
    }
}

function get_category_name( $post_id, $term_name = '' ) {
    $label           = '';
    $post_categories = get_post_categories( $post_id, $term_name );
    if ( ! empty( $post_categories ) ) {
        $label = implode( ', ', $post_categories );
    }
    if ( ! empty( $label ) ) {
        echo '<h4>' . $label . '</h4>';
    }
}

function stashed_admin_init_tasks() {
	if ( ! wp_doing_ajax() ) {
		remove_menu_page( 'edit.php' );
		update_option( 'users_can_register', 1 );
		require_once 'class-custom-frontpage-list.php';
		require_once 'class-custom-running-frontpage-list.php';
		require_once 'class-custom-web-development-frontpage-list.php';
	}
}
add_action( 'admin_init', 'stashed_admin_init_tasks' );

add_action( 'admin_enqueue_scripts', 'stashed_admin_enqueue_scripts' );
function stashed_admin_enqueue_scripts() {
	wp_enqueue_style( 'stashed-custom-css', plugin_dir_url( __FILE__ ) . '/css/styles.css', null, '1.0.1' );
	wp_enqueue_script( 'stashed-custom-script', plugin_dir_url( __FILE__ ) . 'js/custom-admin.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable' ), time(), true );
}

/**
 * Add custom field data
 */
function add_article_meta_boxes() {
	add_meta_box( 'article_meta_box', 'Custom Section Label', 'stashed_display_post_meta_box', 'post', 'normal', 'low' );
	add_meta_box( 'opinion-piece_meta_box', 'Custom Section Label', 'stashed_display_post_meta_box', 'opinion-piece', 'normal', 'low' );
}
add_action( 'admin_init', 'add_article_meta_boxes' );

function stashed_display_post_meta_box( $post ) {
	$meta_value = get_post_meta( $post->ID, 'stashed_custom_section_label', true );
	if ( $post->post_type === 'opinion-piece' && empty( $meta_value ) ) {
		$meta_value = 'OPINIONISTA';
	}
	echo '<input type="text" name="stashed_custom_section_label" value="' . esc_html( $meta_value ) . '" class="widefat" />';
}

/**
 * Save custom field data when creating/updating posts
 */
function stashed_save_post_custom_fields() {
	 global $post;
	if ( $post ) {
		if ( isset( $_POST['stashed_custom_section_label'] ) ) {
			$custom_section_label = $_POST['stashed_custom_section_label'];
			if ( empty( $custom_section_label ) && $post->post_type === 'article' && function_exists( 'get_post_categories' ) ) {
				$custom_section_label = get_post_categories( $post->ID)[0];
			}
			update_post_meta( $post->ID, 'stashed_custom_section_label', $custom_section_label );
		}
	}
}
add_action( 'save_post', 'stashed_save_post_custom_fields' );

add_action( 'wp_ajax_ajaxArticleContentUpdate', 'ajax_article_content_update_callback' );
add_action( 'wp_ajax_nopriv_ajaxArticleContentUpdate', 'ajax_article_content_update_callback' );
function ajax_article_content_update_callback() {
	if ( isset( $_GET['contentValue'] ) && isset( $_GET['postId'] ) ) {
		$post_id       = $_GET['postId'];
		$content_value = $_GET['contentValue'];
		update_feature_tag_meta_field( $post_id, $content_value );
	}
}

function update_feature_tag_meta_field( $post_id, $content_value ) {
	if ( $content_value == 'true' ) {
		update_term_meta( $post_id, 'is_feature', 'feature' );
	} else {
		delete_term_meta( $post_id, 'is_feature', '' );
	}
}

add_action( 'wp_ajax_ajaxRemoveImageFromTaxonomy', 'ajax_remove_image_from_taxonomy_callback' );
add_action( 'wp_ajax_nopriv_ajaxRemoveImageFromTaxonomy', 'ajax_remove_image_from_taxonomy_callback' );
function ajax_remove_image_from_taxonomy_callback() {
	$taxonomy_id = $_GET['taxonomyId'];
	if ( isset( $taxonomy_id ) ) {
		delete_option( '_category_image' . $taxonomy_id );
	}
}

function get_author_custom_post_count(): array
{
	static $counts;
	if ( ! isset( $counts ) ) {
		global $wpdb, $wp_post_types;
		$sql   = <<<SQL
SELECT
  post_type, COUNT(*) AS post_count, post_author
FROM
  {$wpdb->posts}
WHERE 1=1
  AND post_type NOT IN ('revision','nav_menu_item', 'attachment', 'post')
  AND post_status IN ('publish','pending', 'rejected')
GROUP BY post_type, post_author
SQL;
		$posts = $wpdb->get_results( $sql );
		foreach ( $posts as $post ) {
			$post_type_object = $wp_post_types[ $post_type = $post->post_type ];
			if ( ! empty( $post_type_object->label ) ) {
				$label = $post_type_object->label;
			} else {
				if ( ! empty( $post_type_object->labels->name ) ) {
					$label = $post_type_object->labels->name;
				} else {
					$label = ucfirst( str_replace( [ '-', '_' ], ' ', $post_type ) );
				}
			}
			$counts[ $post->post_author ][] = [
				'label' => $label,
				'count' => $post->post_count,
			];
		}
	}
	return $counts;
}

add_action( 'wp_ajax_update_frontpage_menu_order', 'update_front_page_order' );
function update_front_page_order() {
	global $wpdb;
	$status = false;
	if ( ! empty( $_POST['frontpage'] && ! empty( $_POST['order'] ) ) ) {
		foreach ( $_POST['order'] as $key => $id ) {
			$status = update_post_meta( $id, 'stashed-frontpage-' . $_POST['frontpage'] . '-ordering', $key );
		}
	}
	return $status;
}

add_action( 'wp_ajax_check_if_postid_exsist', 'check_if_postid_exsist_callback' );
function check_if_postid_exsist_callback() {
	$data           = [];
	$data['status'] = 0;

	if ( ! empty( $_POST['post'] ) && ! empty( $_POST['frontpage'] ) && get_post_status( $_POST['post'] ) ) {
		$front_page = $_POST['frontpage'];
		$tag        = [];
		$tag[]      = 'featured' . ( ( $front_page != 'main' ) ? '-' . $front_page : '' );
		$title      = get_the_title( intval( $_POST['post'] ) );
		wp_set_object_terms( intval( $_POST['post'] ), $tag, 'flag', true );
		$data['title']  = $title;
		$data['status'] = 1;
	}
	echo json_encode( $data );
	exit;
}

add_action( 'admin_menu', 'add_featured_flagged_post_menu' );
function add_featured_flagged_post_menu() {
    add_menu_page( 'Front Pages', 'Front Pages', 'manage_categories', 'featured-flagged-post', 'featured_flagged_post_pages', 'dashicons-editor-kitchensink', 2 );
	add_submenu_page( 'featured-flagged-post', 'Home Page', 'Home Page', 'manage_categories', 'featured-flagged-post', 'featured_flagged_post_pages' );
	add_submenu_page( 'featured-flagged-post', 'Running', 'Running', 'manage_categories', 'featured-running-flagged-post', 'featured_flagged_post_pages' );
	add_submenu_page( 'featured-flagged-post', 'Web Development', 'Web Development', 'manage_categories', 'featured-web-development-flagged-post', 'featured_flagged_post_pages' );
}

function featured_flagged_post_pages() {   ?>
	<div class="wrap">
		<?php
		$screen      = get_current_screen();
		$screen_slug = $screen->id;

		switch ( (string) $screen_slug ) {
			case 'front-pages_page_featured-running-flagged-post':
				$page_heading  = 'Running Front Page';
				$featured_list = new CustomRunningFrontPageList();
				break;

			case 'front-pages_page_featured-web-development-flagged-post':
				$page_heading  = 'Web Development Front Page';
				$featured_list = new CustomWebDevelopmentFrontPageList();
				break;

			default:
				$page_heading  = 'Home Page Front Page';
				$featured_list = new CustomFrontPageList();
				break;
		}

		echo '<h2>' . $page_heading . '</h2>';
		if ( isset( $_SESSION['message'] ) && $_SESSION['message'] == 'success_front_trash' ) {
			echo "<div id='message' class='notice notice-success is-dismissible'><p>" . __( 'Featured post moved to trash.' ) . "</p><button type='button' class='notice-dismiss'><span class='screen-reader-text'>Dismiss this notice.</span></button></div>";
			unset( $_SESSION['message'] );
		}
		if ( isset( $_SESSION['message'] ) && $_SESSION['message'] == 'success_front_unfeature' ) {
			echo "<div id='message' class='notice notice-success is-dismissible'><p>" . __( 'Unfeatured post successfully.' ) . "</p><button type='button' class='notice-dismiss'><span class='screen-reader-text'>Dismiss this notice.</span></button></div>";
			unset( $_SESSION['message'] );
		}

		?>
		<form method="post">
			<?php
			$featured_list->prepare_items();
			$featured_list->display();
			?>
		</form>
	</div>
	<?php
}

// Dropdown of authors on posts
add_filter( 'wp_dropdown_users_args', 'stashed_build_arguments_for_user_dropdown', 10, 2 );
function stashed_build_arguments_for_user_dropdown( $args, $r ) {
	global $post;
	// displays all authors on quick edit.
	if ( ( 'post_author_override' === $r['name'] || 'post_author' === $r['name'] )
        && in_array( $post->post_type,
            [ 'post', 'opinion-piece' ] ) )
    {

		// displays all authors on post edit.
		if ( current_user_can( 'edit_post', $post->ID ) ) {
			// If we have roles, change the args to only get users of those roles.
			$args['who']      = '';
			$args['role__in'] = [ 'author', 'editor' ];
		}
	}
	return $args;
}

// Dropdown of authors for list views
function stashed_build_filter_by_the_author_drop_down() {
	global $type_now;
	if ( !in_array( $type_now, [ 'post', 'opinion-piece' ] ) ) {
		return;
	}
	$params = array(
		'name'            => 'author',
		'show_option_all' => 'All authors',
		'role__in'        => [ 'administrator', 'editor', 'author' ],
	);
	if ( isset( $_GET['user'] ) ) {
		$params['selected'] = $_GET['user'];
	}
	wp_dropdown_users( $params );
}
add_action( 'restrict_manage_posts', 'stashed_build_filter_by_the_author_drop_down' );

// Redefine user notification function
if ( ! function_exists( 'wp_new_user_notification' ) ) {
	function wp_new_user_notification( $user_id, $plaintext_pass = '' ) {
		$user = new WP_User( $user_id );

		$user_login = stripslashes( $user->user_login );
		$user_email = stripslashes( $user->user_email );

		$email_message  = '<html lang="">';
		$email_message .= '<head>';
		$email_message .= '<title>Welcome to The Site</title>';
		$email_message .= '<style>body, p, td { font-size: 12px; font-family: Helvetica, Arial, sans-serif; }</style>';
		$email_message .= '</head>';
		$email_message .= '<body>';
		$email_message .= '<table width="600px" align="center">';
		$email_message .= '<tr><td align="left">';
		$email_message .= '<a href="' . home_url() . '" style="border: none; text-decoration: none;"><img src="' . site_url() . EMAIL_BANNER_IMAGE_URL . '" alt="Stashed Out" width="600" height="75" style="border: none; text-decoration: none;" /></a>';
		$email_message .= '</td></tr>';
		$email_message .= '<tr><td style="padding: 20px 10px;">';
		$email_message .= '<p style="font-size: 12px; font-family: Helvetica, Arial, sans-serif;">' . sprintf( 'Welcome %s,', ucfirst( $user->user_firstname ) ) . '</p>';
		$email_message .= '<p style="font-size: 12px; font-family: Helvetica, Arial, sans-serif;">Thanks for registering with Us!</p>';
		$email_message .= '<p style="font-size: 12px; font-family: Helvetica, Arial, sans-serif;">Your registration enables you to use some handy features such as saving articles for later reading, as well as automated email updates when an author you follow publishes a new piece.</p>';
		$email_message .= '<p style="font-size: 12px; font-family: Helvetica, Arial, sans-serif;">You can update your notification preferences anytime <a href="' . site_url() . '/edit-my-profile/">here</a>.</p>';
		$email_message .= '<p style="font-size: 12px; font-family: Helvetica, Arial, sans-serif;">Kind regards, <br>';
		$email_message .= '<strong>Stashed Out</strong></p>';
		$email_message .= '</td></tr>';
		$email_message .= '<tr><td align="center">';
		$email_message .= '<p style="font-size: 10px; font-family: Helvetica, Arial, sans-serif; color: #000000; padding: 10px;">Copyright &copy; ' . date( 'Y' ) . ' Stashed Out, All rights reserved.</p>';
		$email_message .= '</td></tr>';
		$email_message .= '</table>';
		$email_message .= '</body>';
		$email_message .= '</html>';

		$headers = array( 'MIME-Version: 1.0', 'Content-Type: text/html; charset=UTF-8' );
		wp_mail( $user_email, sprintf( __( 'Welcome to %s' ), get_option( 'blogname' ) ), $email_message, $headers );
	}
}

add_action( 'wp_ajax_update_featured_tag_order', 'update_featured_tag_order' );
function update_featured_tag_order() {
	 global $wpdb;
	parse_str( $_POST['order'], $data );
	if ( ! is_array( $data ) ) {
		return 'not array';
	}

	foreach ( $data['tag'] as $key => $id ) {
		$status = $wpdb->update( $wpdb->terms, [ 'term_order' => $key ], [ 'term_id' => intval( $id ) ] );
		if ( false === $status ) {
			$message = 'failed: term_id: ' . $id . ', term_order: ' . $key . ' ' . $wpdb->last_error . '<br/>';
		}
	}
	echo $message;
	wp_die();
}

function stashed_term_sort( $a, $b ): int
{
	if ( $a->term_order == $b->term_order ) {
		return 0;
	}
	return ( $a->term_order < $b->term_order ) ? -1 : 1;
}

add_filter( 'wp_get_object_terms', 'stashed_get_object_terms', 10, 3 );
add_filter( 'get_terms', 'stashed_get_object_terms', 10, 3 );
function stashed_get_object_terms( $terms ) {
	if ( is_admin() && isset( $_GET['taxonomy'] ) && isset( $_GET['post_type'] ) && $_GET['post_type'] === 'post' ) {
		if ( ( $_GET['taxonomy'] === 'article_tag' && ( isset( $_GET['is_feature'] ) && $_GET['is_feature'] === 'feature' ) ) || $_GET['taxonomy'] === 'category' ) {
			usort( $terms, 'stashed_term_sort' );
		}
	}
	return $terms;
}

add_action( 'manage_users_columns', 'stashed_add_registration_date_column_on_users_list' );
function stashed_add_registration_date_column_on_users_list( $column_headers ) {
	$column_headers['date_registered'] = 'Registration Date';
	return $column_headers;
}

add_action( 'manage_users_custom_column', 'stashed_add_registration_date_column_value_on_users_row', 10, 3 );
function stashed_add_registration_date_column_value_on_users_row( $custom_column, $column_name, $user_id ) {
	if ( $column_name == 'date_registered' ) {
		$user          = get_userdata( $user_id );
		$custom_column = $user->user_registered;
	}
	return $custom_column;
}

/**
 * This functions fetches all the posts to be showed on a specific homepage, such as the main/frontpage,
 * the running, web development etc...
 **/
function get_home_page_posts( int $exclude_post_id = 0, $custom_per_page = 0, $offset = 0, $front_page = 'main' ): array
{
    $featured_term     = 'featured' . ( ( $front_page != 'main' ) ? '-' . $front_page : '' );
    $posts_per_page = ( ( $custom_per_page > 0 ) ? $custom_per_page : HOMEPAGE_LIST_POST_PER_PAGE ) + 1;

    // Due to us manually removing data instead of using NOT IN, we need to cater for additional posts,
    // i recommend tripling the amount to test with
    $total_post_per_page = $posts_per_page*10;
    /*Featured Post List*/
    $featured_post_args = [
        'post_status' 			 => 'publish',
        'post_type'              => [ 'post', 'opinion-piece' ],
        'posts_per_page'         => $total_post_per_page,
        'tax_query'              => [
            'relation' => 'AND',
            [
                'taxonomy' => 'flag',
                'field'    => 'slug',
                'terms'    => $featured_term,
            ],
        ],
        'update_post_term_cache' => false,
        'update_post_meta_cache' => false,
        'fields'                 => 'ids',
        'meta_key'               => 'stashed-frontpage-' . $front_page . '-ordering',
        'orderby'                => 'meta_value_num',
        'order'                  => 'ASC',
        'include_children'		 => false
    ];

    $featured_posts_query = new WP_Query( $featured_post_args );
    $featured_posts = $featured_posts_query->posts;

    // Lets add our logic here to 'exclude' a post and NOT in queries
    foreach ($featured_posts as $key => $post) {
        // Remove a specific post id
        if ( $exclude_post_id > 0 ) {
            if ($exclude_post_id == $post) {
                unset($featured_posts[$key]);
            }
        }

        // Remove newsdesk items, get_the_terms doesn't hit the database again as per documentation
        $post_section = get_the_terms($post, 'category');

        if (!empty($post_section)) {
            foreach ($post_section as $ps) {
                if ($ps->slug == 'newsdeck') {
                    unset($featured_posts[$key]);
                }
            }
        }
    }

    $featured_posts_count = count( $featured_posts );
    $home_page_remainder_post_count = ($posts_per_page - $featured_posts_count)*5;
    $article_posts                  = [];
    if ( $home_page_remainder_post_count != 0 ) {
        $articles_post_args = [
            'post_status' 			 => 'publish',
            'post_type'              => [ 'post', 'opinion-piece' ],
            'posts_per_page'         => $home_page_remainder_post_count,
            'orderby'                => [ 'post_date' => 'DESC' ],
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
            'fields'                 => 'ids',
            'include_children'		 => false
        ];
        if ( $front_page != 'main' ) {
            // front_page is expected to always be a  string
            $articles_post_args['tax_query'][] = [
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => $front_page
            ];
        }

        $article_posts_query = new WP_Query( $articles_post_args );
        $article_posts = $article_posts_query->posts;

        // Another check here to remove featured and news deck posts
        foreach ($article_posts as $key => $post) {
            $post_section = get_the_terms($post, 'category');
            $post_flag = get_the_terms($post, 'flag');

            if (!empty($post_section)) {
                foreach ($post_section as $ps) {
                    if ($ps->slug == 'newsdeck') {
                        unset($article_posts[$key]);
                    }
                }
            }

            if (!empty($post_flag)) {
                foreach ($post_flag as $pf) {
                    if ($pf->slug == $featured_term) {
                        unset($article_posts[$key]);
                    }
                }
            }
        }
    }

    // Limit the posts as per the original count
    $featured_posts = array_slice($featured_posts, 0, $posts_per_page);
    $article_posts = array_slice($article_posts, 0, $home_page_remainder_post_count);

    $home_page                        = [];
    $home_page['posts']               = array_merge( $featured_posts, $article_posts );
    $home_page['featured_post_count'] = $featured_posts_count;

    return $home_page;
}
