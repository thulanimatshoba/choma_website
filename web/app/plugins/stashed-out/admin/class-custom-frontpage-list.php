<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class CustomFrontPageList extends WP_List_Table {
	private $posts_per_page = 100;
	private $search_string;
	public $exclude_news_deck = [
		[
			'taxonomy' => 'category',
			'field'    => 'slug',
			'terms'    => 'newsdeck',
			'operator' => 'NOT IN',
		],
	];

	/** Class constructor */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'front_featured_post',
				'plural'   => 'front_featured_post',
				'ajax'     => false,
			]
		);
	}

	public function get_featured_posts_args( $front_page = 'main' ) : array {
		$meta_key = 'stashed-frontpage-' . $front_page . '-ordering';
		return [
			'post_type'   => [ 'post', 'opinion-piece' ],
			'post_status' => 'publish',
			'numberposts' => HOMEPAGE_FEATURED_POSTS_DISPLAY_COUNT,
			'tax_query'   => [
				'relation' => 'AND',
				[
					'taxonomy' => 'flag',
					'field'    => 'slug',
					'terms'    => 'featured' . ( ( $front_page != 'main' ) ? '-' . $front_page : '' ),
				],
				$this->exclude_news_deck,
			],
			'meta_key'    => $meta_key,
			'orderby'     => 'meta_value_num',
			'order'       => 'ASC',
		];
	}

	public function add_ordering_meta_to_featured_posts( $front_page = 'main' ) {
		$meta_key = 'stashed-frontpage-' . $front_page . '-ordering';
		$list_featured_args                = $this->get_featured_posts_args( $front_page );
		$list_featured_args['numberposts'] = $this->posts_per_page;
		$list_featured_args['fields']      = 'ids';
		$list_featured_args['meta_query']  = [
			[
				'key'     => $meta_key,
				'compare' => 'NOT EXISTS',
			],
		];
		unset( $list_featured_args['meta_key'] );
		unset( $list_featured_args['orderby'] );
		unset( $list_featured_args['order'] );

		$featured_posts = get_posts( $list_featured_args );
		foreach ( $featured_posts as $featured_post_id ) {
			$order = get_post_meta( $featured_post_id, $meta_key, true );
			if ( $order == '' ) {
				update_post_meta( $featured_post_id, $meta_key, 1 );
			}
		}
	}

	public function un_feature_posts_over_display_count( $front_page = 'main' ) {
		$list_featured_args                = $this->get_featured_posts_args( $front_page );
		$list_featured_args['numberposts'] = $this->posts_per_page;
		$list_featured_args['offset']      = HOMEPAGE_FEATURED_POSTS_DISPLAY_COUNT;
		$list_featured_args['fields']      = 'ids';

		$featured_posts = get_posts( $list_featured_args );
		foreach ( $featured_posts as $featured_post_id ) {
			delete_post_meta( $featured_post_id, 'stashed-frontpage-' . $front_page . '-ordering' );
			wp_remove_object_terms( $featured_post_id, ( ( $front_page == 'main' ) ? 'featured' : 'featured-' . $front_page ), 'flag' );
		}
	}

	public function get_sql_results(): array
    {
		$this->add_ordering_meta_to_featured_posts();
		$this->un_feature_posts_over_display_count();
		return $this->build_featured_posts( $this->get_featured_posts_args() );
	}

	/**
	 * @see WP_List_Table::no_items()
	 */
	public function no_items() {
		_e( 'No featured flagged post found.' );
	}

	/**
	 * @see WP_List_Table::get_columns()
	 */
	function get_columns(): array
    {
        return [
            'cb'             => '<input type="checkbox" />',
            'featured_image' => 'Featured Image',
            'post_title'     => 'Title',
            'post_author'    => 'Author',
            'post_date'      => 'Published',
            'grid-position'  => 'Grid Position',
        ];
	}

	/**
	 * Define which columns are hidden
	 *
	 * @return Array
	 */
	public function get_hidden_columns(): array
    {
		return [ 'id', 'menu_order' ];
	}

	/**
	 * @see WP_List_Table::get_sortable_columns()
	 */
	public function get_sortable_columns(): array
    {
		return [];
	}

	function get_bulk_actions(): array
    {
        return [ 'unfeature' => 'Unfeature' ];
	}

	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],
			$item['id']
		);
	}

	function column_post_title( $item ): string
    {
		$actions = [
			'edit' => '<a href="' . get_edit_post_link( $item['id'] ) . '">Edit</a>',
			'view' => sprintf( '<a href="%1$s" target="_blank">%2$s</a>', get_permalink( $item['id'] ), 'View' ),
		];
		return sprintf( '%1$s %2$s', $item['post_title'], $this->row_actions( $actions ) );
	}

	function process_bulk_action() {
		if ( 'delete' === $this->current_action() ) {
			wp_trash_post( $_GET['ID'] );
			$_SESSION['message'] = 'success_front_trash';
			wp_redirect( admin_url( 'admin.php?page=featured-flagged-post' ) );
			exit;
		} elseif ( 'unfeature' === $this->current_action() ) {
			foreach ( $_POST['front_featured_post'] as $post_id ) {
				wp_remove_object_terms( $post_id, 'featured', 'flag' );
				delete_post_meta( $post_id, 'stashed-frontpage-main-ordering' );
			}
			$_SESSION['message'] = 'success_front_unfeature';
			wp_redirect( esc_url( add_query_arg() ) );
			exit;
		}
	}

	/**
	 * Prepare data for display
	 *
	 * @see WP_List_Table::prepare_items()
	 */
	function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = $this->get_hidden_columns();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->process_bulk_action();
		if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
			$search = $_REQUEST['s'];
        }
        $data   = $this->get_sql_results();
        if ( ! empty( $data ) ) {
			$per_page     = $this->posts_per_page;
			$current_page = $this->get_pagenum();
			$total_items  = count( $data );

			$this->set_pagination_args(
				[
					'total_items' => $total_items,
					'per_page'    => $per_page,
					'total_pages' => ceil( $total_items / $per_page ),
				]
			);
			$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
		}
		$this->items = $data;
	}

	/**
	 * A single column
	 *
	 * @param $item
	 * @param $column_name
	 * @return string
	 */
	public function column_default( $item, $column_name ): string
    {
		return ( $column_name === 'grid-position' ) ? '' : $item[ $column_name ];
	}

    function get_post_categories( $post_id, $term_name = 'category', $return_slug = false ): array
    {
        $post_categories = [];
        $fallback        = true;
        //if ( class_exists( 'WPSEO_Primary_Term' ) ) {
            // Show the post's 'Primary' category, if this Yoast feature is available, & one is set
            $wpseo_primary_term = new WPSEO_Primary_Term( $term_name, $post_id );
            $wpseo_primary_term = $wpseo_primary_term->get_primary_term();
            $term               = get_term( $wpseo_primary_term );
            if ( ! is_wp_error( $term ) ) {
                // Yoast Primary category
                $post_categories[] = (string) ( $return_slug ) ? $term->slug : $term->name;
                $fallback          = false;
            }
        //}
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

	public function get_post_section_label( $post_id, $post_type ): string
    {
		$section_label = '';
		if ( has_term( 'sponsor', 'flag', $post_id ) ) {
			$section_label = 'ADVERTISEMENT';
		} elseif ( $post_type == 'opinion-piece' ) {
			$section_label = 'OPINIONISTA';
		} else {
			$get_feed_title = get_post_meta( $post_id, 'stashed_custom_section_label' );
			if ( ! empty( $get_feed_title ) && ! empty( $get_feed_title[0] ) ) {
				$section_label = $get_feed_title[0];
			} else {
				if ( function_exists( 'get_post_categories' ) ) {
					$post_categories = get_post_categories( $post_id);
					if ( ! empty( $post_categories ) ) {
						$section_label = implode( ', ', $post_categories );
					}
				}
			}
		}
		return strtoupper( $section_label );
	}

	public function build_featured_posts( $list_featured_args ): array
    {
		$featured_post_list = [];
		$featured_posts     = get_posts( $list_featured_args );
		foreach ( $featured_posts as $key => $post ) {
			$link      = get_edit_post_link( $post->ID );
			$post_type = get_post_type_object( $post->post_type );
			$thumb     = get_the_post_thumbnail( $post->ID, [ 50, 50 ] );

			$data                   = [];
			$data['id']             = $post->ID;
			$data['featured_image'] = ( $thumb ) ? $thumb : '';
			$data['post_title']     = "<a href='$link' target='_blank'>$post->post_title</a>";
			$data['post_title']    .= '<br/>' . ( ( $post->post_type == 'post' ) ? $post_type->labels->singular_name . ' - ' : '' ) . $this->get_post_section_label( $post->ID, $post->post_type );
			$data['post_author']    = get_the_author_meta( 'display_name', $post->post_author );
			$data['post_date']      = get_the_time( 'd/m/Y', $post->ID );
			$data['menu_order']     = $post->menu_order;
			$data['post_type']      = $post_type->labels->singular_name;

			$featured_post_list[] = $data;
		}
		return $featured_post_list;
	}
}
