<?php
namespace Oka\RateLimitBundle\Util;

use Oka\RateLimitBundle\Model\RateLimitConfigInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 *
 */
class RateLimitUtil
{
    public static function match(Request $request, RateLimitConfigInterface $config) :bool
    {
        if ($config->getMethod() && false === $request->isMethod($config->getMethod())) {
            return false;
        }
        
        if ($config->getPath() && !preg_match(sprintf('#%s#', strtr($config->getPath(), '#', '\#')), $request->getPathInfo())) {
            return false;
        }
        
        return true;
    }
    
    public static function createCacheItemKey(RateLimitConfigInterface $config, string $clientIp = null, string $account = null) :string
    {
        $key = sprintf('%s.%s.%s.%s', $config->getMethod() ?? '', $config->getPath() ?? '', $clientIp ?? '', $account ?? '');
        $key = strtr($key, [
                '{' => '_', '}' => '_',
                '(' => '_', ')' => '_',
                '/' => '_', '\\\\' => '_',
                '@' => '_', ':' => '_'
        ]);
        
        return md5(strtolower($key));
    }
    
    public static function datetime(string $time = null, string $timezone = 'UTC') :\DateTime
    {
        return new \DateTime($time, new \DateTimeZone($timezone));
    }
}
