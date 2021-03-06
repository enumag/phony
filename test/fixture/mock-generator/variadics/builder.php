<?php

$builder = $factory->create(
    [
        'methodA' => function ($a, $b, ...$c) {},
        'methodB' => function ($a, $b, stdClass ...$c) {},
        'methodC' => function ($a, $b, &...$c) {},
    ]
);

return $builder->named('Phony\Test\MockGeneratorVariadics');
