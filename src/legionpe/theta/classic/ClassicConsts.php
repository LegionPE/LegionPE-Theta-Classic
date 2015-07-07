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

use pocketmine\level\Location;
use pocketmine\math\Vector3;
use pocketmine\Server;

class ClassicConsts{
	const COOLDOWN_TIMEOUT = 0.7;
	public static function isSpawn(Vector3 $v){
		return
			(110 <= $v->x) and
			($v->x <= 149) and
			(-21 <= $v->z) and
			(11 <= $v->z) and
			(32 <= $v->y) and
			($v->y <= 58);
	}
	public static function getSpawnPosition(Server $server){
		return new Location(123.5, 65, -2.5, 90.0, 0.0, $server->getLevelByName("world_pvp"));
	}
}
