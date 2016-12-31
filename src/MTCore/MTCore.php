<?php

namespace MTCore;

use MTCore\MySQL\AddTokensQuery;
use MTCore\MySQL\BanQuery;
use MTCore\MySQL\DisplayMoneyQuery;
use MTCore\MySQL\JoinQuery;
use MTCore\MySQL\SetPermissionsTask;
use MTCore\MySQL\SetRankQuery;
use MTCore\MySQL\SubstractTokensQuery;
use MTCore\MySQL\UpdateStatsQuery;
use MTCore\Object\PlayerData;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\entity\Effect;
use pocketmine\entity\Human;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityArmorChangeEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityInventoryChangeEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerHungerChangeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;

class MTCore extends PluginBase implements Listener{

    /** @var AuthManager $authmgr */
    public $authmgr;

    /** @var Level $level */
    public $level;

    /** @var Position $lobby */
    public $lobby;

    /** @var PlayerData $players */
    public $players = [];

    /** @var string $miniGame */
    public $miniGame;

    public function onEnable() {
        $this->authmgr = new AuthManager($this);
        $this->level = $this->getServer()->getDefaultLevel();
        $this->level->setTime(0);
        $this->level->stopTime();
        $this->lobby = $this->level->getSpawnLocation();
        //$this->getServer()->getPluginManager()->registerEvents(new EnchantManager($this), $this);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new MessageTask($this), 2400);
        $this->getServer()->getScheduler()->scheduleDelayedTask(new LoadPluginTask($this), 5);

        $this->getServer()->getLogger()->info(self::getPrefix()."§r§aENABLED!");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public static function getPrefix(){
        return "§l§4[§r§6MineTox§l§4] §r§f";
    }

    public function onPreLogin(PlayerPreLoginEvent $e){
        $p = $e->getPlayer();
        new SetPermissionsTask($this, $p->getName());
        if (\count($this->getServer()->getOnlinePlayers()) >= 1){
            if (!$p->hasPermission("minetox.log.full")){
                $e->setKickMessage(self::getPrefix()."\n".
                    "§cSorry, but server is full.\n".
                    "§bVIPs can connect even the server is full!\n".
                    "§aBuy VIP at bit.do/mtBUY");
                $e->setCancelled();
            }
        }
        if (($pl = $this->getServer()->getPlayer($p->getName())) instanceof Player){
            if ($this->isAuthed($pl)){
                $e->setKickMessage(self::getPrefix()."\n".
                    "§cPlayer with same nick is already playing.\n");
            }
        }
    }

    public function onLogin(PlayerLoginEvent $e){
        $p = $e->getPlayer();
        $this->players[strtolower($p->getName())] = new PlayerData($p);
        $p->addEffect(Effect::getEffect(Effect::BLINDNESS)->setAmplifier(1)->setDuration(999999)->setVisible(false));
    }

    public function onJoin(PlayerJoinEvent $e){
        $e->setJoinMessage("");
        $p = $e->getPlayer();

        new JoinQuery($this, $p->getName(), $p->getAddress(), $p->getUniqueId());

        if ($p->getGamemode() !== 0) {
            $p->setGamemode(0);
        }

        $this->setLobby($p, true);
    }

    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
        $sd = $sender;
        $not = false;
        switch ($cmd->getName()){
            case "setrank":
                if (!$sd->hasPermission("minetox.cmd.setrank")){
                    $sd->sendMessage(self::getPrefix()."§cYou don't have permission to perform this command");
                    return true;
                }
                if (\count($args) !== 2){
                    $sd->sendMessage(self::getPrefix()."§6Usage: /setrank <nick> <rank>");
                    return true;
                }
                new SetRankQuery($this, $args[0], $args[1]);
                $sd->sendMessage(self::getPrefix()."§a".$args[0]."§e's rank updated");
                $sd->sendMessage(self::getPrefix()."§a".$args[0]."§e's group changed");
                break;
            case "addcoins":
            case "addtokens":
                if (!$sd->hasPermission("minetox.cmd.addtokens")){
                    $sd->sendMessage(self::getPrefix()."§cYou don't have permission to perform this command");
                    return true;
                }
                if (\count($args) !== 2){
                $sd->sendMessage(self::getPrefix()."§6Usage: /addtokens <nick> <coins>");
                return true;
                }
                new AddTokensQuery($this, $args[0], $args[1]);
                $sd->sendMessage(self::getPrefix()."§aAdded §e{$args[1]} §atokens to §9{$args[0]}'s §abalance'");
                break;
            case "substractcoins":
            case "substracttokens":
                if (!$sd->hasPermission("minetox.cmd.substracttokens")){
                    $sd->sendMessage(self::getPrefix()."§cYou don't have permission to perform this command");
                    return true;
                }
                if (\count($args) !== 2){
                    $sd->sendMessage(self::getPrefix()."§6Usage: /substracttokens <nick> <coins>");
                    return true;
                }
                new SubstractTokensQuery($this, $args[0], $args[1]);
                $sd->sendMessage(self::getPrefix()."§aDeducted §e{$args[1]} §atokens from §9{$args[0]}'s §abalance'");
            break;
            case "msg":
                if (!$sd->hasPermission("minetox.cmd.message")){
                    $sd->sendMessage(self::getPrefix()."§cYou don't have permission to use private messages\n".
                        "§eBuy VIP at §bbit.do/mtBUY §eto have an option of private chat!");
                    return true;
                }
                if (\count($args) < 2){
                    $sd->sendMessage(self::getPrefix()."§6Usage: /msg <player> <message>");
                    return true;
                }
                $p = $this->getServer()->getPlayerExact($args[0]);
                if (!($p instanceof Player)){
                    $sd->sendMessage(self::getPrefix()."§cThe player is not online.");
                    return true;
                }
                $msg = \str_replace($args[0], "", implode(" ", $args));
                $snick = $sd instanceof Player ? self::getDisplayRank($sd).$sd->getName() : "§d[CONSOLE] §f";
                $p->sendMessage($snick."§b> §7[Me] §e:§3".self::getChatColor($sd).
                        \str_replace("&", "§", $msg));
                $sd->sendMessage("§7[Me] §b> §e".$p->getDisplayName()." §e:§3".self::getChatColor($sd).
                        \str_replace("&", "§", $msg));
                break;
            case "ban":
                if (!$sd->hasPermission("minetox.ban")){
                    $sd->sendMessage(self::getPrefix()."§cYou do not have permission to perform this command");
                    return true;
                }
                if (\count($args) < 2){
                    $sd->sendMessage(self::getPrefix()."§6Usage: /ban <player> <reason>");
                    return true;
                }
                $p = $this->getServer()->getPlayerExact($args[0]);
                if ($p instanceof Player && ($p->hasPermission("minetox.immune"))){
                    $sd->sendMessage(self::getPrefix()."§cCan not ban this player; Perhaps you are trying to ban server staff?");
                    return true;
                }
                $reason = \str_replace($args[0], "", implode(" ", $args));
                new BanQuery($this, $args[0], $sd->getName(), $reason);
                break;
            case "help":
                $sd->sendMessage(
                    "§7-------------------------------------\n".
                    self::getPrefix()."§eHelp Page §b1/1\n".
                    "§7-------------------------------------\n".
                    "§b/changepwd §e=> §aChange player password\n".
                    "§b/login §e=> §aLogin to your account\n".
                    "§b/msg §e=> §aSends a private message to player\n".
                    "§b/register §e=> §aRegister a new account\n".
                    "§b/tokens §e=> §aShows your tokens balance\n".
                    "§7-------------------------------------"
                );
                break;
            default:
                $not = true;
                break;
        }

        if ($not && $sender instanceof ConsoleCommandSender){
            $sender->sendMessage(self::getPrefix()."§cTyto prikazy nejsou dostupne pro konzoli.");
            return true;
        }
        /** @var Player $sd */
        $sd = $sender;
        switch ($cmd->getName()){
            case "coins":
            case "tokens":
            case "money":
                new DisplayMoneyQuery($this, $sd->getName());
                break;
            case "register":
                if (\count($args) !== 2){
                    $sd->sendMessage(self::getPrefix()."§6Use /register <password> <password>");
                    return true;
                }
                if ($args[0] != $args[1]){
                    $sd->sendMessage(self::getPrefix()."§cBoth passwords need to be same!");
                    return true;
                }
                $this->authmgr->register($sd, $args[0]);
                break;
            case "login":
                if (\count($args) !== 1){
                    $sd->sendMessage(self::getPrefix()."§6Use /login <password>");
                    return true;
                }
                $this->authmgr->login($sd, $args[0]);
                break;
            case "changepwd":
            case "changepassword":
                if (\count($args) !== 2){
                    $sd->sendMessage(self::getPrefix()."§6Use /changepwd <oldpassword> <newpassword>");
                    return true;
                }
                $this->authmgr->changePassword($sd, $args[0], $args[1]);
                break;
        }
        return true;
    }

