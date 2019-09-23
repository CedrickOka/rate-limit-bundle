<?php
namespace Oka\RateLimitBundle\Events;

use Oka\RateLimitBundle\Model\RateLimitConfigInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 *
 */
class RateLimitAttemptsUpdatedEvent extends RateLimitEvent
{
	/**
	 * @var int $remaining
	 */
	protected $remaining;
	
	/**
	 * @var \DateTime $reset
	 */
	protected $reset;
	
	/**
	 * @var array $headers
	 */
	protected $headers;
	
	/**
	 * @param Request $request 					The current request
	 * @param RateLimitConfigInterface $config 	The rate limit config for the current request
	 * @param int $remaining 					The number of requests left for the 15 minute window
	 * @param \DateTime $reset					The remaining window before the rate limit resets, in UTC epoch seconds
	 * @param array $headers					The response headers
	 */
	public function __construct(Request $request, RateLimitConfigInterface $config, int $remaining, \DateTime $reset, array $headers = [])
	{
		parent::__construct($request, $config);
		
		$this->remaining = $remaining;
		$this->reset = $reset;
		$this->headers = $headers;
	}
	
	public function getRemaining() :int
	{
		return $this->remaining;
	}
	
	public function getReset() :\DateTime
	{
		return $this->reset;
	}
	
	public function getHeaders() :array
	{
		return $this->headers;
	}
}
