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

namespace legionpe\theta\classic\battle;

use legionpe\theta\classic\ClassicPlugin;
use pocketmine\scheduler\PluginTask;

class BattleTask extends PluginTask{
	/** @var ClassicPlugin */
	private $main;

	public function __construct(ClassicPlugin $main){
		parent::__construct($main);
		$this->main = $main;
	}
	public function onRun($ticks){
		foreach($this->main->getBattles() as $battle){
			$time = $battle->getTime();
			switch($battle->getStatus()){
				case ClassicBattle::STATUS_STARTING:
					if($time === 0){
						$battle->setStatus(ClassicBattle::STATUS_RUNNING, "Battle has started!");
					}else{
						$battle->broadcast("Starting in {$time}...", "tip");
						$battle->setTime(--$time);
					}
					break;
				case ClassicBattle::STATUS_RUNNING:
					if($time === 0){
						$battle->setStatus(ClassicBattle::STATUS_STARTING, "No one won this round. Starting next round...");
					}else{
						$battle->broadcast("Ending in {$time} seconds", "tip");
						$battle->setTime(--$time);
					}
					break;
			}
		}
	}
}
