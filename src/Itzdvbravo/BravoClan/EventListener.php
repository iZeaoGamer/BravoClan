<?php

namespace Itzdvbravo\BravoClan;

use Itzdvbravo\BravoClan\Main;
use Itzdvbravo\BravoClan\Database;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\Server;

class EventListener implements Listener{
    private $plugin;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }
    public function onChat(PlayerChatEvent $event){
        $player = $event->getPlayer();
        if (!array_key_exists(strtolower($player->getName()), $this->plugin->getClans()->chat)) return;
        $msg = $event->getMessage();
        $event->setCancelled(true);
        $clan = $this->plugin->getDatabase()->getClan($this->plugin->getClans()->chat[strtolower($player->getName())]);
        $minfo = $this->plugin->getDatabase()->getMember(strtolower($player->getName()));
        if (!$this->plugin->getDatabase()->isInClan(strtolower($player->getName())) or $clan['clan'] !== $minfo['clan']){
            unset($this-<plugin->getClans()->chat[strtolower($player->getName())]);
            return;
        }
        $members = $this->plugin->getDatabase()->clanMembers($clan['clan']);
        foreach ($members as $member) {
            if ($this->plugin->isOnline($member)) {
                $getm = Server::getInstance()->getPlayer($member);
                $rank = $clan['leader'] === strtolower($player->getName()) ? "leader" : "member";
                $getm->sendMessage("§o§e[{$clan['clan']}] §a[$rank] §5{$player->getName()} §a-> §e{$msg}");
            }
        }
    }
    public function onDamage(EntityDamageEvent $event){
        if ($event instanceof EntityDamageByEntityEvent){
            $player = $event->getEntity();
            $hitter = $event->getDamager();
            if ($player instanceof Player && $hitter instanceof Player){
                if ($this->plugin->getDatabase()->isInClan(strtolower($player->getname())) && $this->plugin->getDatabase()->isInClan(strtolower($hitter->getname()))) {
                    if ($this->plugin->getClans()->player[strtolower($player->getName())] === $this->plugin->getClans()->player[strtolower($hitter->getName())]) {
                        $event->setCancelled(true);
                    }
                }
            }
        }
    }
    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        if ($this->plugin->getDatabase()->isInClan(strtolower($player->getname()))){
            $clan = $this->plugin->getDatabase()->getClan($this->plugin->getDatabase()->getMember(strtolower($player->getName()))['clan']);
            $this->plugin->getClans()->player[strtolower($player->getName())] = $clan["clan"];
        }
    }
    public function onLeave(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        if ($this->plugin->getDatabase()->isInClan(strtolower($player->getname()))) {
            unset($this->plugin->getClans()->player[strtolower($player->getName())]);
        }
    }
    public function onKill(PlayerDeathEvent $event){
        $player = $event->getPlayer();
        if ($player instanceof Player) {
            $cause = $player->getLastDamageCause();
            if ($cause instanceof EntityDamageByEntityEvent) {
                $killer = $cause->getDamager();
                if ($killer instanceof Player) {
                    if ($this->plugin->getDatabase()->isInClan(strtolower($player->getname()))) {
                        $clan = $this->plugin->getDatabase()->getClan($this->plugin->getClans()->player[strtolower($killer->getName())]);
                        $this->plugin->getClans()->onClanMemberKill($clan, $killer);
                    }
                    if ($this->plugin->getDatabase()->isInClan(strtolower($killer->getname()))) {
                        $clan = $this->plugin->getDatabase()->getClan($this->plugin->getClans()->player[strtolower($player->getName())]);
                        $this->plugin->getClans()->onClanMemberDeath($clan, $player);
                    }
                }
            }
        }
    }
}
