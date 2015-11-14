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
		$inventory = $player->getInventory();
		$inventory->setContents($this->inventory->getContents());
		$inventory->setArmorContents($this->inventory->getArmorContents());
		$inventory->sendContents($player);
		$inventory->sendArmorContents($player);
		$player->removeAllEffects();
		$player->setRotation($this->yaw, $this->pitch);
		$player->teleport($this->position);
		$player->setMaxHealth($this->maxHealth);
		$player->setHealth($this->health);
		$player->setNameTag($this->nameTag);
	}
}
