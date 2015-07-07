<?php

/**
 * Theta
 * Copyright (C) 2015 PEMapModder
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace legionpe\theta\classic;

use legionpe\theta\BasePlugin;
use legionpe\theta\classic\query\ClassicLoginDataQuery;
use legionpe\theta\classic\query\ClassicSaveSinglePlayerQuery;
use pocketmine\Player;

class ClassicPlugin extends BasePlugin{
	const COINS_ON_KILL = 10;
	protected static function defaultLoginData($uid, Player $player){
		$data = parent::defaultLoginData($uid, $player);
		$data["pvp_init"] = 0;
		$data["pvp_kills"] = 0;
		$data["pvp_deaths"] = 0;
		$data["pvp_maxstreak"] = 0;
		$data["pvp_curstreak"] = 0;
		$data["pvp_kit"] = 0;
	}
	public function getLoginQueryImpl(){
		return ClassicLoginDataQuery::class;
	}
	public function getSaveSingleQueryImpl(){
		return ClassicSaveSinglePlayerQuery::class;
	}
	public function sendFirstJoinMessages(Player $player){
		// TODO: Implement sendFirstJoinMessages() method.
	}
	public function query_world(){
		return "pvp-1";
	}
	protected function createSession(Player $player, array $loginData){
		return new ClassicSession($this, $player, $loginData);
	}
}
