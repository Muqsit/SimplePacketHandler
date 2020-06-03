# SimplePacketHandler
Put an end to the if-elseif instanceof hell.

## API Documentation
### Packet Monitor
Monitor data packets - use this if you aren't modifying the outcome of the event.<br>
Packet monitor registers DataPacket(Receive/Send)Event(s) at `MONITOR` priority.

Use case:
- Dumping extra data from packets
- Debugging whether a packet was sent / received

```php
/** @var Plugin $plugin */
$packet_monitor = SimplePacketHandler::createMonitor($plugin);

$packet_monitor->monitorIncoming(function(LoginPacket $packet, NetworkSession $origin) : void{
	$this->getLogger()->debug("Received LoginPacket from #" . spl_object_id($origin));
});

$packet_monitor->monitorIncoming(function(ServerSettingsResponsePacket $packet, NetworkSession $origin) : void{
	$this->getLogger()->debug("Received server settings response from {$origin->getPlayer()->getName()}");
});

$packet_monitor->monitorOutgoing(function(SetActorDataPacket $packet, NetworkSession $target) : void{
	$this->getLogger()->debug("Sent SetActorDataPacket to #" . spl_object_id($target));
});
```

### Packet Interceptor
Handle data packets - DataPacket(Receive/Send)Event(s) are registered at < `MONITOR` priority.

Use case:
- Blocking pocketmine from handling specific data packets
- Modifying data packets before pocketmine handles them

```php
/** @var Plugin $plugin */
$packet_interceptor = SimplePacketHandler::createInterceptor($plugin);

$packet_interceptor->interceptIncoming(function(AdventureSettingsPacket $packet, NetworkSession $origin) : bool{
	if($packet->getFlag(AdventureSettingsPacket::FLYING)){
		return false; // cancels the DataPacketReceiveEvent
	}
	return true; // do nothing
});

$packet_interceptor->interceptOutgoing(function(SetTimePacket $packet, NetworkSession $target) : bool{
	$custom_player = CustomPlayerManager::get($target->getPlayer());
	if($custom_player->getPTime() !== $packet->time){
		$target->sendDataPacket(SetTimePacket::create($custom_player->getPTime()));
		return false;
	}
	return true;
});
```
