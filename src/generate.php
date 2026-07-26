<?php

declare(strict_types=1);

namespace GenericsBenchmark;

const TAGS = 512;

const ROUNDS = 256;

/**
 * @var array<string, array{namespace: string, style: string}>
 */
const TARGETS = [
    'phpdoc-impl' => [
        'namespace' => 'GenericsBenchmark\\PHPDocImpl',
        'style' => 'erased',
    ],
    'holly-generics-impl' => [
        'namespace' => 'GenericsBenchmark\\HollyImpl',
        'style' => 'bare',
    ],
    'reified-generics-impl' => [
        'namespace' => 'GenericsBenchmark\\ReifiedImpl',
        'style' => 'turbofish',
    ],
];

function typeArguments(string $style, string ...$arguments): string
{
    return match ($style) {
        'erased' => '',
        'bare' => '<' . implode(', ', $arguments) . '>',
        'turbofish' => '::<' . implode(', ', $arguments) . '>',
    };
}

function generate(string $namespace, string $style): string
{
    $tags = '';
    $touches = '';
    $calls = '';

    for ($index = 0; $index < TAGS; $index++) {
        $tag = 'Tag' . $index;
        $cell = 'Cell' . typeArguments($style, $tag);
        $box = 'Box' . typeArguments($style, $tag);

        $tags .= sprintf("final class %s {}\n", $tag);
        $calls .= sprintf("        \$sum += self::touch%d();\n", $index);
        $touches .= sprintf(
            "\n    private static function touch%d(): int {\n"
            . "        \$cell = new %s(new %s());\n"
            . "        \$boxed = \$cell instanceof %s;\n\n"
            . "        return \$boxed ? \$cell->tally() : 0;\n"
            . "    }\n",
            $index,
            $cell,
            $tag,
            $box,
        );
    }

    $registryInt = 'Registry' . typeArguments($style, 'int');
    $registryString = 'Registry' . typeArguments($style, 'string');

    return <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$namespace};

        require __DIR__ . '/bootstrap.php';

        const ROUNDS = {ROUNDS_PLACEHOLDER};

        {$tags}
        final class Sites {
            /**
             * Touches every specialization once.
             */
            public static function all(): int {
                \$sum = 0;
        {$calls}
                return \$sum;
            }
        {$touches}}

        {$registryInt}::bump();
        {$registryInt}::bump();
        {$registryString}::bump();
        \$statics = statics({$registryInt}::\$made, {$registryString}::\$made);

        \$firstStart = hrtime(true);
        \$sink = Sites::all();
        \$firstPass = (hrtime(true) - \$firstStart) / 1_000_000_000;

        \$start = hrtime(true);
        for (\$round = 0; \$round < ROUNDS; \$round++) {
            \$sink += Sites::all();
        }

        \$elapsed = (hrtime(true) - \$start) / 1_000_000_000;

        report(\$sink % 1000000007, \$elapsed, \$statics, \$firstPass);

        PHP;
}

foreach (TARGETS as $directory => $target) {
    $path = __DIR__ . '/' . $directory . '/specialization.php';
    $source = str_replace('{ROUNDS_PLACEHOLDER}', (string) ROUNDS, generate($target['namespace'], $target['style']));

    file_put_contents($path, $source);

    echo sprintf('Wrote %s (%d specializations, %d rounds).', $path, TAGS, ROUNDS) . PHP_EOL;
}
