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
			$block[0]->getLevel()->setBlock($block[0], $block[1]);
		}
	}
	public function addBlock(Position $position, Block $block){
		$this->blocks[] = [$position, $block];
	}
}
