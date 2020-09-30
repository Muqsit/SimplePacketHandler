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

The `LoginPacket` example above is the equivalent of:
```php
/**
 * @param DataPacketReceiveEvent $event
 * @priority MONITOR
 */
public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
	$packet = $event->getPacket();
	if($packet instanceof LoginPacket){
		$origin = $event->getOrigin();
		$this->getLogger()->debug("Received LoginPacket from #" . spl_object_id($origin));
	}
}
```

The `SetActorDataPacket` example above is the equivalent of:
```php
/**
 * @param DataPacketSendEvent $event
 * @priority MONITOR
 */
public function onDataPacketSend(DataPacketSendEvent $event) : void{
	foreach($event->getPackets() as $packet){
		if($packet instanceof SetActorDataPacket){
			foreach($event->getTargets() as $target){
				$this->getLogger()->debug("Sent SetActorDataPacket to #" . spl_object_id($target));
			}
		}
	}
}
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

The `AdventureSettingsPacket` example above is the equivalent of:
```php
/**
 * @param DataPacketReceiveEvent $event
 * @priority NORMAL
 */
public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
	$packet = $event->getPacket();
	if($packet instanceof AdventureSettingsPacket){
		$origin = $event->getOrigin();
		if($packet->getFlag(AdventureSettingsPacket::FLYING)){
			$event->cancel();
		}
	}
}
```

The `SetTimePacket` example above is the equivalent of:
```php
/**
 * @param DataPacketSendEvent $event
 * @priority NORMAL
 */
public function onDataPacketSend(DataPacketSendEvent $event) : void{
	foreach($event->getPackets() as $packet){
		if($packet instanceof SetTimePacket){
			$targets = $event->getTargets();
			$new_targets = $targets;
			foreach($new_targets as $index => $target){
				$custom_player = CustomPlayerManager::get($target->getPlayer());
				if($custom_player->getPTime() !== $packet->time){
					$target->sendDataPacket(SetTimePacket::create($custom_player->getPTime()));
					unset($new_targets[$index]);
				}
			}

			if(count($new_targets) !== count($targets)){
				// Cancel the event, try sending the remaining targets the
				// batch of packets again.
				$event->cancel();
				if(count($new_targets > 0){
					$new_target_players = [];
					foreach($new_targets as $new_target){
						$new_target_players[] = $new_target->getPlayer();
					}
					$this->getServer()->broadcastPackets($new_target_players, $event->getPackets());
				}
				break;
			}
		}
	}
}
```
