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


/* Check if Wordpress */
if (!defined('ABSPATH')) exit;


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
	 * Search name
	 */
	function column_search_name($item)
	{
		if ($item["type"] == 1) return $item["name"] . " " . $item["surname"];
		if ($item["type"] == 2) return $item["company_name"];
		return "";
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
	 * Returns true if show filter
	 */
	function is_show_filter()
	{
		return true;
	}
	
	
	
	/**
	 * Returns filter elements
	 */
	function get_filter()
	{
		return [
			"client_id",
			"type",
			"email",
			"phone",
		];
	}
	
	
	
	/**
	 * Show filter item
	 */
	function show_filter_item($item_name)
	{
		if ($item_name == "type")
		{
			?>
			<select name="type" class="web_form_value">
				<option value="">Тип клиента</option>
				<option value="1" <?= \Elberos\is_get_selected("type", "1") ?>>Физ лицо</option>
				<option value="2" <?= \Elberos\is_get_selected("type", "2") ?>>Юр лицо</option>
			</select>
			<?php
		}
		else if ($item_name == "email")
		{
			?>
			<input type="text" name="email" class="web_form_value" placeholder="E-mail"
				value="<?= esc_html(isset($_GET["email"]) ? $_GET["email"] : "") ?>" />
			<?php
		}
		else if ($item_name == "client_id")
		{
			?>
			<input type="text" name="client_id" class="web_form_value" placeholder="ID Клиента"
				value="<?= esc_html(isset($_GET["client_id"]) ? $_GET["client_id"] : "") ?>" />
			<?php
		}
		else if ($item_name == "phone")
		{
			?>
			<input type="text" name="phone" class="web_form_value" placeholder="Телефон"
				value="<?= esc_html(isset($_GET["phone"]) ? $_GET["phone"] : "") ?>" />
			<?php
		}
		else
		{
			parent::show_filter_item($item_name);
		}
	}
	
	
	
	/**
	 * Process items params
	 */
	function prepare_table_items_filter($params)
	{
		global $wpdb;
		
		$params = parent::prepare_table_items_filter($params);
		
		/* Type */
		if (isset($_GET["type"]))
		{
			$params["where"][] = "type=:type";
			$params["args"]["type"] = (int)$_GET["type"];
		}
		
		/* ID Клиента */
		if (isset($_GET["email"]))
		{
			$params["where"][] = "email like :email";
			$params["args"]["email"] = "%" . $wpdb->esc_like(\Elberos\mb_trim($_GET["email"])) . "%";
		}
		
		/* Email */
		if (isset($_GET["client_id"]))
		{
			$params["where"][] = "id=:client_id";
			$params["args"]["client_id"] = \Elberos\mb_trim($_GET["client_id"]);
		}
		
		/* Phone */
		if (isset($_GET["phone"]))
		{
			$params["where"][] = "phone like :phone";
			$params["args"]["phone"] = "%" . $wpdb->esc_like(\Elberos\mb_trim($_GET["phone"])) . "%";
		}
		
		return $params;
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