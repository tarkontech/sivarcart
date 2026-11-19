<?php

declare(strict_types=1);

arch()->preset()->php();

arch()->preset()->security();

arch('it will not use dd(), ddd(), env(), or exit()')
    ->expect(['dd', 'ddd', 'env', 'exit'])
    ->each->not->toBeUsed();

arch('the package source declares strict types')
    ->expect('SivarcartCore\SivarcartCore')
    ->toUseStrictTypes();

arch('the models never read the environment directly')
    ->expect('SivarcartCore\SivarcartCore\Models')
    ->not->toUse(['env', 'getenv']);

arch('the migrations never read the environment directly')
    ->expect('SivarcartCore\SivarcartCore')
    ->not->toUse(['env', 'getenv']);
