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
		add_filter('elberos_struct_builder', '\\Elberos\\UserCabinet\\User::elberos_struct_builder', 10);
	}
	
	
	
	/**
	 * Create user
	 */
	public static function create($action, $item)
	{
		return \Elberos\StructBuilder::create("elberos_user", $action, $item);
	}
	
	
	
	/**
	 * Php style for name field
	 */
	public static function php_style_name($struct, $field)
	{
		$type = $struct->getValue("type");
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
	public static function js_change_name($struct)
	{
		return
			'var value = $form.find("select[data-name=type]").val();' . "\n" .
			'if (value == 1) $(".web_form__row[data-name=name]").show();' . "\n" .
			'else $(".web_form__row[data-name=name]").hide();'
		;
	}
	
	
	
	/**
	 * Php style for surname field
	 */
	public static function php_style_surname($struct, $field)
	{
		$type = $struct->getValue("type");
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
	public static function js_change_surname($struct)
	{
		return
			'var value = $form.find("select[data-name=type]").val();' . "\n" .
			'if (value == 1) $(".web_form__row[data-name=surname]").show();' . "\n" .
			'else $(".web_form__row[data-name=surname]").hide();'
		;
	}
	
	
	
	/**
	 * Php style for company_name field
	 */
	public static function php_style_company_name($struct, $field)
	{
		$type = $struct->getValue("type");
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
	public static function js_change_company_name($struct)
	{
		return
			'var value = $form.find("select[data-name=type]").val();' . "\n" .
			'if (value == 2) $(".web_form__row[data-name=company_name]").show();' . "\n" .
			'else $(".web_form__row[data-name=company_name]").hide();'
		;
	}
	
	
	
	/**
	 * Process item search name
	 */
	public static function process_item_search_name($struct, $item)
	{
		if ($struct->item == null) return $item;
		if ($struct->item["type"] == 1) $item["search_name"] = $struct->item["name"] . " " . $struct->item["surname"];
		if ($struct->item["type"] == 2) $item["search_name"] = $struct->item["company_name"];
		$item["search_name"] = trim($item["search_name"]);
		return $item;
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
					"label" => "��� �������",
					"default" => 1,
					"options" =>
					[
						["id"=>1, "value"=>"��� ����"],
						["id"=>2, "value"=>"�� ����"],
					],
				])
				
				->addField
				([
					"api_name" => "name",
					"label" => "���",
					"type" => "input",
					"php_style" => [static::class, "php_style_name"],
					"js_change" => [static::class, "js_change_name"],
				])
				
				->addField
				([
					"api_name" => "surname",
					"label" => "�������",
					"type" => "input",
					"php_style" => [static::class, "php_style_surname"],
					"js_change" => [static::class, "js_change_surname"],
				])
				
				->addField
				([
					"api_name" => "company_name",
					"label" => "�������� ��������",
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
					"label" => "�������",
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
					"label" => "���������� ������",
					"type" => "password",
					"virtual" => true,
				])
				->addField
				([
					"api_name" => "password2",
					"label" => "��������� ������",
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