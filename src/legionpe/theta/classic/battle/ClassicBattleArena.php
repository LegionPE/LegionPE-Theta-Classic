<?php

/*
 * LegionPE
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
use pocketmine\level\Level;

class ClassicBattleArena{
	/** @var string */
	private $name;
	/** @var \pocketmine\level\Level */
	private $level;
	/** @var \pocketmine\math\Vector3[][] */
	private $spawnpoints = [];
	/** @var int[][] */
	private $rotation = [];

	/**
	 * @param string $name
	 * @param Level $level
	 * @param \pocketmine\math\Vector3[][] $spawnpoints
	 * @param int[][] $rotation
	 */
	public function __construct($name, Level $level, $spawnpoints, $rotation){
		$this->name = $name;
		$this->level = $level;
		$this->spawnpoints = $spawnpoints;
		$this->rotation = $rotation;
	}
	/**
	 * @return string
	 */
	public function getName(){
		return $this->name;
	}
	/**
	 * @param int $team
	 * @param int $index
	 * @param ClassicSession $session
	 */
	public function teleportToSpawnpoint($team, $index, ClassicSession $session){
		$session->getPlayer()->teleport($this->spawnpoints[$team][$index]);
		$session->getPlayer()->setRotation($this->rotation[$team][$index], 0);
	}
}
