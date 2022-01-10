<?php

declare(strict_types=1);

namespace muqsit\simplepackethandler\utils;

use Closure;
use InvalidArgumentException;
use pocketmine\event\HandlerListManager;
use ReflectionFunction;
use ReflectionNamedType;

final class Utils{

	/**
	 * @param Closure $closure
	 * @param string[] $params
	 * @param string $return_type
	 * @return string[]
	 */
	public static function parseClosureSignature(Closure $closure, array $params, string $return_type) : array{
		/** @noinspection PhpUnhandledExceptionInspection */
		$method = new ReflectionFunction($closure);
		$type = $method->getReturnType();
		if(!($type instanceof ReflectionNamedType) || $type->allowsNull() || $type->getName() !== $return_type){
			throw new InvalidArgumentException("Return value of {$method->getName()} must be {$return_type}");
		}

		$parsed_params = [];
		$parameters = $method->getParameters();
		if(count($parameters) === count($params)){
			$parameter_index = 0;
			foreach($parameters as $parameter){
				$parameter_type = $parameter->getType();
				$parameter_compare = $params[$parameter_index++];
				if($parameter_type instanceof ReflectionNamedType && !$parameter_type->allowsNull() && is_a($parameter_type->getName(), $parameter_compare, true)){
					$parsed_params[] = $parameter_type->getName();
					continue;
				}
				break;
			}

			if(count($parsed_params) === count($params)){
				return $parsed_params;
			}
		}

		throw new InvalidArgumentException("Closure must satisfy signature (" . implode(", ", $params) . ") : {$return_type}");
	}

	/**
	 * @param string $event
	 * @param Closure $handler
	 * @param int $priority
	 * @return bool
	 *
	 * @phpstan-template TEvent of \pocketmine\event\Event
	 * @phpstan-param class-string<TEvent> $event
	 * @phpstan-param Closure(TEvent) : void $handler
	 */
	public static function unregisterEventByHandler(string $event, Closure $handler, int $priority) : bool{
		$list = HandlerListManager::global()->getListFor($event);
		foreach($list->getListenersByPriority($priority) as $listener){
			if($listener->getHandler() === $handler){
				$list->unregister($listener);
				return true;
			}
		}
		return false;
	}
}