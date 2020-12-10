<?php
/**
 * Plugin Name: Elberos User Cabinet
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
		add_filter('timber/loader/loader', 'Elberos_User_Cabinet_Plugin::twig_loader');
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
			"site:user:login", "/login",
			"@user-cabinet/login.twig",
			[
				'title' => 'Авторизация',
				'description' => 'Авторизация',
				'context' => function($site, $context)
				{
					return $context;
				}
			]
		);
		
		$site->add_route
		(
			"site:user:logout", "/logout",
			"@user-cabinet/logout.twig",
			[
				'title' => 'Выход',
				'description' => 'Выход',
				'context' => function($site, $context)
				{
					return $context;
				}
			]
		);
		
		$site->add_route
		(
			"site:user:register", "/register",
			"@user-cabinet/register.twig",
			[
				'title' => 'Регистрация',
				'description' => 'Регистрация',
				'context' => function($site, $context)
				{
					return $context;
				}
			]
		);
		
		$site->add_route
		(
			"site:user:profile", "/profile",
			"@user-cabinet/profile.twig",
			[
				'title' => 'Профиль',
				'description' => 'Профиль',
				'context' => function($site, $context)
				{
					return $context;
				}
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
	 * Twig
	 */
	public static function twig_loader($loader)
	{
		$loader->addPath(__DIR__ . "/templates", "user-cabinet");
		return $loader;
	}
}

include __DIR__ . "/include/api.php";

Elberos_User_Cabinet_Plugin::init();
\Elberos\UserCabinet\Api::init();

}