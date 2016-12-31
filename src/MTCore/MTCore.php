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
        return "§l§1[§r§1A§3T§9Games§l§1] §r§f";
    }

    public function onPreLogin(PlayerPreLoginEvent $e){
        $p = $e->getPlayer();
        new SetPermissionsTask($this, $p->getName());
        if (\count($this->getServer()->getOnlinePlayers()) >= 1){
            if (!$p->hasPermission("minetox.log.full")){
                $e->setKickMessage(self::getPrefix()."\n".
                    "§cSorry, server je plny.\n".
                    "§bPouze VIP hraci se muzou pripojit!\n".
                    "§aKup si VIP na bit.do/atBUY");
                $e->setCancelled();
            }
        }
        if (($pl = $this->getServer()->getPlayer($p->getName())) instanceof Player){
            if ($this->isAuthed($pl)){
                $e->setKickMessage(self::getPrefix()."\n".
         "§cHrac se stejnym nickem tady uz hraje.\n");
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
                    $sd->sendMessage(self::getPrefix()."§cNemas permisi na tento prikaz.");
                    return true;
                }
                if (\count($args) !== 2){
                    $sd->sendMessage(self::getPrefix()."§bUsage: /setrank <nick> <rank>");
                    return true;
                }
                new SetRankQuery($this, $args[0], $args[1]);
                $sd->sendMessage(self::getPrefix()."§bHraci §a".$args[0]."§b rank aktualizovan");
                $sd->sendMessage(self::getPrefix()."§bHraci §a".$args[0]."§b group zmenen");
                break;
            case "addcoins":
            case "addtokens":
                if (!$sd->hasPermission("minetox.cmd.addtokens")){
                    $sd->sendMessage(self::getPrefix()."§cNemas permisi na tento prikaz.");
                    return true;
                }
                if (\count($args) !== 2){
                $sd->sendMessage(self::getPrefix()."§bUsage: /addtokens <nick> <coins>");
                return true;
                }
                new AddTokensQuery($this, $args[0], $args[1]);
                $sd->sendMessage(self::getPrefix()."§aPridano §b{$args[1]} §atokenu hracovi §9{$args[0]} §abalance'");
                break;
            case "substractcoins":
            case "substracttokens":
                if (!$sd->hasPermission("minetox.cmd.substracttokens")){
                    $sd->sendMessage(self::getPrefix()."§cNemas permisi na tento prikaz.");
                    return true;
                }
                if (\count($args) !== 2){
                    $sd->sendMessage(self::getPrefix()."§bUsage: /substracttokens <nick> <coins>");
                    return true;
                }
                new SubstractTokensQuery($this, $args[0], $args[1]);
                $sd->sendMessage(self::getPrefix()."§aDeducted §b{$args[1]} §atokens from §9{$args[0]}'s §abalance'");
            break;
            case "msg":
                if (!$sd->hasPermission("minetox.cmd.message")){
                    $sd->sendMessage(self::getPrefix()."§cNemas permisi na pouziti soukromych zprav\n".
                        "§fKup si VIP na §bbit.ly/atBUY §ena pouziti soukromeho chatu.");
                    return true;
                }
                if (\count($args) < 2){
                    $sd->sendMessage(self::getPrefix()."§bUsage: /msg <player> <message>");
                    return true;
                }
                $p = $this->getServer()->getPlayerExact($args[0]);
                if (!($p instanceof Player)){
                    $sd->sendMessage(self::getPrefix()."§cHrac neni online.");
                    return true;
                }
                $msg = \str_replace($args[0], "", implode(" ", $args));
                $snick = $sd instanceof Player ? self::getDisplayRank($sd).$sd->getName() : "§d[CONSOLE] §f";
                $p->sendMessage($snick."§7> §7[Me] §8:§f".self::getChatColor($sd).
                        \str_replace("&", "§", $msg));
                $sd->sendMessage("§7[Me] §7> §f".$p->getDisplayName()." §8:§f".self::getChatColor($sd).
                        \str_replace("&", "§", $msg));
                break;
            case "ban":
                if (!$sd->hasPermission("minetox.ban")){
                    $sd->sendMessage(self::getPrefix()."§cNemas permisi na tento prikaz.");
                    return true;
                }
                if (\count($args) < 2){
                    $sd->sendMessage(self::getPrefix()."§bUsage: /ban <player> <reason>");
                    return true;
                }
                $p = $this->getServer()->getPlayerExact($args[0]);
                if ($p instanceof Player && ($p->hasPermission("minetox.immune"))){
                    $sd->sendMessage(self::getPrefix()."§cNemuzu zabanovat tohoto hrace; Mozna chces zabanovat Nekoho z Ateamu..");
                    return true;
                }
                $reason = \str_replace($args[0], "", implode(" ", $args));
                new BanQuery($this, $args[0], $sd->getName(), $reason);
                break;
            case "help":
                $sd->sendMessage(
                    "§7-------------------------------------\n".
                    self::getPrefix()."§eNapoveda §b1/1\n".
                    "§7-------------------------------------\n".
                    "§b/changepwd §e=> §aZmena hesla\n".
                    "§b/login §e=> §aPrihlaseni hrace\n".
                    "§b/msg §e=> §aSoukroma zprava hraci\n".
                    "§b/register §e=> §aRegistrace hrace\n".
                    "§b/tokens §e=> §aUkaze zustatek tokenu\n".
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
                    $sd->sendMessage(self::getPrefix()."§bPouzij /register <password> <password>");
                    return true;
                }
                if ($args[0] != $args[1]){
                    $sd->sendMessage(self::getPrefix()."§cObe hesla museji byt stejna.");
                    return true;
                }
                $this->authmgr->register($sd, $args[0]);
                break;
            case "login":
                if (\count($args) !== 1){
                    $sd->sendMessage(self::getPrefix()."§bPouzij /login <heslo>");
                    return true;
                }
                $this->authmgr->login($sd, $args[0]);
                break;
            case "changepwd":
            case "changepassword":
                if (\count($args) !== 2){
                    $sd->sendMessage(self::getPrefix()."§6Use /changepwd <stareheslo> <noveheslo>");
                    return true;
                }
                $this->authmgr->changePassword($sd, $args[0], $args[1]);
                break;
        }
        return true;
    }

    public static function getDisplayRank(Player $p){
        if ($p->hasPermission("minetox.owner")){
            return "§l§a[§r§aMajitel§l] §r§a";
        }
        elseif ($p->hasPermission("minetox.developper")){
            return "§l
            
 [§r§3Technik§l] §r§3";
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
            $p->sendMessage(self::getPrefix()."§cNejsi prihlaseny!");
            return;
        }

        /** @var PlayerData $pl */
        $pl = $this->players[strtolower($p->getName())];
        $diff = ($pl->getChatTick()+1) - time();

        if ($diff > 0 && !$pl->getChatTick() == 0 && !$p->hasPermission("minetox.waitbypass")){
            $p->sendMessage(self::getPrefix()."§cPockej prosim $diff sekund abys mohl psat");
            $pl->setTick(time());
            return;
        }
        $pl->setTick(time());

        $ips = [".cz", ".eu", ".sk", ".tk", ".com", ".net", "lifeboat", "inpvp"];
        foreach ($ips as $ip){
            if (stripos($e->getMessage(), $ip) !== false){
                $p->kick(self::getPrefix()."\n§cByl jsi automaticky kicknut za:\n§eServer reklamu");
                return;
            }
        }

        $slova = ['kurva', 'kurvo', 'piča', 'pussy', 'kokot', 'kkt', 'pičo', 'kokote', 'seru', 'sereš', 'seres', 'curak', 'čůrák',
            'curák' . 'cůrák', 'kunda', 'kundo', 'jeba', 'jebat', 'hovno', 'fuck', 'kreten', 'kretén', 'idiot', 'debil', 'blbec',
            'mrd', 'pica', 'pico', 'pic', 'penis', 'shit', 'zkurvysyn', 'vyser', 'zaser', 'hovno', 'hovn', 'zasrany'];
        foreach ($slova as $s) {
            if (stripos(strtolower($e->getMessage()), $s) !== false) {
                $p->sendMessage(self::getPrefix()."§cSprostá slova zakázana!");
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
        $msg = self::getDisplayRank($p).$p->getName()."§7 > ".self::getChatColor($p).$message;

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
            $p->sendMessage(self::getPrefix()."§cNejsi prihlaseny!");
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
                $p->getInventory()->addItem(Item::get(347, 0, 1)->setCustomName("§r§eUkazani hracu"));
                $p->sendMessage("§eZneviditelneni vsech hracu");
            }
            else {
                $this->spawnPlayersTo($p);
                $pl->setPlayersVisible(true);
                $p->getInventory()->remove($p->getInventory()->getItemInHand());
                $p->getInventory()->addItem(Item::get(347, 0, 1)->setCustomName("§r§eSchovani hracu"));
                $p->sendMessage("§eVsichni hraci jsou ted viditelni");
            }
        }
    }

    public function onPlayerDropItem(PlayerDropItemEvent $e) {
        if (!$this->isAuthed($e->getPlayer())) {
            $e->getPlayer()->sendMessage(self::getPrefix()."§cNejsi prihlaseny!");
            $e->setCancelled();
        }
    }

    public function onPlayerItemConsume(PlayerItemConsumeEvent $e) {
        if (!$this->isAuthed($e->getPlayer())) {
            $e->getPlayer()->sendMessage(self::getPrefix()."§cNejsi prihlaseny!");
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
            $p->sendMessage(self::getPrefix()."§cNejsi prihlaseny!");
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
            $p->sendMessage(self::getPrefix()."§cNejsi prihlaseny!");
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
            $p->sendMessage(self::getPrefix()."§cNejsi prihlaseny!");
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
            $p->getInventory()->setItem(0, Item::get(Item::CLOCK, 0, 1)->setCustomName("§r§eSchovat hrace"));
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
            $this->plugin->getServer()->getNetwork()->setName("§bATGames §f§lBed§4Wars");
        }

        $anni = $this->plugin->getServer()->getPluginManager()->getPlugin("Annihilation");
        if ($anni instanceof Plugin && $anni->isEnabled()) {
            $this->plugin->miniGame = "annihilation";
            $this->plugin->getServer()->getNetwork()->setName("§bATGames §9§lAnnihilation");
        }
    }
}

class MessageTask extends Task{

    public static $messages = ["§fNejlepsi minihry na tomto serveru!",
        "§fPotrebujes pomoc? Zeptej se nas na Facebooku: §9facebook.com/ATGamesServer",
        "§fKup si VIP §9bit.ly/atBUY", "§fChces hrat s kamaradama? Rekni jim stejnou IP a port",
        "§fNas web je §9atgames.tk"
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
