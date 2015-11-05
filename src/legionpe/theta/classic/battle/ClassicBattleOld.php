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
	/** @var int */
	private $yaw, $pitch;
	/** @var \pocketmine\level\Position */
	private $position;
	/** @var int */
	private $health, $maxHealth;

	/**
	 * @param ClassicSession $session
	 */
	public function __construct(ClassicSession $session){
		$player = $session->getPlayer();
		$this->session = $session;
		$this->yaw = $player->getYaw();
		$this->pitch = $player->getPitch();
		$this->position = $player->getPosition();
		$this->health = $player->getHealth();
		$this->maxHealth = $player->getMaxHealth();
	}
	public function restore(){
		$player = $this->session->getPlayer();
		$player->removeAllEffects();
		$player->setRotation($this->yaw, $this->pitch);
		$player->teleport($this->position);
		$player->setMaxHealth($this->maxHealth);
		$player->setHealth($this->health);
		$this->session->equip();
	}
}
