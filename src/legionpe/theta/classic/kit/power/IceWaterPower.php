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

namespace legionpe\theta\classic\kit\power;

use legionpe\theta\classic\ClassicSession;
use legionpe\theta\classic\utils\ResetBlocksTask;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\math\Vector3;

class IceWaterPower extends ClassicKitPower{
	/** @var \pocketmine\item\Item */
	public $item;
	/** @var \legionpe\theta\classic\utils\ResetBlocksTask */
	private $task;
	public function __construct($name, $description, $level, Item $item, ResetBlocksTask $task){
		$this->setName($name);
		$this->setDescription($description);
		$this->setLevel($level);
		$this->item = $item;
		$this->task = $task;
		switch($level){
			case 1:
				$this->delay = 120;
				$this->duration = 10;
				break;
			case 2:
				$this->delay = 90;
				$this->duration = 10;
				break;
			case 3:
				$this->delay = 90;
				$this->duration = 20;
				break;
			case 4:
				$this->delay = 90;
				$this->duration = 20;
				break;
		}
	}
	public function onGeneral(ClassicSession $session){

	}
	public function onDamageByEntity(ClassicSession $damager, ClassicSession $damaged, &$damage){

	}
	public function onDamage(ClassicSession $session, &$damage, $event){

	}
	public function onAttack(ClassicSession $attacker, ClassicSession $victim, &$damage){

	}
	public function onHeal(ClassicSession $owner, &$health){

	}
	public function onMove(ClassicSession $session){
		if($this->isActive()){
			$player = $session->getPlayer();
			$level = $player->getLevel();
			$x = $player->getFloorX();
			$y = $player->getFloorY() - 1;
			$z = $player->getFloorZ();
			for($lX = ($x - 1); $lX < ($x + 1); $lX++){
				for($lY = ($y + 1); $lY > ($y - 1); $lY--){
					for($lZ = ($z - 1); $lZ < ($z + 1); $lZ++){
						$block = $level->getBlock(new Vector3($lX, $lY, $lZ));
						if($block->getId() === Block::WATER and $level->getBlock(new Vector3($lX, $lY + 1, $lZ))->getId() === Block::AIR){
							$this->task->addBlock(new Position($block->getFloorX(), $block->getFloorY(), $block->getFloorZ(), $block->getLevel()), $block);
							$level->setBlock(new Vector3($lX, $lY, $lZ), Block::get(Block::ICE));
						}
					}
				}
			}
		}
	}
}
