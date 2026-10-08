<?php
/**
 * Shared assertions for the tests. Not loaded by the plugin.
 */

class FceTest
{
    private $name;
    private $checks = 0;
    private $failures = 0;
    private $skipped = 0;

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

    /**
     * Record a check that cannot run here, with the reason.
     *
     * Not a pass and not a failure: some assertions need a context WP-CLI does
     * not provide, and silently dropping them would hide the gap while failing
     * them would train everyone to ignore a red gate.
     */
    public function skip(string $label, string $why): void
    {
        $this->skipped++;
        echo "  SKIP {$label} — {$why}\n";
    }

    /** Prints the summary and exits non-zero on any failure, so the runner records FAIL. */
    public function finish(): void
    {
        $passed = $this->checks - $this->failures;
        $summary = "[{$this->name}] {$passed}/{$this->checks} passed";

        if ($this->skipped) {
            $summary .= ", {$this->skipped} skipped";
        }

        echo $summary . "\n";

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
