<?php

/*
 * Theta
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

namespace legionpe\theta\classic\utils;

use pocketmine\block\Block;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\Player;
use pocketmine\scheduler\PluginTask;
use legionpe\theta\classic\ClassicPlugin;

class ResetBlocksTask extends PluginTask{
	/** @var ClassicPlugin */
	private $plugin;

	public $blocks = [];
	/**
	 * @param ClassicPlugin $plugin
	 */
	public function __construct(ClassicPlugin $plugin){
		parent::__construct($plugin);
		$this->plugin = $plugin;
	}
	public function onRun($ticks){
		foreach($this->blocks as $block){
			$continue = true;
			if($block[2] instanceof Player){
				if($block[2]->getPosition()->distance($block[0]) < 3){
					$continue = false;
				}
			}
			if($continue) $block[0]->getLevel()->sendBlocks($this->plugin->getServer()->getOnlinePlayers(), [$block[1]], UpdateBlockPacket::FLAG_ALL_PRIORITY);
		}
	}
	public function addBlock(Position $position, Block $block, Player $player = null){
		$this->blocks[] = [$position, $block, $player];
	}
}