    public static function getDisplayRank(Player $p){
        if ($p->hasPermission("minetox.owner")){
            return "§l§a[§r§aOwner§l] §r§a";
        }
        elseif ($p->hasPermission("minetox.developper")){
            return "§l§3[§r§3Developper§l] §r§3";
        }
        elseif ($p->hasPermission("minetox.banner")){
            return "§l§c[§r§cHelper§l] §r§c";
        }
        elseif ($p->hasPermission("minetox.builder")){
            return "§l§2[§r§2Builder§l] §r§2";
        }
        elseif ($p->hasPermission("minetox.youtuber")){
            return "§l§f[§fYou§4Touber§f] §r§f";
        }
        elseif ($p->hasPermission("minetox.extra")){
            return "§l§c[§cExtra§l] §r§c";
        }
        elseif ($p->hasPermission("minetox.vip+")){
            return "§l§b[§bVIP§e+§l] §r§b";
        }
        elseif ($p->hasPermission("minetox.vip")){
            return "§l§b[§bVIP§l] §r§b";
        }
        else {
            return "";
        }
    }

    /**
     * @param Player|ConsoleCommandSender $p
     * @return string
     */

    public static function getChatColor($p){
        if ($p->hasPermission("minetox.owner")){
            return "§a";
        }
        elseif ($p->hasPermission("minetox.developper")){
            return "§b";
        }
        elseif ($p->hasPermission("minetox.banner")){
            return "§c";
        }
        elseif ($p->hasPermission("minetox.builder")){
            return "§2";
        }
        elseif ($p->hasPermission("minetox.youtuber")){
            return "§f";
        }
        elseif ($p->hasPermission("minetox.extra")){
            return "§c";
        }
        elseif ($p->hasPermission("minetox.vip+")){
            return "§b";
        }
        elseif ($p->hasPermission("minetox.vip")){
            return "§b";
        }
        else {
            return "";
        }
    }

