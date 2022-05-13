<?php

// Create table
register_activation_hook( __FILE__, 'createDatabaseTable' );
function createDatabaseTable() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'stashed_fact_of_the_day';
	if ( $wpdb->get_var( 'SHOW TABLES LIKE ' . $table_name ) != $table_name ) {
		$sql = 'CREATE TABLE ' . $table_name . ' ( id int( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY, user_id int( 11 ) NOT NULL, fact TEXT NULL, author VARCHAR( 255 ) NOT NULL )';
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}

add_action( 'admin_menu', 'custom_menu' );
function custom_menu() {
	 add_menu_page( 'Fact Of The Day', 'Fact Of The Day', 'manage_categories', 'factoftheday', 'display_management_page', 'dashicons-format-quote', 7 );
}

function display_management_page() {
	require_once 'factoftheday.class.php';
	$list = new FactOfTheDay();
	$list->prepare_items();
	?>
	<div class="wrap">
		<?php
		if ( isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'stashed_fact_of_the_day';
			$sql        = 'SELECT * FROM ' . $table_name . ' WHERE id = ' . $_GET['id'] . ' LIMIT 1';
			$results    = $wpdb->get_results( $sql );
			?>
			<h2>Edit Fact Of The Day</h2>
			<form name="factsForm" method="post" action="">
				<?php wp_nonce_field( 'fact-nonce' ); ?>
				<table class="editform form-table" width="100%" cellspacing="2" cellpadding="5">
					<tr class="form-field term-description-wrap">
						<th scope="row" valign="top">
							<label for="fact">Fact Of The Day:</label>
						</th>
						<td>
							<textarea name="fact" class="large-text" rows="5" cols="50" required><?php echo stripslashes( $results[0]->fact ); ?></textarea>
						</td>
					</tr>
					<tr class="form-field">
						<th scope="row" valign="top"><label for="author">Author:</label></th>
						<td>
							<input type="text" name="author" value="<?php echo stripslashes( $results[0]->author ); ?>" size="40"/>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><input type="hidden" name="editFact" value="1"/>
							<input type="hidden" name="id" value="<?php echo $results[0]->id; ?>"/>
						</th>
						<td>
							<input type="submit" name="submit" class="button button-primary" value="Update Fact &raquo;"/>
						</td>
					</tr>
				</table>
			</form>
			<?php
		} else {
			?>
			<h2>Add a Fact Of The Day</h2>
			<form name="factsForm" method="post" action="">
				<?php wp_nonce_field( 'fact-nonce' ); ?>
				<table class="editform form-table" width="100%" cellspacing="2" cellpadding="5">
					<tr class="form-field term-description-wrap">
						<th scope="row" valign="top">
							<label for="fact">Fact Of The Day:</label>
						</th>
						<td>
							<textarea name="fact" rows="5" cols="50" required></textarea>
						</td>
					</tr>
					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="author">Author:</label>
						</th>
						<td>
							<input type="text" name="author" size="40"/>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><input type="hidden" name="addFact" value="1"/></th>
						<td>
							<input type="submit" name="submit" class="button button-primary" value="Add Fact Of The Day"/>
						</td>
					</tr>
				</table>
			</form>
			<br/><br/>
			<h2>List of Articles in Fact Of The Days</h2>
			<form id="posts-filter" method="get">
				<input type="hidden" name="page" value="<?php echo $_GET['page']; ?>"/>
				<?php $list->display(); ?>
			</form>
			<?php
		}
		?>
	</div>
	<?php
}

add_action( 'admin_init', 'action_process' );
function action_process() {
	$action    = ($_GET['action'] ?? null);
	$add_fact  = ($_POST['add_fact'] ?? null);
	$edit_fact = ($_POST['edit_fact'] ?? null);

	if ( $action == 'delete-fact' && check_admin_referer( 'fact-nonce' ) ) {
		$msg = delete_fact( $_GET['id'] );
		if ( $msg == true ) {
			wp_redirect( admin_url( '/admin.php?page=factoftheday' ) );
			exit;
		}
	}

	if ( $add_fact == 1 && check_admin_referer( 'fact-nonce' ) ) {
		add_fact( trim( $_POST['fact'] ), $_POST['author'] );
	}

	if ( $edit_fact == 1 && check_admin_referer( 'fact-nonce' ) ) {
		$msg = edit_fact( $_POST['fact'], $_POST['author'], $_POST['id'] );
		if ( $msg == true ) {
			wp_redirect( admin_url( '/edit.php?page=factoftheday' ) );
			exit;
		}
	}
}

function stashed_error_message() {
	?>
	<div class="error notice">
		<p><?php _e( 'Required form fact field is missing', 'field' ); ?></p>
	</div>
	<?php
}

function add_fact( string $fact, string $author = '' ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'stashed_fact_of_the_day';

	if ( empty( $fact ) ) {
		add_action( 'admin_notices', 'stashed_error_message' );
	} else {
		$sql = 'INSERT INTO ' . $table_name . " ( user_id, fact, author ) VALUES ( '" . get_current_user_id() . "', '" . sanitize_textarea_field( $fact ) . "', '" . sanitize_text_field( $author ) . "' )";
		$wpdb->query( $sql );
	}
}

function edit_fact( string $fact = '', string $author = '', int $id ): bool
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'stashed_fact_of_the_day';

	if ( empty( $fact ) ) {
		add_action( 'admin_notices', 'stashed_error_message' );
		return false;
	} else {
		$sql = 'UPDATE ' . $table_name . " SET user_id = '" . get_current_user_id() . "', fact = '" . sanitize_textarea_field( $fact ) . "', author = '" . sanitize_text_field( $author ) . "' WHERE id = " . $id . ' LIMIT 1';
		$wpdb->query( $sql );
		return true;
	}
}

function delete_fact( int $id ): bool
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'stashed_fact_of_the_day';

	if ( (int) $id == 0 ) {
		add_action( 'admin_notices', 'stashed_error_message' );
		return false;
	} else {
		$sql = 'DELETE FROM ' . $table_name . ' WHERE id=' . $id . ' LIMIT 1';
		$wpdb->query( $sql );
		return true;
	}
}
