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

use pocketmine\entity\Entity;
use pocketmine\network\protocol\MobArmorEquipmentPacket;
use pocketmine\network\protocol\MobEquipmentPacket;
use pocketmine\network\protocol\SetEntityDataPacket;
use pocketmine\utils\TextFormat;
use legionpe\theta\classic\ClassicSession;
use legionpe\theta\classic\kit\ClassicKit;
use pocketmine\level\Position;


class CurrentKitStand extends KitStand{
	/**
	 * @param ClassicSession $session
	 * @param ClassicKit $kit
	 * @param Position $npcPos
	 * @param int $yaw
	 * @param Position $next
	 * @param Position $back
	 */
	public function __construct(ClassicSession $session, ClassicKit $kit, Position $npcPos, $yaw, Position $next, Position $back){
		parent::__construct($session, $kit, $npcPos, $yaw, $next, $back);
		$pk = new SetEntityDataPacket();
		$pk->eid = $this->npcEid;
		$pk->metadata = [Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, TextFormat::AQUA . TextFormat::GREEN . $this->kit->getName() . TextFormat::WHITE . " - " . TextFormat::AQUA . "level " . TextFormat::GREEN . $this->kit->level . "\n" . TextFormat::GREEN . "Description: " . TextFormat::AQUA . $this->kit->getDescription()]];
		$session->getPlayer()->dataPacket($pk);
	}
	public function next(){
		if($this->kit->level < $this->session->getKitLevel($this->kit)){
			$this->kit->setLevel(++$this->kit->level);
			$this->update();
		}
	}
	public function back(){
		if($this->kit->level !== 1){
			$this->kit->setLevel(--$this->kit->level);
			$this->update();
		}
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
		$pk->metadata = [Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, TextFormat::AQUA . TextFormat::GREEN . $this->kit->getName() . TextFormat::WHITE . " - " . TextFormat::AQUA . "level " . TextFormat::GREEN . $this->kit->level . "\n" . TextFormat::GREEN . "Description: " . TextFormat::AQUA . $this->kit->getDescription()]];
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
