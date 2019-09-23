<?php
namespace Oka\RateLimitBundle\EventListener;

use Oka\RateLimitBundle\Events\RateLimitResetAttemptsEvent;
use Oka\RateLimitBundle\Model\RateLimitConfig;
use Oka\RateLimitBundle\Util\RateLimitUtil;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * 
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 * 
 */
class RateLimitListener
{
	/**
	 * @var TokenStorageInterface $tokenStorage
	 */
	protected $tokenStorage;
	
	/**
	 * @var CacheItemPoolInterface $cachePool
	 */
	protected $cachePool;
	
	/**
	 * @var array $configs
	 */
	protected $configs;
	
	/**
	 * @param TokenStorageInterface $tokenStorage
	 * @param CacheItemPoolInterface $cachePool
	 * @param array $configs
	 */
	public function __construct(TokenStorageInterface $tokenStorage, CacheItemPoolInterface $cachePool, array $configs = [])
	{
		$this->tokenStorage = $tokenStorage;
		$this->cachePool = $cachePool;
		$this->configs = $configs;
	}
	
	/**
	 * @param RateLimitResetAttemptsEvent $event
	 */
	public function onRateLimitResetAttempts(RateLimitResetAttemptsEvent $event)
	{
		$account = null;
		$request = $event->getRequest();
		$clientIp = $request->getClientIp();
		
		if ($token = $this->tokenStorage->getToken()) {
			$account = $token->getUsername();
		}
		
		foreach ($this->configs as $config) {
			$config = RateLimitConfig::fromNodeConfig($config);
			
			if (false === RateLimitUtil::match($request, $config)) {
				continue;
			}
			
			$cacheItemKey = RateLimitUtil::createCacheItemKey($config, $clientIp, $account);
			$this->cachePool->deleteItem($cacheItemKey . '.rate_limit_exceeded');
			$this->cachePool->deleteItem($cacheItemKey);
			
			break;
		}
	}
}
