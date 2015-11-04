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

namespace legionpe\theta\classic\commands;

use legionpe\theta\BasePlugin;
use legionpe\theta\classic\query\TopKillsQuery;
use legionpe\theta\command\SessionCommand;
use legionpe\theta\Session;

class PvpTopCommand extends SessionCommand{
	public function __construct(BasePlugin $main){
		parent::__construct($main, "top", "List the top 5 users", "/top [kd]");
	}
	protected function run(array $args, Session $sender){
		new TopKillsQuery($sender->getMain(), isset($args[0]) and ($args[0] === "kdr" or $args[0] === "kd"), $sender);
	}
}
