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

if ( !class_exists( User::class ) ) 
{

class User
{
	
	/**
	 * Init
	 */
	public static function init()
	{
		add_filter('elberos_struct_builder', '\\Elberos\\UserCabinet\\User::elberos_struct_builder', -1);
	}
	
	
	
	/**
	 * Create user
	 */
	public static function create($action, $init = null)
	{
		return \Elberos\StructBuilder::create("elberos_user", $action, $init);
	}
	
	
	
	/**
	 * Php style for name field
	 */
	public static function php_style_name($struct, $field, $item)
	{
		$type = $struct->getValue($item, "type");
		return
		[
			"row" =>
			[
				"display" => ($type == 1) ? "block" : "none",
			],
		];
	}
	
	
	
	/**
	 * JS change for name field
	 */
	public static function js_change_name($struct, $item)
	{
		return
			'var value = $form.find("select[data-name=type]").val();' . "\n" .
			'if (value == 1) jQuery(".web_form__row[data-name=name]").show();' . "\n" .
			'else jQuery(".web_form__row[data-name=name]").hide();'
		;
	}
	
	
	
	/**
	 * Php style for surname field
	 */
	public static function php_style_surname($struct, $field, $item)
	{
		$type = $struct->getValue($item, "type");
		return
		[
			"row" =>
			[
				"display" => ($type == 1) ? "block" : "none",
			],
		];
	}
	
	
	
	/**
	 * JS change for surname field
	 */
	public static function js_change_surname($struct, $item)
	{
		return
			'var value = $form.find("select[data-name=type]").val();' . "\n" .
			'if (value == 1) jQuery(".web_form__row[data-name=surname]").show();' . "\n" .
			'else jQuery(".web_form__row[data-name=surname]").hide();'
		;
	}
	
	
	
	/**
	 * Php style for company_name field
	 */
	public static function php_style_company_name($struct, $field, $item)
	{
		$type = $struct->getValue($item, "type");
		return
		[
			"row" =>
			[
				"display" => ($type == 2) ? "block" : "none",
			],
		];
	}
	
	
	
	/**
	 * JS change for company_name field
	 */
	public static function js_change_company_name($struct, $item)
	{
		return
			'var value = $form.find("select[data-name=type]").val();' . "\n" .
			'if (value == 2) jQuery(".web_form__row[data-name=company_name]").show();' . "\n" .
			'else jQuery(".web_form__row[data-name=company_name]").hide();'
		;
	}
	
	
	
	/**
	 * Process item search name
	 */
	public static function process_item_search_name($struct, $item, $res)
	{
		if ($item == null) return $res;
		if ($item["type"] == 1) $res["search_name"] = $item["name"] . " " . $item["surname"];
		if ($item["type"] == 2) $res["search_name"] = $item["company_name"];
		$res["search_name"] = trim($res["search_name"]);
		return $res;
	}
	
	
	
	/**
	 * Struct builder
	 */
	public static function elberos_struct_builder($struct)
	{
		if ($struct->form_name == "elberos_user")
		{
			$struct
				
				->addField
				([
					"api_name" => "type",
					"type" => "select",
					"label" => "Тип клиента",
					"default" => 1,
					"options" =>
					[
						["id"=>1, "value"=>"Физ лицо"],
						["id"=>2, "value"=>"Юр лицо"],
					],
				])
				
				->addField
				([
					"api_name" => "name",
					"label" => "Имя",
					"type" => "input",
					"php_style" => [static::class, "php_style_name"],
					"js_change" => [static::class, "js_change_name"],
				])
				
				->addField
				([
					"api_name" => "surname",
					"label" => "Фамилия",
					"type" => "input",
					"php_style" => [static::class, "php_style_surname"],
					"js_change" => [static::class, "js_change_surname"],
				])
				
				->addField
				([
					"api_name" => "company_name",
					"label" => "Название компании",
					"type" => "input",
					"php_style" => [static::class, "php_style_company_name"],
					"js_change" => [static::class, "js_change_company_name"],
				])
				
				->addField
				([
					"api_name" => "search_name",
					"show" => false,
					"process_item" => [static::class, "process_item_search_name"],
				])
				
				->addField
				([
					"api_name" => "email",
					"label" => "E-mail",
					"type" => "input",
				])
				
				->addField
				([
					"api_name" => "phone",
					"label" => "Телефон",
					"type" => "input",
				])
			;
		}
		
		/* Register user */
		if ($struct->form_name == "elberos_user" and $struct->action == "register")
		{
			$struct
				->addField
				([
					"api_name" => "password1",
					"label" => "Придумайте пароль",
					"type" => "password",
					"virtual" => true,
				])
				->addField
				([
					"api_name" => "password2",
					"label" => "Повторите пароль",
					"type" => "password",
					"virtual" => true,
				])
			;
		}
		
		/* Profile user */
		if ($struct->form_name == "elberos_user" and $struct->action == "profile")
		{
			$struct->removeShowField("email");
		}
		
		return $struct;
	}
}

}