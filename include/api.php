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


if ( !class_exists( Api::class ) ) 
{

class Api
{
	
	static $ENABLE_CAPTCHA = true;
	
	
	
	/**
	 * Init api
	 */
	public static function init()
	{
		add_action('elberos_register_routes', '\\Elberos\\UserCabinet\\Api::register_routes');
		add_action('elberos_user_register_validation', 
			'\\Elberos\\UserCabinet\\Api::elberos_user_register_validation_email');
	}
	
	
	
	/**
	 * Register API
	 */
	public static function register_routes($site)
	{
		$site->add_api("elberos_cabinet", "login",
			"\\Elberos\\UserCabinet\\Api::api_login");
		$site->add_api("elberos_cabinet", "register",
			"\\Elberos\\UserCabinet\\Api::api_register");
		$site->add_api("elberos_cabinet", "recovery_password1", 
			"\\Elberos\\UserCabinet\\Api::api_recovery_password1");
		$site->add_api("elberos_cabinet", "recovery_password2", 
			"\\Elberos\\UserCabinet\\Api::api_recovery_password2");
		$site->add_api("elberos_cabinet", "update_profile", 
			"\\Elberos\\UserCabinet\\Api::api_update_profile");
		$site->add_api("elberos_cabinet", "change_password", 
			"\\Elberos\\UserCabinet\\Api::api_change_password");
		$site->add_api("elberos_cabinet", "change_email", 
			"\\Elberos\\UserCabinet\\Api::api_change_email");
	}
	
	
	
	/**
	 * Register form
	 */
	public static function api_register($site)
	{
		global $wpdb;
		
		/* Captcha check */
		if (static::$ENABLE_CAPTCHA)
		{
			$captcha = isset($_POST["captcha"]) ? $_POST["captcha"] : "";
			if (!\Elberos\captcha_validation($captcha))
			{
				return
				[
					"message" => "Неверный код с картинки.<br/>Попробуйте обновить картинку, кликнув по ней, и ввести код заново",
					"code" => -100,
				];
			}
		}
		
		/* Check password */
		$password1 = isset($_POST['password1']) ? $_POST['password1'] : "";
		$password2 = isset($_POST['password2']) ? $_POST['password2'] : "";
		if ($password1 == "")
		{
			return
			[
				"message" => "Пустой пароль",
				"code" => -1,
			];
		}
		if ($password1 != "" and $password1 != $password2)
		{
			return
			[
				"message" => "Пароли не совпадают",
				"code" => -1,
			];
		}
		
		/* Register user */
		$form_data = stripslashes_deep($_POST);
		$res = static::user_register($form_data, $password1);
		$validation = isset($res["validation"]) ? $res["validation"] : null;
		
		return
		[
			"message" => $res["message"],
			"fields" => ($validation && isset($validation["fields"])) ? $validation["fields"] : [],
			"code" => $res["code"],
		];
	}
	
	
	
	/**
	 * Register form
	 */
	public static function user_register($form_data, $password = null)
	{
		global $wpdb;
		
		/* Validation */
		$params = apply_filters
		(
			"elberos_user_register_validation",
			[
				"code" => -1,
				"form_data" => $form_data,
				"password" => $password,
				"validation" => [],
			]
		);
		$validation = $params["validation"];
		if ($validation != null && count($validation) > 0)
		{
			$validation_error = isset($params["validation"]["error"]) ?
				$params["validation"]["error"] :
				__("Ошибка. Проверьте корректность данных", "elberos");
			return
			[
				"message" => $validation_error,
				"validation" => $validation,
				"code" => -2,
			];
		}
		
		/* Get login */
		$email = sanitize_user(trim(isset($form_data["email"]) ? $form_data["email"] : ""));
		
		/* Find user */
		$table_clients = $wpdb->base_prefix . 'elberos_clients';
		$sql = $wpdb->prepare
		(
			"SELECT * FROM $table_clients WHERE email = %s", $email
		);
		$client = $wpdb->get_row($sql, ARRAY_A);
		if ($client)
		{
			return
			[
				"message" => "Такой email уже существует",
				"code" => -1,
			];
		}
		
		/* Process item */
		$user_fields = \Elberos\UserCabinet\User::create("register");
		$item = $user_fields->getDefault();
		$item = $user_fields->update($item, $form_data);
		$item = $user_fields->processItem($item);
		
		/* Set user type = 1 as default */
		if ($item["type"] == 0) $item["type"] = 1;
		$item["email"] = trim($item["email"]);
		
		/* Set password */
		if ($password)
		{
			$password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost'=>11]);
			$item['password'] = $password_hash;
			$item['gmtime_add'] = \Elberos\dbtime();
		}
		
		/* gmtime add */
		$item["gmtime_add"] = gmdate("Y-m-d H:i:s", time());
		
		/* Apply action */
		$res = apply_filters("elberos_user_register_before", ["user"=>$item, "password"=>$password]);
		$item = $res["user"];
		
		/* Code 1C */
		if (!isset($item['code_1c']))
		{
			$item['code_1c'] = \Elberos\uid();
		}
		
		/* Insert item */
		$wpdb->insert($table_clients, $item);
		$item["id"] = $wpdb->insert_id;
		
		/* Flush captcha */
		if (static::$ENABLE_CAPTCHA)
		{
			\Elberos\flush_captcha();
		}
		
		/* Apply action */
		do_action("elberos_user_register_after", ["user"=>$item, "password"=>$password]);
		
		return
		[
			"message" => "Ok",
			"code" => 1,
			"item" => $item,
		];
	}
	
	
	
	/**
	 * User validation
	 */
	public static function elberos_user_register_validation_email($params)
	{
		$form_data = isset($params["form_data"]) ? $params["form_data"] : [];
		$email = sanitize_user(trim(isset($form_data["email"]) ? $form_data["email"] : ""));
		if ($email == "" || !filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			$params["code"] = -2;
			$params["validation"]["error"] = __("Ошибка. Проверьте корректность данных", "elberos");
			$params["validation"]["fields"]["email"][] = "E-mail не верен";
		}
		return $params;
	}
	
	
	
	/**
	 * Login form
	 */
	public static function api_login($site)
	{
		global $wpdb;
		
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
		
		$login = sanitize_user(isset($_POST["login"]) ? $_POST["login"] : "");
		$password = isset($_POST["password"]) ? $_POST["password"] : "";
		
		/* Check password */
		global $wpdb;
		$table_clients = $wpdb->base_prefix . 'elberos_clients';
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
		
		/* If account is blocked */
		if ($row['is_deleted'])
		{
			return
			[
				"success" => false,
				"message" => "Ваш аккаунт был заблокирован",
				"fields" => [],
				"code" => -1,
			];
		}
		
		/* Create JWT */
		$jwt = static::create_session($row);
		
		/* Apply action */
		do_action("elberos_user_login_after", $row);
		
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
	public static function api_logout($site)
	{
		/* Set cookie */
		$domain = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : "";
		if (is_multisite())
		{
			$arr = explode(".", $domain);
			$arr = array_slice($arr, count($arr) - 2, 2);
			$domain = implode(".", $arr);
			$domain = "." . $domain;
			setcookie('auth_token', '', 0, '/', $domain);
		}
		else
		{
			setcookie('auth_token', '', 0, '/');
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
	 * Recover password1
	 */
	public static function api_recovery_password1($site)
	{
		global $wpdb;
		
		/* Captcha check */
		if (static::$ENABLE_CAPTCHA)
		{
			$captcha = isset($_POST["captcha"]) ? $_POST["captcha"] : "";
			if (!\Elberos\captcha_validation($captcha))
			{
				return
				[
					"message" => "Неверный код с картинки.<br/>Попробуйте обновить картинку, кликнув по ней, и ввести код заново",
					"code" => -100,
				];
			}
		}
		
		/* Get login */
		$login = sanitize_user(isset($_POST["login"]) ? $_POST["login"] : "");
		
		/* Find user */
		$table_clients = $wpdb->base_prefix . 'elberos_clients';
		$sql = $wpdb->prepare
		(
			"SELECT * FROM $table_clients WHERE email = %s", $login
		);
		$client = $wpdb->get_row($sql, ARRAY_A);
		if (!$client)
		{
			return
			[
				"message" => "Неверный email",
				"code" => -1,
			];
		}
		
		/* Recovery code */
		$recovery_code = wp_generate_password(12, false, false);
		$wpdb->update
		(
			$table_clients,
			[
				"recovery_password_code" => $recovery_code,
				"recovery_password_expire" => \Elberos\dbtime( time() + 4*60*60 ),
			],
			[
				"id" => $client['id'],
			]
		);
		
		/* Set recovery code */
		$client["recovery_password_code"] = $recovery_code;
		
		/* Flush captcha */
		if (static::$ENABLE_CAPTCHA)
		{
			\Elberos\flush_captcha();
		}
		
		/* Apply action */
		do_action("elberos_user_recovery_password1_after", $client);
		
		return
		[
			"message" => "Код был выслан на указанную почту",
			"code" => 1,
		];
	}
	
	
	
	/**
	 * Send email
	 */
	public static function recovery_password1_send_email_old($client)
	{
		$recover_link = site_url("/cabinet/recovery_password2/?recovery_password_code=" .
			htmlspecialchars($client['recovery_password_code']) . "&login=" .
			htmlspecialchars($client['email'])
		);
		\Elberos\send_email
		(
			"noreply",
			$client["email"],
			"@user-cabinet/email_recovery_password1.twig",
			[
				"recover_link" => $recover_link,
				"client" => $client,
				"title" => "Восстановление пароля на сайте " . \Elberos\get_site_name(),
			]
		);
	}
	
	
	
	/**
	 * Recover password2
	 */
	public static function api_recovery_password2($site)
	{
		global $wpdb;
		
		/* Get login */
		$login = sanitize_user(isset($_POST["login"]) ? $_POST["login"] : "");
		$code = isset($_POST["code"]) ? $_POST["code"] : "";
		$password1 = isset($_POST["password1"]) ? $_POST["password1"] : "";
		$password2 = isset($_POST["password2"]) ? $_POST["password2"] : "";
		if ($password1 == "")
		{
			return
			[
				"message" => "Пустой пароль",
				"code" => -1,
			];
		}
		if ($password1 != $password2)
		{
			return
			[
				"message" => "Пароли не совпадают",
				"code" => -1,
			];
		}
		
		/* Find user */
		$current_date = \Elberos\dbtime( time() );
		$table_clients = $wpdb->base_prefix . 'elberos_clients';
		$sql = $wpdb->prepare
		(
			"SELECT * FROM $table_clients WHERE email = %s and recovery_password_code = %s and recovery_password_expire > %s and recovery_password_expire is not null",
			$login, $code, $current_date
		);
		$client = $wpdb->get_row($sql, ARRAY_A);
		if (!$client)
		{
			return
			[
				"message" => "Код восстановления неверный или истек",
				"code" => -1,
			];
		}
		if ($client['recovery_password_code'] == "" or $client['recovery_password_code'] != $code)
		{
			return
			[
				"message" => "Код восстановления неверный или истек",
				"code" => -1,
			];
		}
		
		/* Update password */
		$wpdb->update
		(
			$table_clients,
			[
				"password" => password_hash($password1, PASSWORD_BCRYPT, ['cost'=>11]),
				"recovery_password_code" => "",
				"recovery_password_expire" => null,
			],
			[
				"id" => $client['id'],
			]
		);
		
		/* Apply action */
		do_action("elberos_user_recovery_password2_after", [
			"user" => $current_user,
			"new_password" => $password1,
		]);
		
		return
		[
			"message" => "Пароль был успешно изменен",
			"code" => 1,
		];
	}
	
	
	
	/**
	 * Profile form
	 */
	public static function api_update_profile($site)
	{
		global $wpdb;
		
		list($jwt, $current_user) = static::get_current_user();
		if ($current_user == null)
		{
			return
			[
				"message" => "Вы не авторизованы",
				"code" => -1,
			];
		}
		if ($current_user['is_deleted'])
		{
			return
			[
				"message" => "Пользователь заблокирован",
				"code" => -2,
			];
		}
		
		/* Validation */
		$params = apply_filters
		(
			"elberos_user_update_profile_validation",
			[
				"code" => -1,
				"form_data" => $form_data,
				"password" => $password,
				"validation" => [],
			]
		);
		$validation = $params["validation"];
		if ($validation != null && count($validation) > 0)
		{
			$validation_error = isset($params["validation"]["error"]) ?
				$params["validation"]["error"] :
				__("Ошибка. Проверьте корректность данных", "elberos");
			return
			[
				"message" => $validation_error,
				"validation" => $validation,
				"code" => -2,
			];
		}
		
		/* Process item */
		$user_fields = \Elberos\UserCabinet\User::create("profile");
		$item = $user_fields->update($current_user, $_POST);
		$item = $user_fields->processItem($item);
		
		/* Update user profile */
		$table_clients = $wpdb->base_prefix . 'elberos_clients';
		$result = $wpdb->update($table_clients, $item, ['id' => $current_user['id']]);
		
		/* Apply action */
		do_action("elberos_user_update_profile_after", [
			"user" => $current_user,
		]);
		
		return
		[
			"message" => "Данные успешно обновлены",
			"code" => 1,
		];
	}
	
	
	
	/**
	 * Change password
	 */
	public static function api_change_password($site)
	{
		global $wpdb;
		
		list($jwt, $current_user, $current_password_hash) = static::get_current_user();
		if ($current_user == null)
		{
			return
			[
				"message" => "Вы не авторизованы",
				"code" => -1,
			];
		}
		if ($current_user['is_deleted'])
		{
			return
			[
				"message" => "Пользователь заблокирован",
				"code" => -2,
			];
		}
		
		$current_password = isset($_POST['current_password']) ? $_POST['current_password'] : "";
		$new_password1 = isset($_POST['new_password1']) ? $_POST['new_password1'] : "";
		$new_password2 = isset($_POST['new_password2']) ? $_POST['new_password2'] : "";
		if ($new_password1 == "")
		{
			return
			[
				"message" => "Новый пароль пустой",
				"code" => -1,
			];
		}
		if ($new_password1 != "" and $new_password1 != $new_password2)
		{
			return
			[
				"message" => "Пароли не совпадают",
				"code" => -1,
			];
		}
		if (!password_verify($current_password, $current_password_hash))
		{
			return
			[
				"message" => "Неверный текущий пароль",
				"code" => -1,
			];
		}
		
		$password = password_hash($new_password1, PASSWORD_BCRYPT, ['cost'=>11]);
		
		/* Update user profile */
		$table_clients = $wpdb->base_prefix . 'elberos_clients';
		$result = $wpdb->update
		(
			$table_clients,
			[
				'password' => $password,
			],
			[
				'id' => $current_user['id']
			]
		);
		
		/* Apply action */
		do_action("elberos_user_change_password_after", [
			"user" => $current_user,
			"new_password" => $new_password1,
		]);
		
		return
		[
			"message" => "Данные успешно обновлены",
			"code" => 1,
		];
	}
	
	
	
	/**
	 * Change email
	 */
	public static function api_change_email($site)
	{
		global $wpdb;
		
		list($jwt, $current_user, $current_password_hash) = static::get_current_user();
		if ($current_user == null)
		{
			return
			[
				"success" => false,
				"message" => "Вы не авторизованы",
				"fields" => [],
				"code" => -1,
			];
		}
		if ($current_user['is_deleted'])
		{
			return
			[
				"message" => "Пользователь заблокирован",
				"code" => -2,
			];
		}
		
		$current_password = isset($_POST['current_password']) ? $_POST['current_password'] : "";
		if (!password_verify($current_password, $current_password_hash))
		{
			return
			[
				"message" => "Неверный текущий пароль",
				"code" => -1,
			];
		}
		
		$email = sanitize_user(trim(isset($_POST["new_email"]) ? $_POST["new_email"] : ""));
		if ($email == "" || !filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			return
			[
				"message" => "Неверный email",
				"code" => -1,
			];
		}
		
		$table_clients = $wpdb->base_prefix . 'elberos_clients';
		
		/* Check if email exists */
		$sql = $wpdb->prepare
		(
			"SELECT * FROM $table_clients WHERE email = %s and id != %d", $email, $current_user['id']
		);
		$row = $wpdb->get_row($sql, ARRAY_A);
		if ($row)
		{
			return
			[
				"message" => "Такой email уже зарегистрирован в системе",
				"code" => -1,
			];
		}
		
		/* Update user profile */
		$result = $wpdb->update
		(
			$table_clients,
			[
				'email' => $email,
			],
			[
				'id' => $current_user['id']
			]
		);
		
		/* Apply action */
		do_action("elberos_user_change_email_after", [
			"user" => $current_user,
			"new_email" => $email,
		]);
		
		return
		[
			"message" => "Данные успешно обновлены",
			"code" => 1,
		];
	}
	
	
	
	/**
	 * Create session
	 */
	public static function create_session($row)
	{
		/* Create JWT */
		$expire = time() + 30*24*60*60;
		$session = wp_generate_password(64, false, false);
		$jwt =
		[
			"u" => $row['id'],
			"l" => $row['email'],
			"s" => $session,
			"e" => $expire,
		];
		$jwt = static::create_jwt($jwt);
		
		/* Set cookie */
		$domain = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : "";
		if (is_multisite())
		{
			$arr = explode(".", $domain);
			$arr = array_slice($arr, count($arr) - 2, 2);
			$domain = implode(".", $arr);
			$domain = "." . $domain;
			setcookie('auth_token', $jwt, time() + 30*24*60*60, '/', $domain);
		}
		else
		{
			setcookie('auth_token', $jwt, time() + 30*24*60*60, '/');
		}
		
		return $jwt;
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
		$data = @json_decode($data_json, true);
		if ($data == null) return null;
		
		/* Validate sign */
		$text = $head_b64 . '.' . $data_b64;
		$hash = hash_hmac('SHA512', $text, SECURE_AUTH_KEY, true);
		$hash = \Elberos\base64_encode_url($hash);
		$verify = hash_equals($sign_b64, $hash);
		if (!$verify) return null;
		
		return $data;
	}
	
	
	/**
	 * Get current user
	 */
	public static function get_current_user()
	{
		global $wpdb;
		
		$password = null;
		$current_user = null;
		$jwt = isset($_COOKIE['auth_token']) ? $_COOKIE['auth_token'] : null;
		if ($jwt)
		{
			$jwt = static::decode_jwt($jwt);
			$jwt = $jwt;
			$user_id = $jwt['u'];
			
			$table_clients = $wpdb->base_prefix . 'elberos_clients';
			$sql = $wpdb->prepare
			(
				"SELECT * FROM $table_clients WHERE id = %d", $user_id
			);
			$row = $wpdb->get_row($sql, ARRAY_A);
			if ($row)
			{
				$password = $row['password'];
				unset($row['password']);
				$current_user = $row;
			}
			else
			{
				$jwt = null;
				$current_user = null;
			}
		}
		
		return [ $jwt, $current_user, $password ];
	}
	
}

}