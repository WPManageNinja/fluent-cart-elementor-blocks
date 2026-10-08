<?php
/**
 * Shared assertions for the tests. Not loaded by the plugin.
 */

class FceTest
{
    private $name;
    private $checks = 0;
    private $failures = 0;

    public function __construct(string $name)
    {
        $this->name = $name;
        echo "[{$name}]\n";
    }

    public function check($condition, string $label): void
    {
        $this->checks++;

        if ($condition) {
            echo "  ok   {$label}\n";
            return;
        }

        $this->failures++;
        echo "  FAIL {$label}\n";
    }

    public function same($actual, $expected, string $label): void
    {
        $this->check($actual === $expected, $actual === $expected
            ? $label
            : $label . ' (expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . ')');
    }

    /** Prints the summary and exits non-zero on any failure, so the runner records FAIL. */
    public function finish(): void
    {
        $passed = $this->checks - $this->failures;
        echo "[{$this->name}] {$passed}/{$this->checks} passed\n";

        if ($this->failures) {
            exit(1);
        }
    }

    /** Stops a live test that cannot run here, as a failure so a broken setup isn't read as green. */
    public static function requireLive(string $file, array $classes = []): void
    {
        if (!defined('ABSPATH')) {
            fwrite(STDERR, "Run via: bash tests/bin/run-all.sh (or wp eval-file {$file})\n");
            exit(1);
        }

        foreach ($classes as $class) {
            if (!class_exists($class)) {
                echo "SKIP {$file}: {$class} is not loaded (are FluentCart and Elementor installed?)\n";
                exit(1);
            }
        }
    }
}
