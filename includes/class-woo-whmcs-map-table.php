<?php

if (!class_exists('WP_List_Table')) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Woo_WHMCS_Map_Table extends WP_List_Table {
	function __construct() {
		parent::__construct(array(
			'singular' => 'wp_whmcs_map_item', //Singular label
			'plural' => 'wp_whmcs_map_items', //plural label, also this well be one of the table css class
			'ajax' => false, //We won't support Ajax for this table
		));
	}

	public function prepare_items() {
		$options = array(
			'page' => !empty($_REQUEST['page']) ? $_REQUEST['page'] : 1,
			's' => !empty($_REQUEST['s']) ? $_REQUEST['s'] : '',
			'orderby' => !empty($_REQUEST['orderby']) ? $_REQUEST['orderby'] : '',
			'order' => !empty($_REQUEST['order']) ? $_REQUEST['order'] : '',
		);

		$_SERVER['REQUEST_URI'] = add_query_arg($options, $_SERVER['REQUEST_URI']);

		$this->process_bulk_action();

		$columns = $this->get_columns();
		$hidden = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$perPage = 25;
		$currentPage = $this->get_pagenum();

		$query = !empty($_REQUEST['s']) ? $_REQUEST['s'] : '';
		$start = ($currentPage - 1) * $perPage;
		$limit = $perPage;

		$sort = !empty($_REQUEST['orderby']) ? wp_unslash($_REQUEST['orderby']) : 'id';
		$dir = !empty($_REQUEST['order']) ? wp_unslash($_REQUEST['order']) : 'asc';

		$data = $this->get_data($query, $start, $limit, $sort, $dir);

		$total = $data['total'];
		$data = $data['items'];

		//usort($data, array($this, 'sort_data'));

		//$data = array_slice($data, (($currentPage - 1) * $perPage), $perPage);

		$this->set_pagination_args([
			'total_items' => $total,
			'per_page' => $perPage,
		]);

		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->items = $data;
	}

	public function get_columns() {
		$columns = [
			'cb' => '<input id="check-all" type="checkbox" />',
			'id' => 'ID',
			'product_name' => 'Product Name',
			'whmcs_service_name' => 'WHMCS Service',
		];

		return $columns;
	}

	public function get_hidden_columns() {
		$hidden_columns = ['id'];
		return $hidden_columns;
	}

	public function get_sortable_columns() {
		$columns = $this->get_columns();
		$sort = [];
		/* foreach ($columns as $key => $value) {
			if ($key != 'cb') {
				$sort[$key] = [$key, false];
			}
		} */
		return $sort;
	}

	private function get_data($query, $start, $limit, $sort, $dir) {
		$api_client = new Woo_WHMCS_Api_Client();
		$result_api = $api_client->get_product_service();
		$service = [];
		foreach ($result_api as $items) {
			$service[$items['pid']] = [
				'id' => $items['pid'],
				'name' => $items['name'],
				'description' => $items['description']
			];
		}

		$args = array(
			'limit'  => -1, // All products
			'status' => 'publish',
		);
		$products = wc_get_products($args);
		$subscription = [];
		foreach ($products as $product) {
			if($product->get_type()=='subscription'){
				$subscription[$product->get_id()] = [
					'id' => $product->get_id(),
					'name' => $product->get_name(),
					'description' => $product->get_description()
				];
			}
		}
	
		$map_data = get_option('whmcs_map_list', array());
		$items = [];
		foreach ($map_data as $product_id => $service_id) {
			$product_name = (isset($subscription[$product_id]['name']) ? $subscription[$product_id]['name'] : '');
			$whmcs_service_name = (isset($service[$service_id]['name']) ? $service[$service_id]['name'] : '');

			if(empty($query) || strpos(strtolower($product_name), strtolower($query)) !== false || strpos(strtolower($whmcs_service_name), strtolower($query)) !== false){
				$items[] = [
					'id' => $product_id,
					'product_id' => $product_id,
					'product_name' => $product_name,
					'whmcs_service_id' => $service_id,
					'whmcs_service_name' => $whmcs_service_name
				];
			}
			//$subscription[]
		}

		//print_r($result['items']);
	


		/* $args = array( 'subscriptions_per_page' => -1 );

		$subscriptions = wcs_get_subscriptions( $args );		

		print_r($subscriptions); */

		/* $result['items'] = [
			['id'=>1, 'product_id' => 1, 'product_name' => 'Test Product 1', 'whmcs_service_id' => 1, 'whmcs_service_name' => 'Test Service 1'],
			['id'=>2, 'product_id' => 2, 'product_name' => 'Test Product 2', 'whmcs_service_id' => 2, 'whmcs_service_name' => 'Test Service 2'],
		];
		$items = [];
		foreach ($result['items'] as $item) {
			if(empty($query) || strpos(strtolower($item['product']), strtolower($query)) !== false || strpos(strtolower($item['whmcs_service']), strtolower($query)) !== false){
				$items[] = [
					'id' => $item['id'],
					'product_id' => $item['product_id'],
					'product_name' => $item['product_name'],
					'whmcs_service_id' => $item['whmcs_service_id'],
					'whmcs_service_name' => $item['whmcs_service_name'],
				];
			}
		} */

		$total = count($items);

		$data = [
			'total' => $total,
			'items' => $items,
		];

		return $data;
	}

	public function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="products[]" value="%1$s" />',
			$item['id']
		);
	}

	public function display_rows() {

		$page = @$_GET['page'];
		$i = 0;
		//Get the records registered in the prepare_items method
		$records = $this->items;

		//print_r($records);

		//Get the columns registered in the get_columns and get_sortable_columns methods
		list($columns, $hidden) = $this->get_column_info();

		//Loop for each record
		if (!empty($records)) {

			foreach ($records as $rec) {
				$i++;
				//Open the line
				echo '<tr id="record_' . $rec['id'] . '" class="' . ($i % 2 ? 'alternate' : '') . '">';
				foreach ($columns as $column_name => $column_display_name) {
					//Style attributes for each col
					$class = "class='$column_name column-$column_name'";
					$style = "";
					/* if ($column_name == 'Price'){
						$style .= 'text-align:right;';
					} */
					if (in_array($column_name, $hidden)) {
						$style .= 'display:none;';
					}

					if ($style != '') {
						$style = " style='$style'";
					}

					$attributes = $class . $style;

					//$row_item = $rec[$column_name];

					if ($column_name != 'cb') {

						/* if ($rec['IsNew'] == true) {
							$row_item = '<strong style="color: #C94615">' . $rec[$column_name] . '</strong>';
						} else {
							$row_item = $rec[$column_name];
						} */

						$row_item = $rec[$column_name];
					}
					//print_r($column_display_name);
					switch ($column_name) {
					case "product_name":
						//$row_item = $rec[$column_name];
						/* if ($rec['IsNew'] == true) {
							$params = $_GET;
							unset($params['action']);
							unset($params['products']);

							$params['action'] = 'Import Selected Product';
							$params['products'] = [$rec['Sku']];

							$query_params = http_build_query($params);
							$url = admin_url('admin.php?') . $query_params;
							$link = '<a href="' . $url . '" title="Import"><strong>Import</strong></a>';

							echo '<td ' . $attributes . '>' . $row_item . '<div class="row-actions">' . $link . '</div></td>';
						} else {
							echo '<td ' . $attributes . '>' . $row_item . '</td>';
						} */

						//$url = admin_url('admin.php?page=woo_whmcs_add_product_service_mapping');
						$link = '<span class="edit"><a href="#" title="Edit" data-product_id="' . $rec['product_id'] .'" data-service_id="' . $rec['whmcs_service_id'] .'" class="link-edit-row">Edit</a> | </span>';
						$link .= '<span class="trash"><a href="#" title="Hapus" data-id="' . $rec['id'] .'" class="link-delete-row">Delete</a></span>';

						echo '<td ' . $attributes . '>' . $row_item . '<div class="row-actions">' . $link . '</div></td>';

						//echo '<td ' . $attributes . '>' . $row_item . '</td>';

						break;
					/* case "Name":
						echo '<td ' . $attributes . '><strong style="color: blue">' . $rec[$column_name] . '</strong></td>';
						break; */
					default:
						if ($column_name == 'cb') {
							echo '<td scope="row" class="' . "$column_name column-$column_name check-column" . '" style="padding: 8px 10px;">' . $this->column_cb($rec) . '</td>';
						} else {
							//$row_item = $rec[$column_name];
							echo '<td ' . $attributes . '>' . $row_item . '</td>';
						}
					}
				}
				//Close the line
				echo '</tr>';
			}
		}
	}

	function extra_tablenav($which) {
		if ($which == "top") {
			?>
			<div class="alignleft">
				<button class="button button-secondary btn-delete-selected" title="Delete Selected" disabled style="color: #b32d2e">
					<span class="dashicons dashicons-trash" style="padding-top: 4px;"></span>&nbsp;&nbsp;<span class='btn-delete-selected-text'>Delete Selected</span>
				</button>
			</div>
			<?php
		}
	}
}