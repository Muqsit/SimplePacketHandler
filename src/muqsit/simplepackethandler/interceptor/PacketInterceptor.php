<?php

declare(strict_types=1);

namespace muqsit\simplepackethandler\interceptor;

use Closure;
use pocketmine\plugin\Plugin;

final class PacketInterceptor implements IPacketInterceptor{

	/** @var PacketInterceptorListener */
	private $listener;

	public function __construct(Plugin $register, int $priority, bool $handleCancelled){
		$this->listener = new PacketInterceptorListener($register, $priority, $handleCancelled);
	}

	public function interceptIncoming(Closure $handler) : IPacketInterceptor{
		$this->listener->interceptIncoming($handler);
		return $this;
	}

	public function interceptOutgoing(Closure $handler) : IPacketInterceptor{
		$this->listener->interceptOutgoing($handler);
		return $this;
	}
}