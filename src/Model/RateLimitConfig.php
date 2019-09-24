<?php
namespace Oka\RateLimitBundle\Model;

/**
 *
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 *
 */
class RateLimitConfig implements RateLimitConfigInterface
{
	/**
	 * @var string $method
	 */
	protected $method;
	
	/**
	 * @var string $path
	 */
	protected $path;
	
	/**
	 * @var int $limit
	 */
	protected $limit;
	
	/**
	 * @var int $interval
	 */
	protected $interval;
	
	/**
	 * @var int $maxSleepTime
	 */
	protected $maxSleepTime;
	
	/**
	 * @var array $accountBlacklist
	 */
	protected $accountBlacklist;
	
	/**
	 * @var array $accountWhitelist
	 */
	protected $accountWhitelist;
	
	/**
	 * @var array $clientIpBlacklist
	 */
	protected $clientIpBlacklist;
	
	/**
	 * @var array $clientIpWhitelist
	 */
	protected $clientIpWhitelist;
	
	public function __construct(int $limit, int $interval, int $maxSleepTime, string $method = null, string $path = null, array $accountBlacklist = [], array $accountWhitelist = [], array $clientIpBlacklist = [], array $clientIpWhitelist = [])
	{
		$this->method = $method;
		$this->path = $path;
		$this->limit = $limit;
		$this->interval = $interval;
		$this->maxSleepTime = $maxSleepTime;
		$this->accountBlacklist = $accountBlacklist;
		$this->accountWhitelist = $accountWhitelist;
		$this->clientIpBlacklist = $clientIpBlacklist;
		$this->clientIpWhitelist = $clientIpWhitelist;
	}
	
	public function getMethod() :?string
	{
		return $this->method;
	}
	
	public function setMethod(string $method) :self
	{
		$this->method = $method;
		return $this;
	}
	
	public function getPath() :?string
	{
		return $this->path;
	}
	
	public function setPath(string $path) :self
	{
		$this->path = $path;
		return $this;
	}
	
	public function getLimit() :int
	{
		return $this->limit;
	}
	
	public function setLimit(int $limit) :self
	{
		$this->limit = $limit;
		return $this;
	}
	
	public function getInterval() :int
	{
		return $this->interval;
	}
	
	public function setInterval(int $interval) :self
	{
		$this->interval = $interval;
		return $this;
	}
	
	public function getMaxSleepTime() :int
	{
		return $this->maxSleepTime;
	}
	
	public function setMaxSleepTime(int $maxSleepTime) :self
	{
		$this->maxSleepTime = $maxSleepTime;
		return $this;
	}
	
	public function getAccountBlacklist() :array
	{
		return $this->accountBlacklist;
	}
	
	public function setAccountBlacklist(array $accountBlacklist) :self
	{
		$this->accountBlacklist = $accountBlacklist;
		return $this;
	}
	
	public function getAccountWhitelist() :array
	{
		return $this->accountWhitelist;
	}
	
	public function setAccountWhitelist(array $accountWhitelist) :self
	{
		$this->accountWhitelist = $accountWhitelist;
		return $this;
	}
	
	public function getClientIpBlacklist() :array
	{
		return $this->clientIpBlacklist;
	}
	
	public function setClientIpBlacklist(array $clientIpBlacklist) :self
	{
		$this->clientIpBlacklist = $clientIpBlacklist;
		return $this;
	}
	
	public function getClientIpWhitelist() :array
	{
		return $this->clientIpWhitelist;
	}
	
	public function setClientIpWhitelist(array $clientIpWhitelist) :self
	{
		$this->clientIpWhitelist = $clientIpWhitelist;
		return $this;
	}
	
	public static function fromNodeConfig(array $config) :self
	{
		if (false === isset($config['limit'])
			|| false === isset($config['interval']) 
			|| false === isset($config['max_sleep_time'])
			|| false === isset($config['account_blacklist'])
			|| false === isset($config['account_whitelist'])
			|| false === isset($config['client_ip_blacklist'])
			|| false === isset($config['client_ip_whitelist'])) {
			throw new \InvalidArgumentException('The following configuration are required "limit, interval, max_sleep_time, account_blacklist, account_whitelist, client_ip_blacklist, client_ip_whitelist".');
		}
		
		return new self(
				$config['limit'], 
				$config['interval'], 
				$config['max_sleep_time'], 
				$config['method'], 
				$config['path'], 
				$config['account_blacklist'], 
				$config['account_whitelist'], 
				$config['client_ip_blacklist'], 
				$config['client_ip_whitelist']
		);
	}
}
