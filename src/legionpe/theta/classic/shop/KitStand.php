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

namespace legionpe\theta\classic\shop;

use pocketmine\network\protocol\MobArmorEquipmentPacket;
use pocketmine\network\protocol\MobEquipmentPacket;
use pocketmine\network\protocol\SetEntityDataPacket;
use pocketmine\utils\TextFormat;
use pocketmine\utils\UUID;
use pocketmine\entity\Entity;
use pocketmine\level\Position;
use pocketmine\level\particle\FloatingTextParticle;
use legionpe\theta\classic\kit\ClassicKit;
use legionpe\theta\Session;
use pocketmine\network\protocol\AddPlayerPacket;

class KitStand{
	/** @var Session */
	private $session;
	/** @var ClassicKit */
	private $kit;
	/** @var int */
	private $npcEid;
	/** @var Position */
	private $npcPos;
	/** @var int */
	private $yaw;
	/** @var Position */
	private $nextPos = null;
	/** @var Position */
	private $backPos = null;
	/** @var FloatingTextParticle */
	private $floatingPowers = null;
	/**
	 * @param Session $session
	 * @param ClassicKit $kit
	 * @param Position $npcPos
	 * @param int $yaw
	 * @param Position $next
	 * @param Position $back
	 */
	public function __construct(Session $session, ClassicKit $kit, Position $npcPos, $yaw, Position $next, Position $back){
		$this->session = $session;
		$this->kit = $kit;
		$this->level = $session->getPlayer()->getLevel();
		$this->npcPos = $npcPos;
		$this->nextPos = $next;
		$this->backPos = $back;

		$player = $session->getPlayer();
		$pk = new AddPlayerPacket();
		$this->npcEid = Entity::$entityCount++;
		$pk->uuid = UUID::fromData($this->npcEid, "", "");
		$pk->username = TextFormat::GREEN . $this->kit->getName() . TextFormat::WHITE . " - " . TextFormat::AQUA . "level " . TextFormat::GREEN . $this->kit->level . "\n" . TextFormat::GREEN . "Description: " . TextFormat::AQUA . $this->kit->getDescription();
		$pk->eid = $this->npcEid;
		$pk->x = $npcPos->getX();
		$pk->y = $npcPos->getY();
		$pk->z = $npcPos->getZ();
		$pk->speedX = 0;
		$pk->speedY = 0;
		$pk->speedZ = 0;
		$pk->yaw = $yaw;
		$pk->pitch = 0;
		$pk->item = $kit->getItems()[0];
		$pk->metadata = [];
		$player->dataPacket($pk);
		$powers = "";
		foreach($this->kit->getPowers() as $power){
			$powers .= TextFormat::RED . "- " . TextFormat::GREEN . $power->getName() . ": " .  TextFormat::AQUA . $power->getDescription() . "\n  " . TextFormat::GREEN . "Duration: " . TextFormat::AQUA . $power->getDuration() . "\n  " . TextFormat::GREEN . "Delay: " . TextFormat::AQUA . ($power->isPermanent ? "None, permanent power (always active)" :  $power->getDelay()) . "\n";

		}
		$this->floatingPowers = new FloatingTextParticle($this->getFloatingTextPositionOppositeNpc(2, 0, 0, 0 , 1.5), $powers, TextFormat::GREEN . "Powers: ");
		$player->getLevel()->addParticle($this->floatingPowers, [$player]);
	}

	private function getFloatingTextPositionOppositeNpc($left, $right, $front, $back, $y){
		$npcX = $this->npcPos->getX();
		$npcZ = $this->npcPos->getZ();
		$y += $this->npcPos->getZ();
		$position = null;
		if($this->yaw < 90){
			$position = new Position($npcX + $left - $right, $y, $npcZ + $front - $back, $this->npcPos->getLevel());
		}elseif($this->yaw < 180){
			$position = new Position($npcX - $front + $back, $y, $npcZ - $right + $left, $this->npcPos->getLevel());
		}elseif($this->yaw < 180){
			$position = new Position($npcX - $left + $right, $y, $npcZ - $front + $back, $this->npcPos->getLevel());
		}elseif($this->yaw < 360){
			$position = new Position($npcX + $front - $back, $y, $npcZ + $right - $left, $this->npcPos->getLevel());
		}
		return $position;
	}
	private function update(){
		$player = $this->session->getPlayer();
		$pk = new MobArmorEquipmentPacket();
		$pk->eid = $this->npcEid;
		$pk->slots = $this->kit->getArmorItems();
		$player->dataPacket($pk);
		$pk = new MobEquipmentPacket();
		$pk->eid = $this->npcEid;
		$pk->item = $this->kit->getItems()[0];
		$pk->slot = 0;
		$pk->selectedSlot = 0;
		$player->dataPacket($pk);
		$pk = new SetEntityDataPacket();
		$pk->eid = $this->npcEid;
		$pk->metadata = [Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, TextFormat::GREEN . $this->kit->getName() . TextFormat::WHITE . " - " . TextFormat::AQUA . "level " . TextFormat::GREEN . $this->kit->level . "\n" . TextFormat::GREEN . "Description: " . TextFormat::AQUA . $this->kit->getDescription()]];
		$player->dataPacket($pk);
		$powers = "";
		foreach($this->kit->getPowers() as $power){
			$powers .= TextFormat::RED . "- " . TextFormat::GREEN . $power->getName() . ": " .  TextFormat::AQUA . $power->getDescription() . "\n  " . TextFormat::GREEN . "Duration: " . TextFormat::AQUA . $power->getDuration() . "\n  " . TextFormat::GREEN . "Delay: " . TextFormat::AQUA . ($power->isPermanent ? "None, permanent power (always active)" :  $power->getDelay()) . "\n";

		}
		$this->floatingPowers->setText($powers);
		$encode = $this->floatingPowers->encode();
		if(is_array($encode)){
			foreach($encode as $packet){
				$player->dataPacket($packet);
			}
		}else{
			$player->dataPacket($encode);
		}
	}
}