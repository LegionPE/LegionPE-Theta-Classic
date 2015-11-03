<?php

/*
 * LegionPE
 *
 * Copyright (C) 2015 PEMapModder
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PEMapModder
 */

namespace legionpe\theta\classic;

use pocketmine\entity\Entity;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\scheduler\PluginTask;

class FireballTask extends PluginTask{
	const FIREBALL_NETWORK_ID = 85;

	const MAX_DISTANCE_SQUARED = 42 ** 2;
	const FIREBALL_SPEED = 3;

	/** @var int[] */
	private $rmQueue = [];

	public function __construct(ClassicPlugin $main){
		parent::__construct($main);
		if(IS_HALLOWEEN_MODE){
			$main->getServer()->getScheduler()->scheduleDelayedRepeatingTask($this, 10, 10);
		}
	}
	public function onRun($currentTick){
		$locs = ClassicConsts::getGhastLocations($this->owner->getServer());
		$unset = [];
		foreach($this->rmQueue as $eid => &$tick){
			$tick--;
			if($tick === 0){
				$unset[] = $eid;
				$pk = new RemoveEntityPacket;
				$pk->eid = $eid;
				foreach($locs[0]->getLevel()->getPlayers() as $player){
					$player->dataPacket($pk);
				}
			}
		}
		foreach($unset as $eid){
			unset($this->rmQueue[$eid]);
		}
		foreach($locs as $loc){
			if(mt_rand(0, 999) < 75){
				$level = $loc->getLevel();
				$minDistSq = self::MAX_DISTANCE_SQUARED;
				$closest = null;
				foreach($level->getPlayers() as $player){
					if($minDistSq > ($distSq = $player->distanceSquared($loc))){
						/** @var ClassicPlugin $main */
						$main = $this->owner;
						$ses = $main->getSession($player);
						if($ses instanceof ClassicSession){
							if($ses->isPlaying() and !$ses->isInvincible()){
								$minDistSq = $distSq;
								$closest = $player;
							}
						}
					}
				}
				if($closest !== null){
					$vector = $closest->subtract($loc)->normalize()->multiply(self::FIREBALL_SPEED);
					$pk = new AddEntityPacket;
					$pk->eid = $eid = Entity::$entityCount++;
					$pk->type = self::FIREBALL_NETWORK_ID;
					$pk->x = $loc->x;
					$pk->y = $loc->y;
					$pk->z = $loc->z;
					$pk->speedX = $vector->x;
					$pk->speedY = $vector->y;
					$pk->speedZ = $vector->z;
					$pk->yaw = atan2($vector->x, $vector->z) * 180 / M_PI;
					$pk->pitch = atan2($vector->y, $f = sqrt(($vector->x ** 2) + ($vector->z ** 2))) * 180 / M_PI;
					$pk->metadata = [
						Entity::DATA_FLAGS => [Entity::DATA_TYPE_BYTE, 0],
						Entity::DATA_AIR => [Entity::DATA_TYPE_SHORT, 300],
						Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, ""],
						Entity::DATA_SHOW_NAMETAG => [Entity::DATA_TYPE_BYTE, 0],
						Entity::DATA_SILENT => [Entity::DATA_TYPE_BYTE, 0],
						Entity::DATA_NO_AI => [Entity::DATA_TYPE_BYTE, 0],
					];
					$this->rmQueue[$eid] = 10;
					foreach($level->getChunkPlayers($loc->x >> 4, $loc->z >> 4) as $p){
						$p->dataPacket($pk);
					}
				}
			}
		}
	}
}
