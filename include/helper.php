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
	public static function fields($action, $item)
	{
		return \Elberos\StructBuilder::create("elberos_user", $action, $item);
	}
}

}