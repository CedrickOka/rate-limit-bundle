<?php
namespace Oka\RateLimitBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder('oka_rate_limit');
		
		if (true === method_exists($treeBuilder, 'getRootNode')) {
			/** @var \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $rootNode */
			$rootNode = $treeBuilder->getRootNode();
		} else {
			// BC layer for symfony/config 4.1 and older
			/** @var \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $rootNode */
			$rootNode = $treeBuilder->root('oka_rate_limit');
		}
		
		$rootNode
				->addDefaultsIfNotSet()
				->children()
					->scalarNode('cache_pool_id')
						->defaultValue('cache.app')
					->end()
					
					->scalarNode('time_zone')
						->defaultValue('UTC')
					->end()
					
					->arrayNode('account_blacklist')
						->performNoDeepMerging()
						->prototype('scalar')->end()
					->end()
					
					->arrayNode('account_whitelist')
						->performNoDeepMerging()
						->prototype('scalar')->end()
					->end()
					
					->arrayNode('client_ip_blacklist')
						->performNoDeepMerging()
						->prototype('scalar')->end()
					->end()
					
					->arrayNode('client_ip_whitelist')
						->performNoDeepMerging()
						->prototype('scalar')->end()
					->end()
					
					->arrayNode('configs')
						->requiresAtLeastOneElement()
						->useAttributeAsKey('name')
						->prototype('array')
							->children()
								->scalarNode('path')
									->defaultNull()
									->info('A regular expression. The limit is applied to all URIs that match the regular expression and HTTP method.')
								->end()
								
								->scalarNode('method')
									->defaultNull()
									->info('The HTTP method used in the API call, typically one of GET, PUT, POST, or DELETE')
								->end()
								
								->integerNode('limit')
									->defaultValue(20)
									->info('A limit value that specifies the maximum count of units before the limit takes effect.')
								->end()
								
								->integerNode('interval')
									->defaultValue(60)
									->info('An interval that specifies time frame to which the limit is applied. The interval unit is the second.')
								->end()
								
								->integerNode('max_sleep_time')
									->defaultValue(60)
									->info('App will immediately return a 498 response if the necessary sleep time ever exceeds the given time in seconds.')
								->end()
								
								->arrayNode('account_blacklist')
									->performNoDeepMerging()
									->prototype('scalar')->end()
								->end()
								
								->arrayNode('account_whitelist')
									->performNoDeepMerging()
									->prototype('scalar')->end()
								->end()
								
								->arrayNode('client_ip_blacklist')
									->performNoDeepMerging()
									->prototype('scalar')->end()
								->end()
								
								->arrayNode('client_ip_whitelist')
									->performNoDeepMerging()
									->prototype('scalar')->end()
								->end()
							->end()
						->end()
					->end()
				->end();
		
		return $treeBuilder;
	}
}
