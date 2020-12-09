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

if ( !class_exists( Api::class ) ) 
{

class Api
{
	
	public static function init()
	{
		add_action('rest_api_init', '\\Elberos\\UserCabinet\\Api::register_api');
	}
	
	
	/**
	 * Register API
	 */
	public static function register_api()
	{
		register_rest_route
		(
			'elberos_user_cabinet',
			'login',
			array(
				'methods' => 'POST',
				'callback' => function ($arr){ return static::login($arr); },
			)
		);
		register_rest_route
		(
			'elberos_user_cabinet',
			'logout',
			array(
				'methods' => 'POST',
				'callback' => function ($arr){ return static::logout($arr); },
			)
		);
	}
	
	
	/**
	 * Login form
	 */
	public static function login($params)
	{
		global $wpdb;
		
		/* Check wp nonce */
		$forms_wp_nonce = isset($_POST["_wpnonce"]) ? $_POST["_wpnonce"] : "";
		$wp_nonce_res = (int)wp_verify_nonce($forms_wp_nonce, 'wp_rest');
		if ($wp_nonce_res == 0)
		{
			return 
			[
				"success" => false,
				"message" => __("Ошибка формы. Перезагрузите страницу.", "elberos-core"),
				"fields" => [],
				"code" => -1,
			];
		}
		
		if (!defined('SECURE_AUTH_KEY'))
		{
			return
			[
				"success" => false,
				"message" => "Auth tokens are undefined",
				"fields" => [],
				"code" => -1,
			];
		}
		
		$login = isset($_POST["login"]) ? $_POST["login"] : "";
		$password = isset($_POST["password"]) ? $_POST["password"] : "";
		
		/* Check password */
		global $wpdb;
		$table_clients = $wpdb->prefix . 'elberos_clients';
		$sql = $wpdb->prepare
		(
			"SELECT * FROM $table_clients WHERE email = %s", $login
		);
		$row = $wpdb->get_row($sql, ARRAY_A);
		if (!$row)
		{
			return
			[
				"success" => false,
				"message" => "Неверный логин или пароль",
				"fields" => [],
				"code" => -1,
			];
		}
		
		$password_hash = $row['password'];
		if (!password_verify($password, $password_hash))
		{
			return
			[
				"success" => false,
				"message" => "Неверный логин или пароль",
				"fields" => [],
				"code" => -1,
			];
		}
		
		/* Create JWT */
		$expire = time() + 30*24*60*60;
		$session = wp_generate_password(64, false, false);
		$jwt = [
			"u" => $row['id'],
			"l" => $row['email'],
			"s" => $session,
			"e" => $expire,
		];
		$jwt = static::create_jwt($jwt);
		
		/* Set cookie */
		setcookie('auth_token', $jwt, time() + 30*24*60*60, '/');
		
		return
		[
			"success" => true,
			"message" => "Ok",
			"fields" => [],
			"jwt" => $jwt,
			"code" => 1,
		];
	}
	
	
	
	/**
	 * Logout form
	 */
	public static function logout($params)
	{
		/* Check wp nonce */
		$forms_wp_nonce = isset($_POST["_wpnonce"]) ? $_POST["_wpnonce"] : "";
		$wp_nonce_res = (int)wp_verify_nonce($forms_wp_nonce, 'wp_rest');
		if ($wp_nonce_res == 0)
		{
			return 
			[
				"success" => false,
				"message" => __("Ошибка формы. Перезагрузите страницу.", "elberos-core"),
				"fields" => [],
				"code" => -1,
			];
		}
		
		/* Check JWT */
		if (!defined('SECURE_AUTH_KEY'))
		{
			return
			[
				"success" => false,
				"message" => "Auth tokens are undefined",
				"fields" => [],
				"code" => -1,
			];
		}
		
		
		
		
		return
		[
			"success" => true,
			"message" => "Ok",
			"fields" => [],
			"code" => 1,
		];
	}
	
	
	
	
	/**
	 * Create JWT
	 */
	public static function create_jwt($data)
	{
		$data_json = json_encode($data);
		$data_b64 = \Elberos\base64_encode_url($data_json);
		$head_b64 = \Elberos\base64_encode_url(json_encode(['alg'=>'HS512','typ'=>'JWT']));
		
		/* Sign */
		$text = $head_b64 . '.' . $data_b64;
		$out = hash_hmac('SHA512', $text, SECURE_AUTH_KEY, true);
		$out = \Elberos\base64_encode_url($out);
		
		return $text . '.' . $out;
	}
	
	
	
	/**
	 * Decode JWT
	 */
	public static function decode_jwt($text)
	{
		$arr = explode(".", $text);
		if (count($arr) != 3) return null;
		
		$head_b64 = $arr[0];
		$data_b64 = $arr[1];
		$sign_b64 = $arr[2];
		$data_json = @\Elberos\base64_decode_url($data_b64);
		$data = @json_decode($data_json);
		if ($data == null) return null;
		
		/* Validate sign */
		$text = $head_b64 . '.' . $data_b64;
		$hash = hash_hmac('SHA512', $text, SECURE_AUTH_KEY);
		$verify = hash_equals($sign, $hash);
		$verify = @openssl_verify($text, $sign, $pk, 'SHA512');
		if (!$verify) return null;
		
		return $data;
	}
}

}