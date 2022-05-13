<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class FactOfTheDay extends WP_List_Table {

	function __construct() {
		parent::__construct(
			[
				'singular' => 'Fact Of The Day',
				'plural'   => 'Fact Of The Days',
				'ajax'     => false,
			]
		);
	}

	function no_items() {
		_e( 'No Fact Of The Day Posts found.' );
	}

	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = $this->get_hidden_columns();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = [$columns, $hidden, $sortable];
		$this->process_bulk_action();

		$data         = $this->table_data();
		$per_page     = 10;
		$current_page = $this->get_pagenum();
		$total_items  = count( $data );

		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
			]
		);
		$data                  = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
		$this->_column_headers = [ $columns, $hidden, $sortable ];
		$this->items           = $data;
	}

	public function get_columns(): array
    {
        return [
            'cb'     => '<input type="checkbox" />',
            'id'     => 'Fact ID',
            'fact'   => 'Fact Content',
            'author' => 'Fact Author',
        ];
	}

	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="fact[]" value="%s" />', $item['id'] );
	}

	function get_bulk_actions(): array
    {
        return [
            'bulk-delete' => 'Delete',
        ];
	}

	public function process_bulk_action() {
		$action = $this->current_action();
		switch ( $action ) {
			case 'bulk-delete':
				foreach ( $_GET['fact'] as $fact ) {
					global $wpdb;
					$table_name = $wpdb->prefix . 'stashed_fact_of_the_day';
					$wpdb->query( 'DELETE FROM ' . $table_name . ' WHERE id = ' . $fact . ' LIMIT 1' );
				}
				break;

			default:
				// do nothing or something else
				return;
				break;
		}
		return;
	}

	public function get_hidden_columns(): array
    {
		return [ 'id' ];
	}

	public function get_sortable_columns(): array
    {
		return [
			'id'     => [ 'id', false ],
			'fact'   =>[ 'fact', false ],
			'author' => [ 'author', false ],
		];
	}

	function column_fact( $item ): string
    {
		$edit_url   = get_option( 'siteurl' ) . '/wp-admin/edit.php?page=factoftheday&amp;action=edit&amp;id=' . $item['id'];
		$delete_url = wp_nonce_url( get_option( 'siteurl' ) . '/wp-admin/edit.php?page=factoftheday&amp;action=delete-fact&amp;id=' . $item['id'], 'fact-nonce' );
		$actions    = array(
			'edit'   => '<a href="' . $edit_url . '"> Edit </a>',
			'delete' => '<a href="' . $delete_url . '"> Delete </a>',
		);
		return sprintf( '%1$s %2$s', $item['fact'], $this->row_actions( $actions ) );
	}

	private function table_data(): array
    {
		global $wpdb;
		$facts = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}stashed_fact_of_the_day ORDER BY id DESC" );
		$data  = [];
		foreach ( $facts as $fact ) {
			$data[] = [
				'id'     => $fact->id,
				'fact'   => $fact->fact,
				'author' => $fact->author,
			];
		}
		return $data;
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
			case 'fact':
			case 'author':
				return $item[ $column_name ];

			default:
				return print_r( $item, true );
		}
	}

	private function sort_data( $a, $b ): int
    {
		// Set defaults
		$order_by = 'id';
		$order    = 'desc';

		// If orderby is set, use this as the sort column
		if ( ! empty( $_GET['orderby'] ) ) {
			$order_by = $_GET['orderby'];
		}

		// If order is set use this as the order
		if ( ! empty( $_GET['order'] ) ) {
			$order = $_GET['order'];
		}

		$result = strcmp( $a[ $order_by ], $b[ $order_by ] );

		if ( $order === 'asc' ) {
			return $result;
		}
		return $result;
	}
}
