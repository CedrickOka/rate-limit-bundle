<?php
namespace Oka\RateLimitBundle\EventListener;

use Oka\RESTRequestValidatorBundle\Service\ErrorResponseFactory;
use Oka\RateLimitBundle\RateLimitEvents;
use Oka\RateLimitBundle\Events\RateLimitAttemptsUpdatedEvent;
use Oka\RateLimitBundle\Events\RateLimitExceededEvent;
use Oka\RateLimitBundle\Model\RateLimitConfig;
use Oka\RateLimitBundle\Model\RateLimitConfigInterface;
use Oka\RateLimitBundle\Util\RateLimitUtil;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * 
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 * 
 */
class RequestListener
{
	/**
	 * @var TokenStorageInterface $tokenStorage
	 */
	protected $tokenStorage;
	
	/**
	 * @var TranslatorInterface $translator
	 */
	protected $translator;
	
	/**
	 * @var ErrorResponseFactory $errorResponseFactory
	 */
	protected $errorResponseFactory;
	
	/**
	 * @var CacheItemPoolInterface $cachePool
	 */
	protected $cachePool;
	
	/**
	 * @var array $configs
	 */
	protected $configs;
	
	/**
	 * @var string $timezone
	 */
	protected $timezone;
	
	/**
	 * @param TokenStorageInterface $tokenStorage
	 * @param TranslatorInterface $translator
	 * @param ErrorResponseFactory $errorResponseFactory
	 * @param CacheItemPoolInterface $cachePool
	 * @param array $configs
	 * @param string $timezone
	 */
	public function __construct(TokenStorageInterface $tokenStorage, TranslatorInterface $translator, ErrorResponseFactory $errorResponseFactory, CacheItemPoolInterface $cachePool, array $configs = [], string $timezone = 'UTC')
	{
		$this->tokenStorage = $tokenStorage;
		$this->translator = $translator;
		$this->errorResponseFactory = $errorResponseFactory;
		$this->cachePool = $cachePool;
		$this->configs = $configs;
		$this->timezone = $timezone;
	}
	
