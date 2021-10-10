<?php

/*!
 *  Elberos User Cabinet
 *
 *  (c) Copyright 2019-2020 "Ildar Bikmamatov" <support@elberos.org>
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      https://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */


namespace Elberos\UserCabinet;

if ( !class_exists( Clients_Table::class ) && class_exists( \Elberos\Table::class ) ) 
{

class Clients_Table extends \Elberos\Table 
{
	
	/**
	 * Table name
	 */
	function get_table_name()
	{
		global $wpdb;
		return $wpdb->base_prefix . 'elberos_clients';
	}
	
	
	
	/**
	 * Page name
	 */
	function get_page_name()
	{
		return "elberos-user-cabinet";
	}
	
	
	
	/**
	 * Create struct
	 */
	static function createStruct()
	{
		$struct = \Elberos\UserCabinet\User::create
		(
			"admin_clients",
			function ($struct)
			{
				$struct->table_fields =
				[
					"id",
					"type",
					"search_name",
					"email",
					"phone",
					"gmtime_add",
				];
				
				$struct->form_fields =
				[
					"type",
					"name",
					"surname",
					"company_name",
					"search_name",
					"email",
					"phone",
				];
				return $struct;
			}
		);
		
		return $struct;
	}
	
	
	
	/**
	 * Init struct
	 */
	function initStruct()
	{
		parent::initStruct();
	}
	
	
	
	/**
	 * Column buttons
	 */
	function column_buttons($item)
	{
		$page_name = $this->get_page_name();
		return sprintf
		(
			'<a href="?page=' . $page_name . '&action=edit&id=%s">%s</a>',
			$item['id'], 
			__('Открыть', 'elberos-core')
		);
	}
	
	
	
	/**
	 * Действия
	 */
	function get_bulk_actions()
	{
		$is_deleted = isset($_REQUEST['is_deleted']) ? $_REQUEST['is_deleted'] : "";
		if ($is_deleted != 'true')
		{
			$actions = array(
				'trash' => 'Переместить в корзину',
			);
		}
		else
		{
			$actions = array(
				'notrash' => 'Восстановить из корзины',
				'delete' => 'Удалить навсегда',
			);
		}
		return $actions;
	}
	
	
	
	/**
	 * Process bulk action
	 */
	function process_bulk_action()
	{
		$action = $this->current_action();
		
		/* Edit items */
		if (in_array($action, ['add', 'edit']))
		{
			parent::process_bulk_action();
		}
		
		/* Move to trash items */
		if (in_array($action, ['trash', 'notrash', 'delete']))
		{
			parent::process_bulk_action();
		}
	}
	
	
	
	/**
	 * Get item
	 */
	function do_get_item()
	{
		parent::do_get_item();
	}
	
	
	
	/**
	 * Item validate
	 */
	function item_validate($item)
	{
		$password1 = isset($_POST['password1']) ? $_POST['password1'] : "";
		$password2 = isset($_POST['password2']) ? $_POST['password2'] : "";
		
		if ($password1 != "" and $password1 != $password2)
		{
			return "Пароли не совпадают";
		}
		
		return "";
	}
	
	
	
	/**
	 * Process item
	 */
	function process_item($item, $old_item)
	{
		$item_id = (int) (isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
		if ($item_id == 0)
		{
			$item['gmtime_add'] = \Elberos\dbtime();
		}
		return $item;
	}
	
	
	
	/**
	 * Process item after
	 */
	function process_item_after($item, $old_item, $action, $success)
	{
		global $wpdb;
		
		if ($success)
		{
			$password1 = isset($_POST['password1']) ? $_POST['password1'] : "";
			if ($password1 != "")
			{
				$table_name = $this->get_table_name();
				$wpdb->update
				(
					$table_name,
					[
						'password' => password_hash($password1, PASSWORD_BCRYPT, ['cost'=>11]),
					],
					[
						'id' => $this->form_item_id
					]
				);
			}
		}
	}
	
	
	
	/**
	 * Prepare table items
	 */
	function prepare_table_items()
	{
		$args = [];
		$where = [];
		if (isset($_GET['is_deleted']) && $_GET['is_deleted']) $where[] = "is_deleted=1";
		else $where[] = "is_deleted=0";
		
		$per_page = $this->per_page();
		list($items, $total_items, $pages, $page) = \Elberos\wpdb_query
		([
			"table_name" => $this->get_table_name(),
			"where" => implode(" and ", $where),
			"args" => $args,
			"per_page" => $per_page,
		]);
		
		$this->items = $items;
		$this->set_pagination_args(array(
			'total_items' => $total_items, 
			'per_page' => $per_page,
			'total_pages' => ceil($total_items / $per_page) 
		));
	}
	
	
	
	/**
	 * CSS
	 */
	function display_css()
	{
		parent::display_css();
	}
	
	
	
	/**
	 * Display table sub
	 */
	function display_table_sub()
	{
		$page_name = $this->get_page_name();
		$is_deleted = isset($_REQUEST['is_deleted']) ? $_REQUEST['is_deleted'] : "";
		?>
		<ul class="subsubsub">
			<li>
				<a href="admin.php?page=elberos-user-cabinet"
					class="<?= ($is_deleted != "true" ? "current" : "")?>"  >Все</a> |
			</li>
			<li>
				<a href="admin.php?page=elberos-user-cabinet&is_deleted=true"
					class="<?= ($is_deleted == "true" ? "current" : "")?>" >Корзина</a>
			</li>
		</ul>
		<?php
	}
	
	
	
	/**
	 * Display table
	 */
	function display_table()
	{
		// var_dump( $this->struct );
		// var_dump( $this->items );
		parent::display_table();
	}
	
	
	
	/**
	 * Display form sub
	 */
	function display_form_sub()
	{
		$page_name = $this->get_page_name();
		?>
		<br/>
		<a type="button" class='button-primary' href='?page=<?= $page_name ?>'> Back </a>
		<br/>
		<?php
	}
	
	
	
	/**
	 * Display form
	 */
	function display_form()
	{
		parent::display_form();
		
		?>
		<h2 style='padding-left: 0;'>Смена пароля</h2>
		<p>
			<label for="password1"><?= __('Введите пароль', 'elberos-user-cabinet') ?>:</label>
		<br>	
			<input id="password1" name="password1" type="password" style="width: 100%" value="" >
		</p>
		<p>
			<label for="password2"><?= __('Повторите пароль', 'elberos-user-cabinet') ?>:</label>
		<br>	
			<input id="password2" name="password2" type="password" style="width: 100%" value="" >
		</p>
		<?php
		
	}
	
	
	
	/**
	 * Display form buttons
	 */
	function display_form_buttons()
	{
		parent::display_form_buttons();
	}
	
}

}