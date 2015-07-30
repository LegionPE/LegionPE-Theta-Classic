<?php

/*
 * LegionPE Theta
 *
 * Copyright (C) 2015 PEMapModder and contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PEMapModder
 */

namespace legionpe\theta\classic;

use legionpe\theta\Session;
use pocketmine\level\Location;
use pocketmine\math\Vector3;
use pocketmine\Server;

class ClassicConsts{
	const DEFAULT_COOLDOWN_TIMEOUT = 0.7;
	public static function isSpawn(Vector3 $v){
		$result = (((110 <= $v->x) and ($v->x <= 149) and (-21 <= $v->z) and ($v->z <= 11)) or
			((32 <= $v->y) and ($v->y <= 58) and (126 <= $v->x) and ($v->x <= 153) and (-16 <= $v->z) and ($v->z <= 11)));
		return $result;
	}
	public static function getSpawnPosition(Server $server){
		return new Location(123.5, 65, -2.5, 90.0, 0.0, $server->getLevelByName("world_pvp"));
	}
	public static function getKillHeal(Session $session){
		if($session->isVIPPlus()){
			return 15;
		}
		if($session->isVIP()){
			return 10;
		}
		if($session->isDonatorPlus()){
			return 6;
		}
		if($session->isDonator()){
			return 4;
		}
		return 3;
	}
	/**
	 * @param Session $session
	 * @return int number of half seconds
	 */
	public static function getAutoHealFrequency(Session $session){
		if($session->isModerator(false)){
			return 5;
		}
		if($session->isVIP()){
			return 10;
		}
		if($session->isDonator()){
			return 12;
		}
		return 15;
	}
	public static function getAutoHeal(Session $session){
		return $session->isVIP() ? 2 : 1;
	}
}