	/**
	 * @param GetResponseEvent $event
	 */
	public function onKernelRequest(GetResponseEvent $event, $eventName, EventDispatcherInterface $dispatcher)
	{
		if (false === $event->isMasterRequest()) {
			return;
		}
		
		$headers = [];
		$account = null;
		$request = $event->getRequest();
		$clientIp = $request->getClientIp();
		$now = RateLimitUtil::datetime(null, $this->timezone);
		
		if ($token = $this->tokenStorage->getToken()) {
			$account = $token->getUsername();
		}
		
		foreach ($this->configs as $config) {
			$config = RateLimitConfig::fromNodeConfig($config);
			
			if (false === RateLimitUtil::match($request, $config)) {
				continue;
			}
			
			$cacheItemKey = RateLimitUtil::createCacheItemKey($config, $clientIp, $account);
			$rateLimitExceededCacheItem = $this->cachePool->getItem($cacheItemKey . '.rate_limit_exceeded');
			
			if (true === $rateLimitExceededCacheItem->isHit()) {
				$this->handleRateLimitExceeded($event, $dispatcher, $config, ['X-Rate-Limit-Max-Sleep-Reset' => $rateLimitExceededCacheItem->get()]);
				return;
			}
			
			if ($account) {
				if (true === in_array($account, $config->getAccountWhitelist())) {
					$headers = ['X-Rate-Limit-Account' => 'Whitelist'];
					break;
				}
				if (true === in_array($account, $config->getAccountBlacklist())) {
					$this->handleRateLimitExceeded($event, $dispatcher, $config, ['X-Rate-Limit-Account' => 'Blacklist']);
					return;
				}
			}
			
			if (true === in_array($clientIp, $config->getClientIpWhitelist())) {
				$headers = ['X-Rate-Limit-Client-Ip' => 'Whitelist'];
				break;
			}
			if (true === in_array($clientIp, $config->getClientIpBlacklist())) {
				$this->handleRateLimitExceeded($event, $dispatcher, $config, ['X-Rate-Limit-Client-Ip' => 'Blacklist']);
				return;
			}
			
			$cacheItem = $this->cachePool->getItem($cacheItemKey);
			
			if (true === $cacheItem->isHit()) {
				$value = $cacheItem->get();
				$reset = RateLimitUtil::datetime('@' . $value['reset'], $this->timezone);
				++$value['count'];
			} else {
				$reset = RateLimitUtil::datetime('@' . ($now->getTimestamp() + $config->getInterval()), $this->timezone);
				$value = ['count' => 1, 'reset' => $reset->getTimestamp()];
				
				if ($cacheItem instanceof CacheItem) {
					$cacheItem->tag('oka_rate_limit');
				}
				
				$cacheItem->expiresAt($reset);
			}
			
			$remaining = $config->getLimit() - $value['count'];
			
			if (($now->getTimestamp() <= $value['reset'] && $remaining < 0)) {
				if ($config->getMaxSleepTime() > 0) {
					$maxSleepReset = RateLimitUtil::datetime('@' . ($now->getTimestamp() + $config->getMaxSleepTime()), $this->timezone);
					$rateLimitExceededCacheItem->set($maxSleepReset->format('c'));
					$rateLimitExceededCacheItem->expiresAt($maxSleepReset);
					
					if ($rateLimitExceededCacheItem instanceof CacheItem) {
						$rateLimitExceededCacheItem->tag('oka_rate_limit');
					}
					
					$this->cachePool->save($rateLimitExceededCacheItem);
					$headers = ['X-Rate-Limit-Max-Sleep-Reset' => $cacheItem->get()];
				}
				
				$this->handleRateLimitExceeded($event, $dispatcher, $config, $headers);
				return;
			}
			
			$cacheItem->set($value);
			$this->cachePool->save($cacheItem);
			
			$rateLimitAttemptsUpdatedEvent = new RateLimitAttemptsUpdatedEvent($request, $config, $remaining, $reset);
			$dispatcher->dispatch(RateLimitEvents::RATE_LIMIT_ATTEMPTS_UPDATED, $rateLimitAttemptsUpdatedEvent);
			
			if (!$headers = $rateLimitAttemptsUpdatedEvent->getHeaders()) {
				$headers = [
						'X-Rate-Limit-Limit' => $config->getLimit(),
						'X-Rate-Limit-Remaining' => $remaining,
						'X-Rate-Limit-Reset' => $reset->format('c')
				];
			}
			break;
		}
		
		if (false === empty($headers)) {
			$dispatcher->addListener(KernelEvents::RESPONSE, function(FilterResponseEvent $event) use ($headers){
				if (false === $event->isMasterRequest()) {
					return;
				}
				
				$response = $event->getResponse();
				
				foreach ($headers as $key => $value) {
					$response->headers->set($key, $value);
				}
			}, -255);
		}
	}
	
	/**
	 * @param GetResponseEvent $event
	 * @param EventDispatcherInterface $dispatcher
	 * @param RateLimitConfigInterface $config
	 * @param array $headers
	 */
	private function handleRateLimitExceeded(GetResponseEvent $event, EventDispatcherInterface $dispatcher, RateLimitConfigInterface $config, array $headers = [])
	{
		$rateLimitExceededEvent = new RateLimitExceededEvent($event->getRequest(), $config);
		$dispatcher->dispatch(RateLimitEvents::RATE_LIMIT_EXCEEDED, $rateLimitExceededEvent);
		
		if (!$response = $rateLimitExceededEvent->getResponse()) {
			$response = $this->errorResponseFactory->create($this->translator->trans('Rate limit exceeded', [], 'oka_rate_limit'), 429, null, [], 429, $headers);
		}
		
		$event->setResponse($response);
	}
}
