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

class GroundToIcePower extends ClassicKitPower{
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
			for($lX = ($x - 2); $lX < ($x + 2); $lX++){
				for($lY = ($y + 2); $lY > ($y - 4); $lY--){
					for($lZ = ($z - 2); $lZ < ($z + 2); $lZ++){
						$block = $level->getBlock(new Vector3($lX, $lY, $lZ));
						$blacklisted = [Block::WATER, Block::LAVA, Block::STILL_LAVA, Block::AIR, Block::FENCE, Block::FENCE_GATE, Block::FENCE_GATE_ACACIA, Block::FENCE_GATE_BIRCH, Block::FENCE_GATE_ACACIA, Block::FENCE_GATE_DARK_OAK, Block::FENCE_GATE_JUNGLE, Block::FENCE_GATE_SPRUCE, Block::ACACIA_WOOD_STAIRS, Block::ACACIA_WOODEN_STAIRS, Block::BIRCH_WOOD_STAIRS, Block::BIRCH_WOODEN_STAIRS, Block::BRICK_STAIRS, Block::COBBLE_STAIRS, Block::COBBLESTONE_STAIRS, Block::DARK_OAK_WOOD_STAIRS, Block::DARK_OAK_WOODEN_STAIRS, Block::JUNGLE_WOOD_STAIRS, Block::JUNGLE_WOODEN_STAIRS, Block::OAK_WOOD_STAIRS, Block::OAK_WOODEN_STAIRS, Block::NETHER_BRICKS_STAIRS, Block::QUARTZ_STAIRS, Block::SANDSTONE_STAIRS, Block::SPRUCE_WOOD_STAIRS, Block::SPRUCE_WOODEN_STAIRS, Block::STONE_BRICK_STAIRS];
						if(in_array($block->getId(), $blacklisted) and $level->getBlock(new Vector3($lX, $lY + 1, $lZ))->getId() === Block::AIR){
							$this->task->addBlock(new Position($block->getFloorX(), $block->getFloorY(), $block->getFloorZ(), $block->getLevel()), $block);
							$level->setBlock(new Vector3($lX, $lY, $lZ), Block::get(Block::ICE));
						}
					}
				}
			}
		}
	}
}
