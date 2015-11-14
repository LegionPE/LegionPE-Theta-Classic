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
use pocketmine\utils\TextFormat;

class BattleTask extends PluginTask{
	/** @var \legionpe\theta\classic\ClassicPlugin */
	private $main;

	/**
	 * @param \legionpe\theta\classic\ClassicPlugin $main
	 */
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
						$battle->setStatus(ClassicBattle::STATUS_RUNNING, TextFormat::RED . "Battle has started!");
					}else{
						$battle->broadcast("Starting in " . TextFormat::RED . $time . TextFormat::RESET . "...", "tip");
						$battle->setTime(--$time);
					}
					break;
				case ClassicBattle::STATUS_RUNNING:
					if($time === 0){
						if($battle->getMaxRounds() === $battle->getRound()){
							$battle->setStatus(ClassicBattle::STATUS_ENDING, "You ran out of time. No one won this round.", $battle->getOverallWinner());
						}else{
							$battle->setStatus(ClassicBattle::STATUS_STARTING, "No one won this round. Starting next round...");
						}
					}else{
						$battle->broadcast("Ending in " . TextFormat::RED . $time . TextFormat::RESET . " seconds", "tip");
						$battle->setTime(--$time);
					}
					break;
			}
		}
	}
}
