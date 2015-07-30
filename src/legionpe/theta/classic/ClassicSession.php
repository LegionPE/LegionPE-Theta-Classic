<?php

/*
 * LegionPE Theta
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

namespace legionpe\theta\classic;

use legionpe\theta\BasePlugin;
use legionpe\theta\lang\Phrases;
use legionpe\theta\Session;
use legionpe\theta\utils\MUtils;
use pocketmine\block\Block;
use pocketmine\entity\Egg;
use pocketmine\entity\Projectile;
use pocketmine\entity\Snowball;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\item\Apple;
use pocketmine\item\Bow;
use pocketmine\item\IronBoots;
use pocketmine\item\IronChestplate;
use pocketmine\item\IronHelmet;
use pocketmine\item\IronSword;
use pocketmine\item\Item;
use pocketmine\item\LeatherPants;
use pocketmine\Player;

class ClassicSession extends Session{
	/** @var BasePlugin */
	private $main;
	/** @var bool */
	private $friendlyFire;
	/** @var float */
	public $lastHurtTime = 0.0, $nextCooldownTimeout = 0.0;
	/** @var Block|null */
	private $lastDamagePosition = null;
	/** @var float */
	private $lastKillTime = 0.0, $nextKillstreakTimeout = ClassicConsts::KILLSTREAK_TIMEOUT_BASE, $lastDamageTime = 0.0;
	/** @var EntityDamageByEntityEvent|null */
	private $lastFallCause = null;
	private $counter = 0;
	public function __construct(BasePlugin $main, Player $player, array $loginData){
		$this->main = $main;
		parent::__construct($player, $loginData);
		if(!$this->getLoginDatum("pvp_init")){
			$this->setLoginDatum("pvp_init", time());
		}
	}
	public function joinedClassicSince(){
		return $this->getLoginDatum("pvp_init");
	}
	public function getKills(){
		return $this->getLoginDatum("pvp_kills");
	}
	public function getDeaths(){
		return $this->getLoginDatum("pvp_deaths");
	}
	public function onDamage(EntityDamageEvent $event){
		if(!parent::onDamage($event)){
			return false;
		}
		if($event->getCause() === EntityDamageEvent::CAUSE_SUICIDE){
			return true;
		}
		if(ClassicConsts::isSpawn($this->getPlayer())){
			return false;
		}
		if($event instanceof EntityDamageByEntityEvent){
			$fromEnt = $event->getDamager();
			if($fromEnt instanceof Player){
				$ses = $this->getMain()->getSession($fromEnt);
				if(ClassicConsts::isSpawn($fromEnt)){
					if($ses instanceof ClassicSession){
						$fromEnt->sendTip($ses->translate(Phrases::PVP_ATTACK_SPAWN));
					}
					return false;
				}
				if($ses instanceof ClassicSession){
					$type = $this->getFriendType($ses->getUid());
					if($type >= self::FRIEND_LEVEL_GOOD_FRIEND and !$this->isFriendlyFire() and !$ses->isFriendlyFire()){
						$fromEnt->sendTip($this->translate(Phrases::PVP_ATTACK_FRIENDS, [
							"target" => $this->getInGameName(),
							"type" => $this->translate(self::$FRIEND_TYPES[$type])
						]));
						return false;
					}
					$now = microtime(true);
					if($now - $this->lastHurtTime < $this->nextCooldownTimeout){
						return false;
					}
					$this->lastHurtTime = microtime(true);
					$this->nextCooldownTimeout = ClassicConsts::DEFAULT_COOLDOWN_TIMEOUT;
				}
			}
			$this->lastDamagePosition = $this->getPlayer()->getLevel()->getBlock($this->getPlayer());
			$this->lastDamageTime = microtime(true);
		}elseif($event->getCause() === EntityDamageEvent::CAUSE_FALL){
			$last = $this->getPlayer()->getLastDamageCause();
			if($last instanceof EntityDamageByEntityEvent){
				$this->lastFallCause = $last;
			}
		}
		return true;
	}
	/**
	 * @return ClassicPlugin
	 */
	public function getMain(){
		return $this->main;
	}
	/**
	 * @return boolean
	 */
	public function isFriendlyFire(){
		return $this->friendlyFire;
	}
	/**
	 * @param boolean $friendlyFire
	 */
	public function setFriendlyFire($friendlyFire){
		$this->friendlyFire = $friendlyFire;
	}
	public function onDeath(PlayerDeathEvent $event){
		if(!parent::onDeath($event)){
			return false;
		}
		$event->setDeathMessage("");
		$event->setDrops([]);
		$streak = $this->getCurrentStreak();
		$maxStreak = $this->getMaximumStreak();
		$this->setMaximumStreak(max($streak, $maxStreak));
		$this->setCurrentStreak();
		$cause = $this->getPlayer()->getLastDamageCause();
		$this->addDeath();
		if(!($cause instanceof EntityDamageEvent)){
			$this->send(Phrases::PVP_DEATH_GENERIC);
			return true;
		}
		if($cause instanceof EntityDamageByEntityEvent){
			$killer = $cause->getDamager();
			if($killer instanceof Player){
				/** @var static $ks */
				$ks = $this->getMain()->getSession($killer);
				if($ks instanceof Session){
					$kn = $ks->getInGameName();
					$amount = ClassicConsts::getKillHeal($ks);
					$killer->heal($amount, new EntityRegainHealthEvent($killer, $amount, EntityRegainHealthEvent::CAUSE_CUSTOM));
				}
			}
			if(!isset($kn)){
				$kn = (new \ReflectionClass($killer))->getShortName();
				MUtils::word_camelToStd($kn);
				MUtils::word_addSingularArticle($kn);
			}
			$data = ["killer" => $kn, "action" => $this->translate(Phrases::PVP_ACTION_GENERIC)];
			if($cause instanceof EntityDamageByChildEntityEvent){
				$child = $cause->getChild();
				if($child instanceof Snowball){
					$data["action"] = $this->translate(Phrases::PVP_ACTION_SNOWBALL);
				}elseif($child instanceof Egg){
					$data["action"] = $this->translate(Phrases::PVP_ACTION_EGG);
				}elseif($child instanceof Projectile){
					$data["action"] = $this->translate(Phrases::PVP_ACTION_ARROW);
				}
			}
			$this->send(Phrases::PVP_DEATH_KILLED, $data);
			if(isset($ks) and ($ks instanceof ClassicSession)){
				$data["victim"] = $this->getInGameName();
				$ks->send(Phrases::PVP_KILL_KILLED, $data);
				$ks->addKill();
			}
		}elseif($cause->getCause() === EntityDamageEvent::CAUSE_FALL and microtime(true) - $this->lastDamageTime < 2.5){
			$knock = $this->lastFallCause;
			$pos = $this->lastDamagePosition;
			$id = $pos->getId();
			if($id === Block::LADDER){
				$deathPhrase = Phrases::PVP_DEATH_FALL_LADDER;
				$killPhrase = Phrases::PVP_KILL_FALL_LADDER;
			}elseif($id === Block::VINE){
				$deathPhrase = Phrases::PVP_DEATH_FALL_VINE;
				$killPhrase = Phrases::PVP_KILL_FALL_VINE;
			}else{
				$deathPhrase = Phrases::PVP_DEATH_FALL_GENERIC;
				$killPhrase = Phrases::PVP_KILL_FALL_GENERIC;
			}
			$killer = $knock->getDamager();
			if($killer instanceof Player and ($ks = $this->getMain()->getSession($killer)) instanceof Session){
				$kn = $ks->getInGameName();
			}else{
				$kn = (new \ReflectionClass($killer))->getShortName();
				MUtils::word_camelToStd($kn);
				MUtils::word_addSingularArticle($kn);
			}
			$data = ["killer" => $kn, "victim" => $this->getInGameName(), "action" => $this->translate(Phrases::PVP_ACTION_GENERIC)];
			if($knock instanceof EntityDamageByChildEntityEvent){
				$child = $knock->getChild();
				if($child instanceof Snowball){
					$data["action"] = $this->translate(Phrases::PVP_ACTION_SNOWBALL);
				}elseif($child instanceof Egg){
					$data["action"] = $this->translate(Phrases::PVP_ACTION_EGG);
				}elseif($child instanceof Projectile){
					$data["action"] = $this->translate(Phrases::PVP_ACTION_ARROW);
				}
			}
			$this->send($deathPhrase, $data);
			if(isset($ks) and $ks instanceof ClassicSession){
				$ks->send($killPhrase, $data);
				$ks->addKill();
			}
		}
		return true;
	}
	public function getCurrentStreak(){
		return $this->getLoginDatum("pvp_curstreak");
	}
	public function getMaximumStreak(){
		return $this->getLoginDatum("pvp_maxstreak");
	}
	public function setMaximumStreak($kills){
		$this->setLoginDatum("pvp_maxstreak", $kills);
	}
	public function setCurrentStreak($kills = 0){
		$this->setLoginDatum("pvp_curstreak", $kills);
	}
	public function addKill(){
		$kills = $this->incrLoginDatum("pvp_kills");
		list($add, $final) = $this->grantCoins(ClassicPlugin::COINS_ON_KILL);
		if(microtime(true) - $this->lastKillTime < $this->nextKillstreakTimeout){
			$streak = $this->incrLoginDatum("pvp_curstreak");
		}else{
			$this->setLoginDatum("pvp_curstreak", $streak = 1);
		}
		$this->send(Phrases::PVP_KILL_INFO, [
			"literal" => $kills,
			"ord" => $kills . MUtils::num_getOrdinal($kills),
			"streak" => $streak,
			"streakord" => $streak . MUtils::num_getOrdinal($streak),
			"coins" => $final,
			"added" => $add
		]);
	}
	public function addDeath(){
		$deaths = $this->incrLoginDatum("pvp_deaths");
		$this->send(Phrases::PVP_DEATH_INFO, [
			"literal" => $deaths,
			"ord" => $deaths . MUtils::num_getOrdinal($deaths)
		]);
	}
	public function login($method){
		parent::login($method);
		$this->onRespawn(new PlayerRespawnEvent($this->getPlayer(), $this->getPlayer()->getPosition()));
	}
	public function onRespawn(PlayerRespawnEvent $event){
		parent::onRespawn($event);
		$spawn = ClassicConsts::getSpawnPosition($this->getMain()->getServer());
		$this->getPlayer()->setSpawn($spawn);
		$event->setRespawnPosition($spawn);
		$this->getPlayer()->teleport($spawn);
		$this->equip();
		$this->getPlayer()->setMaxHealth(40);
		$this->getPlayer()->setHealth(40);
	}
	protected function equip(){
		$inv = $this->getPlayer()->getInventory();
		$inv->clearAll();
		$inv->setHelmet(new IronHelmet);
		$inv->setChestplate(new IronChestplate);
		$inv->setLeggings(new LeatherPants);
		$inv->setBoots(new IronBoots);
		$inv->sendArmorContents([$this->getPlayer()]);
		$inv->setItem(0, new Bow);
		$inv->setItem(1, new IronSword);
		$inv->setItem(2, new Apple(0, 32));
		$inv->setItem(3, Item::get(Item::ARROW, 0, 32));
		$inv->setHotbarSlotIndex(0, 0);
		$inv->setHotbarSlotIndex(1, 1);
		$inv->setHotbarSlotIndex(2, 2);
		$inv->setHotbarSlotIndex(3, 3);
		$inv->sendContents([$this->getPlayer()]);
	}
	public function onPlace(BlockPlaceEvent $event){
		if(!parent::onPlace($event)){
			return false;
		}
		return $this->isBuilder();
	}
	public function onBreak(BlockBreakEvent $event){
		if(!parent::onBreak($event)){
			return false;
		}
		return $this->isBuilder();
	}
	public function halfSecondTick(){
		parent::halfSecondTick();
		if((++$this->counter) === 10){
			$this->counter = 0;
			if($this->getPlayer()->getHealth() !== $this->getPlayer()->getMaxHealth()){
				$amount = ClassicConsts::getAutoHeal($this);
				$this->getPlayer()->heal($amount, new EntityRegainHealthEvent($this->getPlayer(), $amount, EntityRegainHealthEvent::CAUSE_REGEN));
			}
		}
	}
}
