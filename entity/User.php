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


if ( !class_exists( User::class ) && class_exists( \Elberos\StructBuilder::class ) ) 
{

class User extends \Elberos\StructBuilder
{
	
	/**
	 * Get entity name
	 */
	public static function getEntityName()
	{
		return "elberos_user";
	}
	
	
	/**
	 * Init struct
	 */
	public function init()
	{
		$this
			
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
				"api_name" => "id",
				"label" => "ID клиента",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "name",
				"label" => "Имя",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "surname",
				"label" => "Фамилия",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "lastname",
				"label" => "Отчество",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "company_name",
				"label" => "Название компании",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "search_name",
				"label" => "Имя",
				"form_show" => false,
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
			
			->addField
			([
				"api_name" => "gmtime_add",
				"label" => "Дата регистрации",
				"type" => "input",
				"column_value" => function($struct, $item)
				{
					return \Elberos\wp_from_gmtime( $item["gmtime_add"] );
				},
			])
		;
		
		/* Register user */
		if ($this->action == "register")
		{
			$this
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
				->addField
				([
					"api_name" => "captcha",
					"label" => "Введите код, указанный на картинке",
					"type" => "captcha",
					"virtual" => true,
				])
				->setFormFields([
					"type",
					"name",
					"surname",
					"company_name",
					"search_name",
					"email",
					"phone",
					"password1",
					"password2",
					"captcha",
				])
			;
		}
		
		/* Profile user */
		if ($this->action == "profile")
		{
			$this
				->setFormFields([
					"type",
					"name",
					"surname",
					"company_name",
					"search_name",
					"phone",
				])
			;
		}
		
		return $this;
	}
	
	
	
	/**
	 * Process item field
	 */
	public function processItemField($field, $item, $res)
	{
		if ($field["api_name"] == "search_name")
		{
			if ($item == null) return $res;
			if ($item["type"] == 1) $res["search_name"] = $item["name"] . " " . $item["surname"];
			if ($item["type"] == 2) $res["search_name"] = $item["company_name"];
			$res["search_name"] = trim($res["search_name"]);
			return $res;
		}
		return $res;
	}
	
	
	
	/**
	 * PHP Style
	 */
	public function phpFormStyleField($field, $item)
	{
		$type = $this->getValue($item, "type");
		if (in_array($field["api_name"], ["name", "surname"]))
		{
			return
			[
				"row" =>
				[
					"display" => ($type == 1) ? "block" : "none",
				],
			];
		}
		if ($field["api_name"] == "company_name")
		{
			return
			[
				"row" =>
				[
					"display" => ($type == 2) ? "block" : "none",
				],
			];
		}
		return [];
	}
	
	
	
	/**
	 * JS script
	 */
	public function jsFormChange($field, $item)
	{
		if ($field["api_name"] == "name")
		{
			return
				'var value = $form.find("select[data-name=type]").val();' . "\n" .
				'if (value == 1) jQuery(".web_form_row[data-name=name]").show();' . "\n" .
				'else jQuery(".web_form_row[data-name=name]").hide();'
			;
		}
		
		if ($field["api_name"] == "surname")
		{
			return
				'var value = $form.find("select[data-name=type]").val();' . "\n" .
				'if (value == 1) jQuery(".web_form_row[data-name=surname]").show();' . "\n" .
				'else jQuery(".web_form_row[data-name=surname]").hide();'
			;
		}
		
		if ($field["api_name"] == "company_name")
		{
			return
				'var value = $form.find("select[data-name=type]").val();' . "\n" .
				'if (value == 2) jQuery(".web_form_row[data-name=company_name]").show();' . "\n" .
				'else jQuery(".web_form_row[data-name=company_name]").hide();'
			;
		}
		
		return "";
	}
	
	
}

}