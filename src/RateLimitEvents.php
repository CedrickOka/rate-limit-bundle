<?php
namespace Oka\RateLimitBundle;

/**
 *
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 *
 */
final class RateLimitEvents
{
	/**
	 * The LIMIT_ATTEMPTS_UPDATED will be emitted when a request for a rate limited endpoint is detected.
	 * 
	 * @Event("Oka\RateLimitBundle\Event\RateLimitAttemptsUpdatedEvent")
	 */
	const RATE_LIMIT_ATTEMPTS_UPDATED = 'oka_rate_limit.rate_limit_attempts_updated';
	
	/**
	 * The LIMIT_EXCEEDED will be emitted when a endpoint limit is exceeded.
	 * 
	 * @Event("Oka\RateLimitBundle\Event\RateLimitExceededEvent")
	 */
	const RATE_LIMIT_EXCEEDED = 'oka_rate_limit.rate_limit_exceeded';
	
	/**
	 * The LIMIT_RESET_ATTEMPTS event can be used to reset the state for a specific endpoint (e.g. after a successful login).
	 * 
	 * @Event("Oka\RateLimitBundle\Event\RateLimitResetAttemptsEvent")
	 */
	const RATE_LIMIT_RESET_ATTEMPTS = 'oka_rate_limit.rate_limit_reset_attempts';
}
