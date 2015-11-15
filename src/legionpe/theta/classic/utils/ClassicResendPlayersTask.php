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

use legionpe\theta\classic\battle\ClassicBattle;
use legionpe\theta\classic\ClassicPlugin;
use legionpe\theta\classic\ClassicSession;
use legionpe\theta\utils\ResendPlayersTask;

class ClassicResendPlayersTask extends ResendPlayersTask{
	/** @var ClassicPlugin */
	private $plugin;
	/**
	 * @param ClassicPlugin $plugin
	 */
	public function __construct(ClassicPlugin $plugin){
		parent::__construct($plugin);
		$this->plugin = $plugin;
	}

	public function onRun($currentTick){
		foreach($this->getOwner()->getServer()->getOnlinePlayers() as $player){
			$session = $this->plugin->getSession($player->getName());
			if($session instanceof ClassicSession){
				if(!($session->getBattle() instanceof ClassicBattle)){
					$player->respawnToAll();
				}
			}
		}
	}
}
