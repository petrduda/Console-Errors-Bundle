<?php

namespace VasekPurchart\ConsoleErrorsBundle\DependencyInjection;

use Psr\Log\LogLevel;

use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration implements \Symfony\Component\Config\Definition\ConfigurationInterface
{

	const DEFAULT_ERROR_LISTENER_PRIORITY = 0;
	const DEFAULT_ERROR_LOG_LEVEL = LogLevel::ERROR;
	const DEFAULT_EXCEPTION_LISTENER_PRIORITY = 0;

	const PARAMETER_ERROR_ENABLED = 'enabled';
	const PARAMETER_ERROR_LISTENER_PRIORITY = 'listener_priority';
	const PARAMETER_ERROR_LOG_LEVEL = 'log_level';
	const PARAMETER_EXCEPTION_ENABLED = 'enabled';
	const PARAMETER_EXCEPTION_LISTENER_PRIORITY = 'listener_priority';

	const SECTION_ERRORS = 'errors';
	const SECTION_EXCEPTIONS = 'exceptions';

	/** @var string */
	private $rootNode;

	/**
	 * @param string $rootNode
	 */
	public function __construct($rootNode)
	{
		$this->rootNode = $rootNode;
	}

	/**
	 * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder
	 */
	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder();
		$rootNode = $treeBuilder->root($this->rootNode);

		$rootNode
			->children()
				->arrayNode(self::SECTION_EXCEPTIONS)
					->addDefaultsIfNotSet()
					->children()
						->scalarNode(self::PARAMETER_EXCEPTION_ENABLED)
							->info('Enable logging for exceptions.')
							->defaultTrue()
							->end()
						->integerNode(self::PARAMETER_EXCEPTION_LISTENER_PRIORITY)
							->info('Priority with which the listener will be registered.')
							->defaultValue(self::DEFAULT_EXCEPTION_LISTENER_PRIORITY)
							->end()
						->end()
					->end()
				->arrayNode(self::SECTION_ERRORS)
					->addDefaultsIfNotSet()
					->children()
						->scalarNode(self::PARAMETER_ERROR_ENABLED)
							->info('Enable logging for errors (non zero exit codes).')
							->defaultTrue()
							->end()
						->append($this->createLogLevelNode(
							self::PARAMETER_ERROR_LOG_LEVEL,
							'Log level with which errors should be logged (accepts string or integer values).',
							self::DEFAULT_ERROR_LOG_LEVEL
						))
						->integerNode(self::PARAMETER_ERROR_LISTENER_PRIORITY)
							->info('Priority with which the listener will be registered.')
							->defaultValue(self::DEFAULT_ERROR_LISTENER_PRIORITY)
							->end()
						->end()
					->end()
				->end()
			->end();

		return $treeBuilder;
	}

	/**
	 * @param string $parameterName
	 * @param string$parameterInfo
	 * @param string|integer $defaultValue
	 * @return \Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition
	 */
	private function createLogLevelNode($parameterName, $parameterInfo, $defaultValue)
	{
		$logLevelNode = new ScalarNodeDefinition($parameterName);
		$logLevelNode
			->info($parameterInfo)
			->defaultValue($defaultValue)
			->beforeNormalization()
				->ifString()
				->then(function ($value) {
					return strtolower($value);
				})
				->end()
			->validate()
				->ifTrue(function ($value) {
					switch (true) {
						case is_int($value):
							return false;
						case is_string($value) && defined(LogLevel::class . '::' . strtoupper($value)):
							return false;
						default:
							return true;
					}
				})
				->thenInvalid(sprintf(
					'Invalid log level value "%%s". Must be either value from %s or an integer.',
					LogLevel::class
				))
				->end()
			->end();

		return $logLevelNode;
	}

}
