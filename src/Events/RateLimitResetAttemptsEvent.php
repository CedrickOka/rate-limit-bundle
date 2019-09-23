<?php
namespace Oka\RateLimitBundle\Events;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

/**
 * 
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 * 
 */
class RateLimitResetAttemptsEvent extends Event
{
	/**
	 * @var Request $request
	 */
	protected $request;
	
	public function __construct(Request $request)
	{
		$this->request = $request;
	}
	
	public function getRequest() :Request
	{
		return $this->request;
	}
}
