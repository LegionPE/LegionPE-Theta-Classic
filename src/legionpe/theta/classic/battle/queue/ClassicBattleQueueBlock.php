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

namespace legionpe\theta\classic\battle\queue;

use legionpe\theta\classic\ClassicPlugin;
use legionpe\theta\classic\ClassicSession;
use pocketmine\block\Block;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;

class ClassicBattleQueueBlock{
	/** @var \legionpe\theta\classic\ClassicPlugin */
	private $main;
	/** @var \pocketmine\block\Block */
	private $block;
	/** @var string */
	private $text;
	/** @var int */
	private $type;
	/** @var \pocketmine\level\particle\FloatingTextParticle[]  */
	private $floatingTextParticles = [];

	/**
	 * @param ClassicPlugin $main
	 * @param Block $block
	 * @param string $text
	 * @param int $type
	 */
	public function __construct(ClassicPlugin $main, Block $block, $text, $type){
		$this->main = $main;
		$this->block = $block;
		$this->text = $text;
		$this->type = $type;
	}
	/**
	 * @return Block
	 */
	public function getBlock(){
		return $this->block;
	}
	/**
	 * @return int
	 */
	public function getType(){
		return $this->type;
	}
	/**
	 * @param string $text
	 */
	public function setText($text){
		if(count($this->floatingTextParticles) !== 0){
			foreach($this->floatingTextParticles as $user=>$particle){
				$particle->setText($text);
				$player = $this->main->getServer()->getPlayerExact($user);
				$encode = $particle->encode();
				if(is_array($encode)){
					foreach($encode as $packet){
						$player->dataPacket($packet);
					}
				}else{
					$player->dataPacket($encode);
				}
			}
		}
		$this->text = $text;
	}
	/**
	 * @return string
	 */
	public function getText(){
		return $this->text;
	}
	/**
	 * @param ClassicSession $session
	 */
	public function addSession(ClassicSession $session){
		$particle = new FloatingTextParticle(new Vector3($this->block->getFloorX(), $this->block->getFloorY(), $this->block->getFloorZ()), $this->text, TextFormat::RED . "Battles " . TextFormat::GOLD . "({$this->type}v{$this->type})");
		$this->block->getLevel()->addParticle($particle, [$session->getPlayer()]);
		$this->floatingTextParticles[$session->getPlayer()->getName()] = $particle;
	}
	/**
	 * @param ClassicSession $session
	 */
	public function removeSession(ClassicSession $session){
		if(isset($this->floatingTextParticles[$session->getPlayer()->getName()])){
			unset($this->floatingTextParticles[$session->getPlayer()->getName()]);
		}
	}
}
