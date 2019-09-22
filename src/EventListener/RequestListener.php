<?php
namespace Oka\RateLimitBundle\EventListener;

use Oka\RESTRequestValidatorBundle\Service\ErrorResponseFactory;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
	 * @var array $rateLimits
	 */
	protected $rateLimits;
	
	/**
	 * @var string $timezone
	 */
	protected $timezone;
	
	/**
	 * @param TokenStorageInterface $tokenStorage
	 * @param CacheItemPoolInterface $cachePool
	 * @param array $rateLimits
	 */
	public function __construct(TokenStorageInterface $tokenStorage, TranslatorInterface $translator, ErrorResponseFactory $errorResponseFactory, CacheItemPoolInterface $cachePool, array $rateLimits = [], string $timezone = 'UTC')
	{
		$this->tokenStorage = $tokenStorage;
		$this->translator = $translator;
		$this->errorResponseFactory = $errorResponseFactory;
		$this->cachePool = $cachePool;
		$this->rateLimits = $rateLimits;
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
		
		if ($token = $this->tokenStorage->getToken()) {
			$account = $token->getUsername();
		}
		
		foreach ($this->rateLimits as $rateLimit) {
			if (false === $this->match($request, $rateLimit)) {
				continue;
			}
			
			$cacheItemKey = $this->createCacheItemKey($rateLimit, $clientIp, $account);
			
			if (true === $this->cachePool->getItem($cacheItemKey . '.exceeded')->isHit()) {
				$event->setResponse($this->createRateLimitExceededResponse());
				return;
			}
			
			if ($account) {
				if (true === in_array($account, $rateLimit['account_whitelist'])) {
					$headers = ['X-Rate-Limit-Account' => 'Whitelist'];
					break;
				}
				if (true === in_array($account, $rateLimit['account_blacklist'])) {
					$event->setResponse($this->createRateLimitExceededResponse(['X-Rate-Limit-Account' => 'Blacklist']));
					return;
				}
			}
			
			if (true === in_array($clientIp, $rateLimit['client_ip_whitelist'])) {
				$headers = ['X-Rate-Limit-Client-Ip' => 'Whitelist'];
				break;
			}
			if (true === in_array($clientIp, $rateLimit['client_ip_blacklist'])) {
				$event->setResponse($this->createRateLimitExceededResponse(['X-Rate-Limit-Client-Ip' => 'Blacklist']));
				return;
			}
			
			$now = new \DateTime(null, new \DateTimeZone($this->timezone));
			$cacheItem = $this->cachePool->getItem($cacheItemKey);
			
			if (true === $cacheItem->isHit()) {
				$value = $cacheItem->get();
				++$value['count'];
			} else {
				$value = ['count' => 1, 'reset' => $now->getTimestamp() + $rateLimit['interval']];
				
				if ($cacheItem instanceof CacheItem) {
					$cacheItem->tag('oka_rate_limit');
				}
				
				$cacheItem->expiresAt(new \DateTime('@' . $value['reset'], new \DateTimeZone($this->timezone)));
			}
			
			$remaining = $rateLimit['limit'] - $value['count'];
			
			if (($now->getTimestamp() <= $value['reset'] && $remaining < 0)) {
				if ($rateLimit['max_sleep_time'] > 0) {
					$cacheItem = $this->cachePool->getItem($cacheItemKey . '.exceeded');
					$cacheItem->expiresAfter($rateLimit['max_sleep_time']);
					$cacheItem->set(true);
					
					if ($cacheItem instanceof CacheItem) {
						$cacheItem->tag('oka_rate_limit');
					}
					
					$this->cachePool->save($cacheItem);
				}
				
				$event->setResponse($this->createRateLimitExceededResponse());
				return;
			}
			
			$cacheItem->set($value);
			$this->cachePool->save($cacheItem);
			
			$headers = [
					'X-Rate-Limit-Limit' => $rateLimit['limit'],
					'X-Rate-Limit-Remaining' => $remaining,
					'X-Rate-Limit-Reset' => $value['reset']
			];
			break;
		}
		
		if (false === empty($headers)) {
			$this->addListenerKernelResponse($dispatcher, $headers);
		}
	}
	
	/**
	 * @param Request $request
	 * @param array $rateLimit
	 * @return boolean
	 */
	private function match(Request $request, array $rateLimit) :bool
	{
		if (true === isset($rateLimit['method']) && false === $request->isMethod($rateLimit['method'])) {
			return false;
		}
		
		if (true === isset($rateLimit['path']) && !preg_match(sprintf('#%s#', strtr($rateLimit['path'], '#', '\#')), $request->getPathInfo())) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * @param array $rateLimit
	 * @param string $clientIp
	 * @param string $account
	 * @return string
	 */
	private function createCacheItemKey(array $rateLimit, string $clientIp = null, string $account = null) :string
	{
		$key = sprintf('%s.%s.%s.%s', $rateLimit['method'] ?? '', $rateLimit['path'] ?? '', $clientIp ?? '', $account ?? '');
		$key = strtr($key, [
				'{' => '_', '}' => '_', 
				'(' => '_', ')' => '_', 
				'/' => '_', '\\\\' => '_', 
				'@' => '_', ':' => '_'
		]);
		
		return md5(strtolower($key));
	}
	
	/**
	 * @param array $headers
	 * @return Response
	 */
	private function createRateLimitExceededResponse(array $headers = []) :Response
	{
		return $this->errorResponseFactory->create($this->translator->trans('Rate limit exceeded', [], 'oka_rate_limit'), 429, null, [], 429, $headers);
	}
	
	/**
	 * @param EventDispatcherInterface $dispatcher
	 * @param array $headers
	 */
	private function addListenerKernelResponse(EventDispatcherInterface $dispatcher, array $headers)
	{
		$dispatcher->addListener(KernelEvents::RESPONSE, function(FilterResponseEvent $event) use ($headers){
			if (false === $event->isMasterRequest()) {
				return;
			}
			
			$response = $event->getResponse();
			
			foreach ($headers as $key => $value) {
				$response->headers->set($key, $value);
			}
		});
	}
}