    public function onChat(PlayerChatEvent $e) {

        $p = $e->getPlayer();
        $e->setCancelled();

        if (!$this->isAuthed($p)) {
            $p->sendMessage(self::getPrefix()."§cYou are not logged in");
            return;
        }

        /** @var PlayerData $pl */
        $pl = $this->players[strtolower($p->getName())];
        $diff = ($pl->getChatTick()+5) - time();

        if ($diff > 0 && !$pl->getChatTick() == 0 && !$p->hasPermission("minetox.waitbypass")){
            $p->sendMessage(self::getPrefix()."§cPlease wait $diff seconds until chatting again");
            $pl->setTick(time());
            return;
        }
        $pl->setTick(time());

        $ips = [".cz", ".eu", ".sk", ".tk", ".com", ".net", "lifeboat", "inpvp"];
        foreach ($ips as $ip){
            if (stripos($e->getMessage(), $ip) !== false){
                $p->kick(self::getPrefix()."\n§cYou have been kicked due to:\n§eServer advertising");
                return;
            }
        }

        $slova = ['kurva', 'kurvo', 'piča', 'pussy', 'kokot', 'kkt', 'pičo', 'kokote', 'seru', 'sereš', 'seres', 'curak', 'čůrák',
            'curák' . 'cůrák', 'kunda', 'kundo', 'jeba', 'jebat', 'hovno', 'fuck', 'kreten', 'kretén', 'idiot', 'debil', 'blbec',
            'mrd', 'pica', 'pico', 'pic', 'penis', 'shit', 'zkurvysyn', 'vyser', 'zaser', 'hovno', 'hovn', 'zasrany'];
        foreach ($slova as $s) {
            if (stripos(strtolower($e->getMessage()), $s) !== false) {
                $p->sendMessage(self::getPrefix()."§cDo not swear!");
                return;
            }
        }

        if ($pl->inLobby()) {
            $this->messageLobbyPlayers($e->getMessage(), $p);
            return;
        }
    }

