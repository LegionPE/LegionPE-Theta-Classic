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

use legionpe\theta\classic\ClassicSession;
use pocketmine\network\protocol\MobArmorEquipmentPacket;
use pocketmine\network\protocol\MobEquipmentPacket;
use pocketmine\network\protocol\SetEntityDataPacket;
use pocketmine\utils\MainLogger;
use pocketmine\utils\TextFormat;
use pocketmine\utils\UUID;
use pocketmine\entity\Entity;
use pocketmine\level\Position;
use pocketmine\level\particle\FloatingTextParticle;
use legionpe\theta\classic\kit\ClassicKit;
use pocketmine\network\protocol\AddPlayerPacket;

class KitStand{
	/** @var ClassicSession */
	protected $session;
	/** @var ClassicKit */
	protected $kit;
	/** @var int */
	protected $npcEid;
	/** @var Position */
	protected $npcPos;
	/** @var int */
	protected $yaw;
	/** @var Position[] */
	protected $nextPos = [];
	/** @var Position[] */
	protected $backPos = [];
	/** @var FloatingTextParticle */
	protected $floatingPowers = null;
	/**
	 * @param ClassicSession $session
	 * @param ClassicKit $kit
	 * @param Position $npcPos
	 * @param int $yaw
	 * @param Position[] $next
	 * @param Position[] $back
	 * @param Position[] $floatingPos
	 */
	public function __construct(ClassicSession $session, ClassicKit $kit, Position $npcPos, $yaw, $back, $next, Position $floatingPos){
		$this->session = $session;
		$this->kit = $kit;
		$this->yaw = $yaw;
		$this->level = $session->getPlayer()->getLevel();
		$this->npcPos = $npcPos;
		$this->nextPos = $next;
		$this->backPos = $back;

		$player = $session->getPlayer();
		$pk = new AddPlayerPacket();
		$this->npcEid = Entity::$entityCount++;
		$pk->uuid = UUID::fromData($this->npcEid, "", "");
		$pk->username = TextFormat::AQUA . ($session->getKitLevel($kit) >= $kit->level ? "Unlocked" : $kit->getPrice() . " coins") . "\n" . TextFormat::GREEN . $this->kit->getName() . TextFormat::WHITE . " - " . TextFormat::AQUA . "level " . TextFormat::GREEN . $this->kit->level;
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
		$pk = new MobArmorEquipmentPacket();
		$pk->eid = $this->npcEid;
		$pk->slots = $this->kit->getArmorItems();
		$player->dataPacket($pk);
		$powers = "";
		foreach($this->kit->getPowers() as $power){
			$powers .= TextFormat::RED . "- " . TextFormat::GREEN . $power->getName() . ": " .  TextFormat::AQUA . chunk_split($power->getDescription(), 24, "\n   " . TextFormat::AQUA) . TextFormat::GREEN . "Duration: " . TextFormat::AQUA . $power->getDuration() . "\n   " . TextFormat::GREEN . "Delay: " . TextFormat::AQUA . ($power->isPermanent ? "None, permanent power (always active)" :  $power->getDelay()) . "\n";

		}
		$this->floatingPowers = new FloatingTextParticle($floatingPos, $powers, TextFormat::GREEN . "Powers: ");
		$player->getLevel()->addParticle($this->floatingPowers, [$player]);
	}
	/**
	 * @return ClassicKit
	 */
	public function getKit(){
		return $this->kit;
	}
	/**
	 * @return int
	 */
	public function getEid(){
		return $this->npcEid;
	}
	public function next(){
		if($this->kit->level <= $this->kit->maxLevel){
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
	private function getFloatingTextPositionOppositeNpc($left, $right, $front, $back, $y){
		$npcX = $this->npcPos->getX();
		$npcZ = $this->npcPos->getZ();
		$y += $this->npcPos->getZ();
		$position = null;
		if($this->yaw <= 90){
			$position = new Position($npcX + $left - $right, $y, $npcZ + $front - $back, $this->npcPos->getLevel());
		}elseif($this->yaw <= 180){
			$position = new Position($npcX - $front + $back, $y, $npcZ - $right + $left, $this->npcPos->getLevel());
		}elseif($this->yaw <= 270){
			$position = new Position($npcX - $left + $right, $y, $npcZ - $front + $back, $this->npcPos->getLevel());
		}elseif($this->yaw <= -90){
			$position = new Position($npcX + $front - $back, $y, $npcZ + $right - $left, $this->npcPos->getLevel());
		}
		return $position;
	}
	public function update(){
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
		$pk->metadata = [Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, TextFormat::AQUA . ($this->session->getKitLevel($this->kit) >= $this->kit->level ? "Unlocked" : $this->kit->getPrice() . " coins") . "\n" . TextFormat::GREEN . $this->kit->getName() . TextFormat::WHITE . " - " . TextFormat::AQUA . "level " . TextFormat::GREEN . $this->kit->level]];
		$player->dataPacket($pk);
		$powers = "";
		foreach($this->kit->getPowers() as $power){
			$powers .= TextFormat::RED . "- " . TextFormat::GREEN . $power->getName() . ": " .  TextFormat::AQUA . chunk_split($power->getDescription(), 24, "\n   " . TextFormat::AQUA) . TextFormat::GREEN . "Duration: " . TextFormat::AQUA . $power->getDuration() . "\n   " . TextFormat::GREEN . "Delay: " . TextFormat::AQUA . ($power->isPermanent ? "None, always active" : $power->getDelay()) . "\n";

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
	public function isNextPosition(Position $position){
		foreach($this->nextPos as $pos){
			if($pos->x === $position->x and $pos->y === $position->y and $pos->z === $position->z and $pos->level->getId() === $pos->level->getId()){
				var_dump("hit");
				return true;
				break;
			}
		}
		return false;
	}
	public function isBackPosition(Position $position){
		foreach($this->backPos as $pos){
			if($pos->equals($position)){
				var_dump("hit");
				return true;
				break;
			}
		}
		return false;
	}
}
