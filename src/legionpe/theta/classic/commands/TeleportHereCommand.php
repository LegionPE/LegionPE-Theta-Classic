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

namespace legionpe\theta\classic\commands;

use legionpe\theta\BasePlugin;
use legionpe\theta\classic\ClassicSession;
use legionpe\theta\command\SessionCommand;
use legionpe\theta\Session;

class TeleportHereCommand extends SessionCommand{
	public function __construct(BasePlugin $main){
		parent::__construct($main, "tphere", "Send or accept request to teleport to you", "/tpa <player>", ["tphere"]);
	}
	protected function run(array $args, Session $sender){
		/** @var ClassicSession $sender */
		if(!isset($args[0])){
			return false;
		}
		$other = $this->getSession($name = array_shift($args));
		if(!($other instanceof ClassicSession)){
			return $this->notOnline($sender, $name);
		}
	}
}
