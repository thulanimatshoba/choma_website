<?php

class CustomWebDevelopmentFrontPageList extends CustomFrontPageList {

	/** Class constructor */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'front_web_development_featured_post',
				'plural'   => 'front_web_development_featured_post',
				'ajax'     => false,
			)
		);
	}

	function process_bulk_action() {
		if ( 'delete' === $this->current_action() ) {
			wp_trash_post( $_GET['ID'] );
			$_SESSION['message'] = 'success_front_trash';
			wp_redirect( admin_url( 'admin.php?page=featured-web-development-flagged-post' ) );
			exit;
		} elseif ( 'unfeature' === $this->current_action() ) {
			foreach ( $_POST['front_featured_post'] as $post_id ) {
				wp_remove_object_terms( $post_id, 'featured-maverick-life', 'flag' );
				delete_post_meta( $post_id, 'stashed-web-development-ordering' );
			}
			$_SESSION['message'] = 'success_front_unfeature';
			wp_redirect( esc_url( add_query_arg() ) );
			exit;
		}
	}

    public function get_sql_results(): array
     {
        $this->add_ordering_meta_to_featured_posts( 'web-development' );
        $this->un_feature_posts_over_display_count( 'web-development' );
        $list_featured_args = $this->get_featured_posts_args( 'web-development' );

        return $this->build_featured_posts( $list_featured_args );
    }
}
