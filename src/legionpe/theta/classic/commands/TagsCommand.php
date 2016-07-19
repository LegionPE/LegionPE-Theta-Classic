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
use legionpe\theta\classic\ClassicConsts;
use legionpe\theta\command\SessionCommand;
use legionpe\theta\Session;

class TagsCommand extends SessionCommand{
	public function __construct(BasePlugin $main){
		parent::__construct($main, "tags", "View all the tags", "/tags");
	}
	protected function run(array $args, Session $sender){
		$tags = implode(ClassicConsts::$killTags, " §3/§2 ");
		$tags = str_replace($tag = ClassicConsts::getKillsTag($sender->getKills()), "§c" . $tag, $tags);
		return "§bHere are the tags, sorted from worst to best: \n§2 " . $tags;
	}
}
