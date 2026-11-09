<?php
/**
 * Fail when an acceptance test would never run in CI.
 *
 *   php tests/check-groups.php
 *
 * CI runs the acceptance suite one `--group` at a time (the matrix in
 * .github/workflows/codeception.tests.yml), so a test method that carries none of those
 * groups is silently skipped. Tests marked `@group skipCI` are left out on purpose.
 */

$root = dirname(__DIR__);
$workflow = file_get_contents($root . '/.github/workflows/codeception.tests.yml');

preg_match_all('/^\s+group:\s*(\S+)\s*$/m', $workflow, $m);
$shards = array_unique($m[1]);

if (!$shards) {
    fwrite(STDERR, "check-groups: found no acceptance groups in the workflow matrix.\n");
    exit(1);
}

$missing = [];
$count = 0;

foreach (glob($root . '/tests/acceptance/*Cest.php') as $file) {
    $source = file_get_contents($file);

    // Each public test method, with the docblock directly above it when there is one.
    preg_match_all('~(/\*\*(?:(?!\*/).)*\*/\s*)?public\s+function\s+(\w+)\s*\(~s', $source, $tests, PREG_SET_ORDER);

    foreach ($tests as $test) {
        [, $docblock, $method] = $test;

        if (str_starts_with($method, '_')) {
            continue;
        }

        $count++;
        preg_match_all('/@group\s+(\S+)/', $docblock, $g);

        if (!array_intersect($g[1], $shards) && !in_array('skipCI', $g[1], true)) {
            $missing[] = basename($file) . '::' . $method;
        }
    }
}

if ($missing) {
    fwrite(STDERR, "These acceptance tests carry none of the CI groups (" . implode(', ', $shards) . ") and would not run in CI:\n");
    foreach ($missing as $test) {
        fwrite(STDERR, "  {$test}\n");
    }
    exit(1);
}

echo "check-groups: all {$count} acceptance tests belong to a CI group.\n";