    public function messageLobbyPlayers($message, Player $p) {

        if (!$p->hasPermission("minetox.color")){
            $message = str_replace("§", "", $message);
        }
        $msg = self::getDisplayRank($p).$p->getName()."§3 > ".self::getChatColor($p).$message;

        /** @var PlayerData $pl */
        foreach ($this->players as $pl) {
            if ($pl->inLobby()){
                $pl->getPlayer()->sendMessage($msg);
            }
        }
        $this->getServer()->getLogger()->info($msg);
    }

    public function onPlayerInteract(PlayerInteractEvent $e) {
        $p = $e->getPlayer();

        if (!$this->isAuthed($p)) {
            $p->sendMessage(self::getPrefix()."§cYou are not logged in");
            $e->setCancelled();
            return;
        }

        /** @var PlayerData $pl */
        $pl = $this->players[strtolower($p->getName())];

        if ($pl->inLobby() && $e->getItem()->getId() === 347 && $e->getAction() === PlayerInteractEvent::RIGHT_CLICK_AIR){
            if ($pl->isPlayersVisible()){
                $this->despawnPlayersFrom($p);
                $pl->setPlayersVisible(false);
                $p->getInventory()->remove($p->getInventory()->getItemInHand());
                $p->getInventory()->addItem(Item::get(347, 0, 1)->setCustomName("§r§eShow Players"));
                $p->sendMessage("§eVanished all players");
            }
            else {
                $this->spawnPlayersTo($p);
                $pl->setPlayersVisible(true);
                $p->getInventory()->remove($p->getInventory()->getItemInHand());
                $p->getInventory()->addItem(Item::get(347, 0, 1)->setCustomName("§r§eHide Players"));
                $p->sendMessage("§eAll players are now visible");
            }
        }
    }

    public function onPlayerDropItem(PlayerDropItemEvent $e) {
        if (!$this->isAuthed($e->getPlayer())) {
            $e->getPlayer()->sendMessage(self::getPrefix()."§cYou are not logged in");
            $e->setCancelled();
        }
    }

    public function onPlayerItemConsume(PlayerItemConsumeEvent $e) {
        if (!$this->isAuthed($e->getPlayer())) {
            $e->getPlayer()->sendMessage(self::getPrefix()."§cYou are not logged in");
            $e->setCancelled();
        }

        new UpdateStatsQuery($this, $e->getPlayer()->getName(), UpdateStatsQuery::EATEN);
    }

    public function onPlayerItemHeld(PlayerItemHeldEvent $e) {
        if (!$this->isAuthed($e->getPlayer())) {
            $e->setCancelled();
        }
    }

    public function onPlayerQuit(PlayerQuitEvent $e) {
        unset($this->players[strtolower($e->getPlayer()->getName())]);
        $e->setQuitMessage("");
    }

