<?php
namespace Oka\RateLimitBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class OkaRateLimitExtension extends Extension
{
	/**
	 * {@inheritdoc}
	 */
	public function load(array $configs, ContainerBuilder $container)
	{
		$configuration = new Configuration();
		$config = $this->processConfiguration($configuration, $configs);
		
		$loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
		$loader->load('services.yml');
		
		$cachePoolReference = new Reference($config['cache_pool_id']);
		
		$requestListener = $container->getDefinition('oka_rate_limit.request.event_listener');
		$requestListener->replaceArgument(3, $cachePoolReference);
		$requestListener->replaceArgument(4, $config['configs']);
		$requestListener->replaceArgument(5, $config['time_zone']);
	}
}
