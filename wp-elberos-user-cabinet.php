<?php
/**
 * Plugin Name: WordPress User Cabinet
 * Description: User Cabinet plugin for WordPress
 * Version:     0.1.0
 * Author:      Elberos team <support@elberos.org>
 * License:     Apache License 2.0
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

if ( !class_exists( 'Elberos_User_Cabinet_Plugin' ) ) 
{

class Elberos_User_Cabinet_Plugin
{
	
	
	/**
	 * Init Plugin
	 */
	public static function init()
	{
		add_action
		(
			'admin_init', 
			function()
			{
				require_once __DIR__ . "/include/admin-clients.php";
			}
		);
		add_action('admin_menu', 'Elberos_User_Cabinet_Plugin::register_admin_menu');
		add_action('elberos_setup_after', 'Elberos_User_Cabinet_Plugin::elberos_setup_after');
		add_action('elberos_register_routes', 'Elberos_User_Cabinet_Plugin::elberos_register_routes');
		add_action('elberos_twig', 'Elberos_User_Cabinet_Plugin::elberos_twig', 100);
		
		/* User register */
		add_action('elberos_user_recovery_password1_after', '\\Elberos\\UserCabinet\\Api::recovery_password1_send_email');
		
		/* User cabinet menu */
		add_filter('elberos_user_cabinet_menu', 'Elberos_User_Cabinet_Plugin::elberos_user_cabinet_menu');
		
		/* Remove plugin updates */
		add_filter('site_transient_update_plugins', 'Elberos_User_Cabinet_Plugin::filter_plugin_updates');
		
		/* Init entity */
		\Elberos\UserCabinet\User::init();
	}
	
	
	
	/**
	 * Remove plugin updates
	 */
	public static function filter_plugin_updates($value)
	{
		$name = plugin_basename(__FILE__);
		if (isset($value->response[$name]))
		{
			unset($value->response[$name]);
		}
		return $value;
	}
	
	
	
	/**
	 * Register Admin Menu
	 */
	public static function register_admin_menu()
	{
		add_menu_page
		(
			'Клиенты', 'Клиенты',
			'manage_options', 'elberos-user-cabinet',
			function ()
			{
				\Elberos\UserCabinet\Clients::show();
			},
			null,
			35
		);
	}
	
	
	
	/**
	 * Register routes
	 */
	public static function elberos_register_routes($site)
	{
		$site->add_route
		(
			"site:cabinet:login", "/cabinet/login/",
			"@user-cabinet/login.twig",
			[
				'title' => 'Авторизация',
				'description' => 'Авторизация',
			]
		);
		
		$site->add_route
		(
			"site:cabinet:logout", "/cabinet/logout/",
			null,
			[
				'title' => 'Авторизация',
				'description' => 'Авторизация',
				'render' => function($site)
				{
					$res = \Elberos\UserCabinet\Api::api_logout($site);
					header('Location: ' . site_url("/"));
					return "";
				},
			]
		);
		
		$site->add_route
		(
			"site:cabinet:recovery_password1", "/cabinet/recovery_password1/",
			"@user-cabinet/recovery_password1.twig",
			[
				'title' => 'Восстановить пароль',
				'description' => 'Восстановить пароль',
			]
		);
		
		$site->add_route
		(
			"site:cabinet:recovery_password2", "/cabinet/recovery_password2/",
			"@user-cabinet/recovery_password2.twig",
			[
				'title' => 'Восстановить пароль',
				'description' => 'Восстановить пароль',
			]
		);
		
		$site->add_route
		(
			"site:cabinet:register", "/cabinet/register/",
			"@user-cabinet/register.twig",
			[
				'title' => 'Регистрация',
				'description' => 'Регистрация',
				'render' => function ($site)
				{
					$user_fields = \Elberos\UserCabinet\User::create("register", []);
					$site->context['user_fields'] = $user_fields;
					return null;
				},
			]
		);
		
		$site->add_route
		(
			"site:cabinet", "/cabinet/",
			"@user-cabinet/cabinet.twig",
			[
				'title' => 'Личный кабинет',
				'description' => 'Личный кабинет',
			]
		);
		
		$site->add_route
		(
			"site:cabinet:profile", "/cabinet/profile/",
			"@user-cabinet/profile.twig",
			[
				'title' => 'Профиль',
				'description' => 'Профиль',
				'render' => function ($site)
				{
					$user_fields = \Elberos\UserCabinet\User::create("profile", $site->current_user);
					$site->context['user_fields'] = $user_fields;
					return null;
				},
			]
		);
		
		$site->add_route
		(
			"site:cabinet:change_password", "/cabinet/change_password/",
			"@user-cabinet/change_password.twig",
			[
				'title' => 'Изменить пароль',
				'description' => 'пароль',
			]
		);
		
		$site->add_route
		(
			"site:cabinet:change_email", "/cabinet/change_email/",
			"@user-cabinet/change_email.twig",
			[
				'title' => 'Изменить E-mail',
				'description' => 'Изменить E-mail',
			]
		);
	}
	
	
	
	/**
	 * Setup after
	 */
	public static function elberos_setup_after($site)
	{
		global $wpdb;
		
		list($jwt, $current_user) = \Elberos\UserCabinet\Api::get_current_user();
		$site->jwt = $jwt;
		$site->current_user = $current_user;
	}
	
	
	
	/**
	 * User cabinet menu
	 */
	public static function elberos_user_cabinet_menu($menu)
	{
		$menu[] =
		[
			"route_name" => "site:cabinet:profile",
			"href" => "/cabinet/profile/",
			"label" => "Профиль",
		];
		$menu[] =
		[
			"route_name" => "site:cabinet:change_password",
			"href" => "/cabinet/change_password/",
			"label" => "Изменить пароль",
		];
		$menu[] =
		[
			"route_name" => "site:cabinet:change_email",
			"href" => "/cabinet/change_email/",
			"label" => "Изменить email",
		];
		return $menu;
	}
	
	
	
	/**
	 * Twig
	 */
	public static function elberos_twig($twig)
	{
		$twig->getLoader()->addPath(__DIR__ . "/templates", "user-cabinet");
	}
}

include __DIR__ . "/include/api.php";
include __DIR__ . "/entity/User.php";

Elberos_User_Cabinet_Plugin::init();
\Elberos\UserCabinet\Api::init();

}