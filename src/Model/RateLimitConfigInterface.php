<?php
namespace Oka\RateLimitBundle\Model;

/**
 *
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 *
 */
interface RateLimitConfigInterface
{
	public function getMethod() :?string;
	
	public function getPath() :?string;
	
	public function getLimit() :int;
	
	public function getInterval() :int;
	
	public function getMaxSleepTime() :int;
	
	public function getAccountBlacklist() :array;
	
	public function getAccountWhitelist() :array;
	
	public function getClientIpBlacklist() :array;
	
	public function getClientIpWhitelist() :array;
}
