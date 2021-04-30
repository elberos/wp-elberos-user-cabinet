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


if ( !class_exists( Clients::class ) ) 
{

class Clients
{
	public static function show()
	{
		$table = new Clients_Table();
		$table->display();		
	}
}


class Clients_Table extends \WP_List_Table 
{
	
	function __construct()
	{
		global $status, $page;

		parent::__construct(array(
			'singular' => 'elberos-user-cabinet',
			'plural' => 'elberos-user-cabinet',
		));
	}
	
	function get_table_name()
	{
		global $wpdb;
		return $wpdb->prefix . 'elberos_clients';
	}
	
	// Вывод значений по умолчанию
	function get_default()
	{
		return array(
			'id' => 0,
			'name' => '',
			'email' => '',
			'phone' => '',
		);
	}
		
	// Колонки таблицы
	function get_columns()
	{
		$columns = array(
			'cb' => '<input type="checkbox" />', 
			'name' => __('Имя', 'elberos-user-cabinet'),
			'email' => __('Email', 'elberos-user-cabinet'),
			'phone' => __('Телефон', 'elberos-user-cabinet'),
			'gmtime_add' => __('Дата регистрации', 'elberos-user-cabinet'),
			'buttons' => __('', 'elberos-user-cabinet'),
		);
		return $columns;
	}
	
	// Сортируемые колонки
	function get_sortable_columns()
	{
		$sortable_columns = array(
			'name' => array('name', true),
			'gmtime_add' => array('gmtime_add', true),
		);
		return $sortable_columns;
	}
	
	// Действия
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
	
	// Вывод каждой ячейки таблицы
	function column_default($item, $column_name)
	{
		return isset($item[$column_name]) ? $item[$column_name] : '';
	}
	
	// Заполнение колонки cb
	function column_cb($item)
	{
		return sprintf(
			'<input type="checkbox" name="id[]" value="%s" />',
			$item['id']
		);
	}
	
	// Дата регистрации
	function column_gmtime_add($item)
	{
		return \Elberos\wp_from_gmtime($item['gmtime_add']);
	}
	
	// Колонка name
	function column_buttons($item)
	{
		$actions = array(
			'edit' => sprintf(
				'<a href="?page=elberos-user-cabinet&action=edit&id=%s">%s</a>',
				$item['id'], 
				__('Edit', 'elberos-user-cabinet')
			),
			/*
			'delete' => sprintf(
				'<a href="?page=elberos-user-cabinet&action=show_delete&id=%s">%s</a>',
				$item['id'],
				__('Delete', 'elberos-user-cabinet')
			),*/
		);
		
		return $this->row_actions($actions, true);
	}
	
	// Создает элементы таблицы
	function prepare_items()
	{
		global $wpdb;
		$table_name = $this->get_table_name();
		
		$per_page = 10; 

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		
		$this->_column_headers = array($columns, $hidden, $sortable);
	   
		$this->process_bulk_action();

		$total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

		$is_deleted = isset($_REQUEST['is_deleted']) ? $_REQUEST['is_deleted'] : "";
		$paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
		$orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : '';
		$order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : '';
		
		if ($order == "" && $orderby == ""){ $orderby = "name"; $order = "asc"; }
		if ($orderby == ""){ $orderby = "id"; }
		if ($order == ""){ $order = "asc"; }
		
		$where = "";
		if ($is_deleted == "true") $where = "where is_deleted = 1";
		else $where = "where is_deleted = 0";
		
		$sql = $wpdb->prepare
		(
			"SELECT t.* FROM $table_name as t $where
			ORDER BY $orderby $order LIMIT %d OFFSET %d",
			$per_page, $paged * $per_page
		);
		$this->items = $wpdb->get_results($sql, ARRAY_A);

		$this->set_pagination_args(array(
			'total_items' => $total_items, 
			'per_page' => $per_page,
			'total_pages' => ceil($total_items / $per_page) 
		));
	}
	
	
	function process_bulk_action()
	{
		global $wpdb;
		$table_name = $this->get_table_name();

		if ($this->current_action() == 'trash')
		{
			$ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
			if (is_array($ids)) $ids = implode(',', $ids);

			if (!empty($ids)) {
				$wpdb->query("update $table_name set is_deleted=1 WHERE id IN($ids)");
			}
		}
		
		if ($this->current_action() == 'notrash')
		{
			$ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
			if (is_array($ids)) $ids = implode(',', $ids);

			if (!empty($ids)) {
				$wpdb->query("update $table_name set is_deleted=0 WHERE id IN($ids)");
			}
		}
		
		if ($this->current_action() == 'delete')
		{
			$ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
			if (is_array($ids)) $ids = implode(',', $ids);

			if (!empty($ids)) {
				$wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
			}
		}
	}
	
	// Валидация значений
	function item_validate($item)
	{
		$password1 = isset($_POST['password1']) ? $_POST['password1'] : "";
		$password2 = isset($_POST['password2']) ? $_POST['password2'] : "";
		
		if ($password1 != "" and $password1 != $password2)
		{
			return "Пароли не совпадают";
		}
		
		return true;
	}
	
	function process_item($item)
	{
		$item = \Elberos\Update::intersect
		(
			$item,
			[
				"email",
				"phone",
				"name",
			]
		);
		
		$item_id = (int) (isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
		if ($item_id == 0)
		{
			$item['gmtime_add'] = \Elberos\dbtime();
		}
		
		return $item;
	}
	
	function after_process_item($action, $success_save, $item)
	{
		global $wpdb;
		
		$item_id = $item['id'];
		if ($success_save)
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
						'id' => $item_id
					]
				);
			}
		}
	}
	
	function css()
	{
	}
	
	function display_add_or_edit()
	{
		global $wpdb;
		
		$res = \Elberos\Update::wp_save_or_update($this, basename(__FILE__));
		
		$message = $res['message'];
		$notice = $res['notice'];
		$item = $res['item'];
		
		?>
		
		<div class="wrap">
			<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
			<h1><?php _e($item['id'] > 0 ? 'Редактировать клиента' : 'Добавить клиента', 'elberos-user-cabinet')?></h1>
			
			<?php if (!empty($notice)): ?>
				<div id="notice" class="error"><p><?php echo $notice ?></p></div>
			<?php endif;?>
			<?php if (!empty($message)): ?>
				<div id="message" class="updated"><p><?php echo $message ?></p></div>
			<?php endif;?>
			
			<a type="button" class='button-primary' href='?page=elberos-user-cabinet'> Back </a>
			
			<form id="form" method="POST">
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
				<input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>
				<div class="metabox-holder" id="poststuff">
					<div id="post-body">
						<div id="post-body-content">
							<div class="add_or_edit_form" style="width: 60%">
								<? $this->display_form($item) ?>
							</div>
							<input type="submit" class="button-primary" value="<?php _e('Save', 'elberos-user-cabinet')?>" >
						</div>
					</div>
				</div>
			</form>
		</div>
		
		<?php
	}
	
	function display_form($item)
	{
		?>
		<p>
			<label for="name"><?= __('Имя', 'elberos-user-cabinet') ?>:</label>
		<br>	
			<input id="name" name="name" type="text" style="width: 100%" required
				value="<?php echo esc_attr($item['name'])?>" >
		</p>
		<p>
			<label for="email"><?= __('Email', 'elberos-user-cabinet') ?>:</label>
		<br>	
			<input id="email" name="email" type="text" style="width: 100%" required
				value="<?php echo esc_attr($item['email'])?>" >
		</p>
		<p>
			<label for="phone"><?= __('Телефон', 'elberos-user-cabinet') ?>:</label>
		<br>	
			<input id="phone" name="phone" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['phone'])?>" >
		</p>
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
	
	function display_table()
	{
		$is_deleted = isset($_REQUEST['is_deleted']) ? $_REQUEST['is_deleted'] : "";
		
		$this->prepare_items();
		$message = "";
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php echo get_admin_page_title() ?>
			</h1>
			<a href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=elberos-user-cabinet&action=add');?>"
				class="page-title-action"
			>
				<?php _e('Add new', 'template')?>
			</a>
			<hr class="wp-header-end">
			
			<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
			<?php echo $message; ?>
			
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
			// выводим таблицу на экран где нужно
			echo '<form action="" method="POST">';
			parent::display();
			echo '</form>';
			?>

		</div>
		<?php
	}
	
	function display()
	{
		$action = $this->current_action();
		$this->css();
		if ($action == 'add' or $action == 'edit')
		{
			$this->display_add_or_edit();
		}
		else
		{
			$this->display_table();
		}
	}
	
}

}