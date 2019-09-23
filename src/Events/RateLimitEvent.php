<?php
namespace Oka\RateLimitBundle\Events;

use Oka\RateLimitBundle\Model\RateLimitConfigInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;


/**
 *
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 *
 */
class RateLimitEvent extends Event
{
	/**
	 * @var Request $request
	 */
	protected $request;
	
	/**
	 * @var RateLimitConfigInterface $config
	 */
	protected $config;
	
	/**
	 * @param Request $request 					The current request
	 * @param RateLimitConfigInterface $config 	The rate limit config for the current request
	 */
	public function __construct(Request $request, RateLimitConfigInterface $config)
	{
		$this->request = $request;
		$this->config = $config;
	}
	
	public function getRequest() :Request
	{
		return $this->request;
	}
	
	public function getConfig() :RateLimitConfigInterface
	{
		return $this->config;
	}
}