    public function onPlayerKick(PlayerKickEvent $e) {
        $e->setQuitMessage("");
    }

    public function onEntityArmorChange(EntityArmorChangeEvent $e) {
        /** @var Player $p */
        $p = $e->getEntity();
        if (!$p instanceof Player || !$this->isAuthed($p)) {
            $e->setCancelled();
        }
    }

    public function onEntityInventoryChange(EntityInventoryChangeEvent $e) {
        /** @var Player $p */
        $p = $e->getEntity();
        if (!$p instanceof Player || !$this->isAuthed($p)) {
            $e->setCancelled();
        }
    }

    public function onEntityRegainHealth(EntityRegainHealthEvent $e) {
        /** @var Player $p */
        $p = $e->getEntity();
        if (!$p instanceof Player || !$this->isAuthed($p)) {
            $e->setCancelled();
        }
    }

    public function onEntityShootBow(EntityShootBowEvent $e) {
        /** @var Player $p */
        $p = $e->getEntity();
        if (!$p instanceof Player || !$this->isAuthed($p)) {
            $e->setCancelled();
        }
    }

    public function onEntityDamage(EntityDamageEvent $e) {
        /** @var Player $p */
        $p = $e->getEntity();

        /** @var PlayerData $pl */
        $pl = $this->players[strtolower($p->getName())];

        if ($e->getCause() === EntityDamageEvent::CAUSE_VOID && $pl->inLobby()){
            $p->teleport($this->lobby);
        }

        if ($e instanceof EntityDamageByEntityEvent){
            /** @var Player $px */
            if (!(($px = $e->getDamager()) instanceof Player)){
                $e->setCancelled();
                return;
            }
            /** @var PlayerData $pxa */
            $pxa = $this->players[strtolower($px->getName())];

            if ($pl->inLobby() && $pxa->inLobby()){
                $e->setCancelled();
                $px->despawnFrom($p);
            }
            if (!$p instanceof Player){
                $e->setCancelled();
                if ($p instanceof Human && $this->miniGame == "annihilation"){
                    //$this->getServer()->getPluginManager()->getPlugin("Annihilation")->onHit(); ToDo: Přidat k Anni
                }
                return;
            }
        }
        if (!$this->isAuthed($p) || $p->getLevel() === $this->level){
            $e->setCancelled();
        }

    }

    public function onBlockPlace(BlockPlaceEvent $e) {
        $p = $e->getPlayer();

        if (!$this->isAuthed($p)) {
            $p->sendMessage(self::getPrefix()."§cYou are not logged in");
            $e->setCancelled();
        }

        if ($p->getLevel() == $this->level && !$p->hasPermission("minetox.place")) {
            $e->setCancelled();
        }

        new UpdateStatsQuery($this, $p->getName(), UpdateStatsQuery::PLACES);
    }

    public function onBlockBreak(BlockBreakEvent $e) {
        $p = $e->getPlayer();

        if (!$this->isAuthed($p)) {
            $p->sendMessage(self::getPrefix()."§cYou are not logged in");
            $e->setCancelled();
        }

        if ($p->getLevel() == $this->level && !$p->hasPermission("minetox.break")) {
            $e->setCancelled();
        }

        new UpdateStatsQuery($this, $p->getName(), UpdateStatsQuery::BREAKS);
    }

    public function isAuthed(Player $p){
        /** @var PlayerData $pl */
        $pl = $this->players[strtolower($p->getName())];

        return $pl->isAuthed();
    }

    public function commandPreprocces(PlayerCommandPreprocessEvent $e) {
        $p = $e->getPlayer();

        $msg = strtolower($e->getMessage());

        if (!$this->isAuthed($p) && strpos($msg, '/login') !== 0 && strpos($msg, '/register') !== 0) {
            $p->sendMessage(self::getPrefix()."§cYou are not logged in");
            $e->setCancelled();
            return;
        }

        $e->setMessage(str_replace("&", "§", $e->getMessage()));
    }

