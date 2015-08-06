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
	const DEFAULT_COOLDOWN_TIMEOUT = 0.55;
	const KILLSTREAK_TIMEOUT_BASE = 15.0;
	public static function isSpawn(Vector3 $v){
		return ($v->y > 64) and (108.5 <= $v->x) and ($v->x <= 112.5) and (-24.5 <= $v->z) and ($v->z <= -20.5);
	}
	public static function getSpawnPosition(Server $server){
		return new Location(110.5, 65.0, -22.5, 0, 90, $server->getLevelByName("world_pvp"));
	}
	public static function getKillHeal(Session $session){
		if($session->isVIPPlus()){
			return 38;
		}
		if($session->isVIP()){
			return 30;
		}
		if($session->isDonatorPlus()){
			return 20;
		}
		if($session->isDonator()){
			return 15;
		}
		return 10;
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
