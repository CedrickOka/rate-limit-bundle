<?php
namespace Oka\RateLimitBundle\Events;

use Oka\RateLimitBundle\Model\RateLimitConfigInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 *
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 *
 */
class RateLimitExceededEvent extends RateLimitEvent
{
	/**
	 * @var Response $response
	 */
	protected $response;
	
	/**
	 * @param Request $request 					The current request
	 * @param RateLimitConfigInterface $config 	The rate limit config for the current request
	 * @param Response $response 				The rate limit response which could be returned
	 */
	public function __construct(Request $request, RateLimitConfigInterface $config, Response $response = null)
	{
		parent::__construct($request, $config);
		
		$this->response = $response;
	}
	
	public function getResponse() :?Response
	{
		return $this->response;
	}
	
	public function setResponse(Response $response) :self
	{
		$this->response = $response;
		return $this;
	}
}