    public function onQuery(QueryRegenerateEvent $e){
        if ($e->getPlayerCount() > 60) $e->setPlayerCount(60);
        $e->setMaxPlayerCount(60);
    }

    public function onUpdate(BlockUpdateEvent $e){
        $e->setCancelled();
    }

    public function setLobby(Player $p, $join = false) {
        $p->setHealth(20);
        $p->setFood(20);

        /** @var PlayerData $pl */
        $pl = $this->players[$p->getName()];
        $pl->setPlayersVisible(true);

        if (!$join && $p->getInventory() instanceof PlayerInventory) {
            $p->getInventory()->clearAll();
            $p->getInventory()->setItem(0, Item::get(Item::CLOCK, 0, 1)->setCustomName("§r§eHide Players"));
            $p->getInventory()->setItem(1, Item::get(Item::GOLD_INGOT, 0, 1));
            $p->getInventory()->setHotbarSlotIndex(0, 0);
            $p->getInventory()->setHotbarSlotIndex(1, 1);
            $p->getInventory()->sendContents($p);
            $p->setDisplayName(self::getDisplayRank($p)." ".$p->getName());
            $p->setNameTag($p->getDisplayName());
        }

    }

    public function despawnPlayersFrom(Player $p) {

        foreach ($this->level->getPlayers() as $pl) {
            $pl->despawnFrom($p);
        }

    }

    public function spawnPlayersTo(Player $p) {

        foreach ($this->level->getPlayers() as $pl) {
            $pl->spawnTo($p);
        }

    }

    public function onHunger(PlayerHungerChangeEvent $e){
        if ($e->getPlayer()->getLevel() === $this->level){
            $e->setCancelled();
        }
    }

    public function sendText(Player $p, $perm){
        if ($p->hasPermission($perm)){
            $p->sendMessage("Mas pravo na permissi $perm");
            return;
        }
        $p->sendMessage("Nemas pravo na permissi $perm");
    }

}

class LoadPluginTask extends Task{

    private $plugin;

    public function __construct(MTCore $plugin){
        $this->plugin = $plugin;
    }

    public function onRun($t) {
        $bedwars = $this->plugin->getServer()->getPluginManager()->getPlugin("BedWars");
        if ($bedwars instanceof Plugin && $bedwars->isEnabled()) {
            $this->plugin->miniGame = "bedwars";
            $this->plugin->getServer()->getNetwork()->setName("§6 MineTox §f§lBed§4Wars");
        }

        $anni = $this->plugin->getServer()->getPluginManager()->getPlugin("Annihilation");
        if ($anni instanceof Plugin && $anni->isEnabled()) {
            $this->plugin->miniGame = "annihilation";
            $this->plugin->getServer()->getNetwork()->setName("§6 MineTox §9§lAnnihilation");
        }
    }
}

class MessageTask extends Task{

    public static $messages = ["§3Vote for §6MineTox §3at §abit.do/mtVOTE",
        "§3Do you need help? Ask us on the twitter: §atwitter.com/MineTox_MCPE",
        "§3You can buy VIP rank at §abit.do/mtBUY", "§3Want to play with friends? Join the same IP and port",
        "§3See server status at §astatus.minetox.cz", "§3Register at our forums and get 3000 Tokens! §abit.do/mtFORUMS"
    ];
    private $i = 0;
    private $plugin;

    public function __construct(MTCore $plugin){
        $this->plugin = $plugin;
    }

    public function onRun($currentTick){
        if($this->i >= \count(self::$messages)){
            $this->i = 0;
        }

        foreach($this->plugin->getServer()->getOnlinePlayers() as $p){
            $p->sendMessage(MTCore::getPrefix().self::$messages[$this->i]);
        }

        $this->plugin->getServer()->getLogger()->info(MTCore::getPrefix().self::$messages[$this->i]);
        $this->i++;

    }


}