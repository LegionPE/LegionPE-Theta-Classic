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

namespace legionpe\theta\classic\battle;

use pocketmine\network\protocol\UpdateAttributesPacket;
use legionpe\theta\classic\ClassicSession;

class ClassicBattleOld{
	/** @var \legionpe\theta\classic\ClassicSession */
	private $session;
	/** @var string */
	private $nameTag;
	/** @var int */
	private $yaw, $pitch;
	/** @var \pocketmine\level\Position */
	private $position;
	/** @var int */
	private $health, $maxHealth;
	/** @var int */
	private $gamemode;
	/** @var \pocketmine\inventory\PlayerInventory */
	private $inventory;

	/**
	 * @param ClassicSession $session
	 */
	public function __construct(ClassicSession $session){
		$player = $session->getPlayer();
		$this->session = $session;
		$this->nameTag = $session->getPlayer()->getNameTag();
		$this->yaw = $player->getYaw();
		$this->pitch = $player->getPitch();
		$this->position = $player->getPosition();
		$this->health = $player->getHealth();
		$this->maxHealth = $player->getMaxHealth();
		$this->gamemode = $session->getPlayer()->getGamemode();
		$this->inventory = $player->getInventory();
	}
	public function restore(){
		$player = $this->session->getPlayer();
		$player->setGamemode($this->gamemode);
		$player->setAllowFlight(false);
		$inventory = $player->getInventory();
		$inventory->clearAll();
		$inventory->setContents($this->inventory->getContents());
		$inventory->setArmorContents($this->inventory->getArmorContents());
		for($i = 0; $i < 7; $i++){
			$inventory->setHotbarSlotIndex($i, $i);
		}
		$inventory->sendContents($player);
		$inventory->sendArmorContents($player);
		$player->removeAllEffects();
		$player->setRotation($this->yaw, $this->pitch);
		$player->teleport($this->position);
		$player->setMaxHealth($this->maxHealth);
		$player->setHealth($this->health);
		// steadfast 2
		$pk = new UpdateAttributesPacket();
		$pk->minValue = 0;
		$pk->maxValue = $this->maxHealth;
		$pk->value = $this->maxHealth;
		$pk->name = UpdateAttributesPacket::HEALTH;
		$session->getPlayer()->dataPacket($pk);
		$player->setNameTag($this->nameTag);
	}
}
