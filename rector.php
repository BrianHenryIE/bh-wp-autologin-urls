<?php
/**
 * Rector rules to automatically refactor code to modern syntax.
 *
 * @package brianhenryie/bh-wp-autologin-urls
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;

return RectorConfig::configure()
	->withPaths(
		array(
			__DIR__ . '/src',
			__DIR__ . '/tests/integration',
			__DIR__ . '/tests/unit',
			__DIR__ . '/tests/wpunit',
		)
	)
	->withSkip(
		array(
			__DIR__ . '/vendor',
			__DIR__ . '/vendor-prefixed',

			LongArrayToShortArrayRector::class, // WPCS says to use long array syntax.
			ArrayToFirstClassCallableRector::class, // I don't know how to test the new syntax with `WP_Mock::expectActionAdded()`.
			// Promoting properties moves their docblocks inside the constructor's parameter list,
			// which phpcbf cannot then format to the WordPress standard.
			ClassPropertyAssignToConstructorPromotionRector::class,
			// Arrow functions cannot contain the multi-line bodies WPCS formats these closures into.
			ClosureToArrowFunctionRector::class,
		)
	)
	->withPhpSets(
		php81: true,
	)
	->withPreparedSets(
		deadCode: false,
		codeQuality: false,
		codingStyle: false,
		typeDeclarations: false,
		privatization: false,
		naming: false,
		instanceOf: false,
		earlyReturn: false,
		strictBooleans: false,
	);
