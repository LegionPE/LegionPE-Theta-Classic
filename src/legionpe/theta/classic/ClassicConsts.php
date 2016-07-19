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
	const COMBAT_MODE_COOLDOWN = 25;
	public static $killTags = [
		0=>"Newbie",
		25=>"Fighter",
		75=>"Knight",
		150=>"Dangerous",
		200=>"Ninja",
		300=>"Beast",
		450=>"Elite",
		600=>"Warrior",
		800=>"Thief",
		1000=>"Killer",
		1200=>"Addict",
		1400=>"Unstoppable",
		1600=>"Pro",
		1750=>"Hardcore",
		2000=>"Master",
		2300=>"Legend",
		3000=>"God"
	];
	public static function isSpawn(Vector3 $v){
		return self::isSpawnArea($v);
	}
	public static function spawnPortal(Player $player){
		return ($player->y < 18) and self::isSpawnArea($player);
	}
	private static function isSpawnArea(Vector3 $v){
		return (($v->x >= -22) and ($v->x <= 17) and ($v->z <= 21) and ($v->z >= -18) and ($v->y >= 11)) or (($v->y >= 6) and ($v->x <= 11) and ($v->x >= -16) and ($v->z >= -12) and ($v->z <= 15));
	}
	public static function getSpawnPosition(Server $server){
		return new Position(-2, 12, 1, $server->getLevelByName("world_pvp"));
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
	/**
	 * @param $kills
	 * @return string
	 */
	public static function getKillsTag($kills){
		$tag = "";
		foreach(self::$killTags as $killCount=>$killTag){
			if($kills >= $killCount){
				$tag = $killTag;
			}
		}
		return $tag;
	}
	public static function getNextKillsTag($kills){
		$nextTag = "";
		foreach(self::$killTags as $killCount=>$killTag){
			if($kills < $killCount){
				$nextTag = $killTag;
				break;
			}
		}
		return $nextTag;
	}
	/**
	 * @param Server $server
	 * @return Location[]
	 */
	public static function getGhastLocations(Server $server){
		$level = $server->getLevelByName("world_pvp");
		return [
			new Location(304, 54, -135, 180, 29, $level),
			new Location(197, 83, 2, 120, 16, $level),
			new Location(145, 78, -112, 222, 27, $level),
			new Location(213, 72, -104, 224, 27, $level),
			new Location(136, 63, -70, 0, 37, $level),
		];
	}
}
