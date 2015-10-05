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
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;

class ClassicConsts{
	const DEFAULT_COOLDOWN_TIMEOUT = 0.55;
	const KILLSTREAK_TIMEOUT_BASE = 90.0;
	const COINS_ON_KILL = 10;
	const CONS_PER_KS = 10;
	const RESPAWN_INVINCIBILITY = 15;
	const TELEPORT_DELAY_TICKS = 100;
	const AUTO_HEAL_AMPLIFIER = 2;
	const KILL_HEAL_AMPLIFIER = 3;
	public static function isSpawn(Vector3 $v){
		return ($v->y >= 18) and self::isSpawnArea($v);
	}
	public static function spawnPortal(Player $player){
		return ($player->y < 18) and self::isSpawnArea($player);
	}
	private static function isSpawnArea(Vector3 $v){
		return (270 <= $v->x) and ($v->x <= 344) and (-179 <= $v->z) and ($v->z <= -115);
	}
	public static function getSpawnPosition(Server $server){
		return new Position(304, 48, -153, $server->getLevelByName("world_pvp"));
	}
	public static function getRandomSpawnPosition(Server $server){
		$spawns = [
			new Vector3(227, 62, -123),
			new Vector3(195, 61, 0),
			new Vector3(111, 49, -63),
		];
		return Position::fromObject($spawns[mt_rand(0, 2)], $server->getLevelByName("world_pvp"));
	}
	public static function getKillHeal(Session $session){
		return self::getKillHeal0($session) * self::KILL_HEAL_AMPLIFIER;
	}
	private static function getKillHeal0(Session $session){
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
		return ($session->isVIP() ? 2 : 1) * self::AUTO_HEAL_AMPLIFIER;
	}
	public static function get1v1HostPos(Server $server){
		return new Position(196, 16, 4, $server->getLevelByName("world_pvp"));
	}
	public static function get1v1GuestPos(Server $server){
		return new Position(214, 16, 23, $server->getLevelByName("world_pvp"));
	}
}
