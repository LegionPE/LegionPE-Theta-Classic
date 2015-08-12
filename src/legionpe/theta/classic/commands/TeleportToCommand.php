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
use legionpe\theta\classic\ClassicPlugin;
use legionpe\theta\command\SessionCommand;
use legionpe\theta\Session;

class TeleportToCommand extends SessionCommand{
	public function __construct(BasePlugin $main){
		parent::__construct($main, "tp", "Send or accept request to teleport to another player", "/tp <target>", ["tpr", "tp"]);
	}
	protected function run(array $args, Session $sender){
		/** @var ClassicPlugin $main */
		$main = $this->getPlugin();
	}
}
