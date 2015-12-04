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

use pocketmine\entity\AttributeManager;
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
	/** @var \pocketmine\item\Item[] */
	private $items = [];
	/** @var \pocketmine\item\Item[] */
	private $armorItems = [];

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
		$this->items = $player->getInventory()->getContents();
		$this->armorItems = $player->getInventory()->getArmorContents()
	}
	public function restore(){
		$player = $this->session->getPlayer();
		$player->setGamemode($this->gamemode);
		$player->setAllowFlight(false);
		$inventory = $player->getInventory();
		$inventory->clearAll();
		$inventory->setContents($this->items);
		$inventory->setArmorContents($this->armorItems);
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
		$player->getAttribute()->addAttribute(AttributeManager::MAX_HEALTH, "generic.health", 0, $this->maxHealth, $this->health, $this->health, true);
		$player->setNameTag($this->nameTag);
	}
}
