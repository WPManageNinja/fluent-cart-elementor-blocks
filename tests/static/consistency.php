<?php
/**
 * Checks that need no WordPress: text domain, version and config consistency.
 *
 * Usage:  php tests/static/consistency.php
 */

require __DIR__ . '/../lib/assert.php';

$root = dirname(__DIR__, 2);
$test = new FceTest('static/consistency');
$domain = 'fluent-cart-elementor-blocks';

// Every translation call names this add-on's text domain.
$files = [$root . '/fluent-cart-elementor-blocks.php'];
foreach (['app', 'boot'] as $dir) {
    if (!is_dir($root . '/' . $dir)) {
        continue;
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $dir, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
}

$calls = 0;
$wrong = [];
$pattern = '/\b(?:__|_e|_x|_n|_nx|esc_html__|esc_html_e|esc_attr__|esc_attr_e|esc_html_x|esc_attr_x)\(\s*([\'"]).*?\1\s*(?:,\s*[^,()]+?)*,\s*[\'"]([a-z0-9_-]+)[\'"]\s*\)/s';
foreach ($files as $file) {
    $code = file_get_contents($file);
    if (!preg_match_all($pattern, $code, $matches, PREG_OFFSET_CAPTURE)) {
        continue;
    }
    foreach ($matches[2] as $match) {
        $calls++;
        if ($match[0] !== $domain) {
            $line = substr_count(substr($code, 0, $match[1]), "\n") + 1;
            $wrong[] = str_replace($root . '/', '', $file) . ':' . $line . " uses '{$match[0]}'";
        }
    }
}
$test->check($calls > 100, "found the translation calls ({$calls})");
$test->check(!$wrong, 'every translation call uses the add-on text domain' . ($wrong ? ' (' . count($wrong) . "):\n         " . implode("\n         ", array_slice($wrong, 0, 10)) : ''));

// Header, constant, readme, config and POT agree.
$main = file_get_contents($root . '/fluent-cart-elementor-blocks.php');
$readme = file_get_contents($root . '/readme.txt');
$config = require $root . '/config/app.php';

preg_match('/^Version:\s*(\S+)/m', $main, $headerVersion);
preg_match("/define\('FLUENTCART_ELEMENTOR_BLOCKS_VERSION',\s*'([^']+)'\)/", $main, $constVersion);
preg_match('/^Stable tag:\s*(\S+)/m', $readme, $stableTag);
preg_match('/^Text Domain:\s*(\S+)/m', $main, $headerDomain);
preg_match('/^Domain Path:\s*(\S+)/m', $main, $domainPath);

$version = $headerVersion[1] ?? '';
$test->check($version !== '', "plugin header has a version ({$version})");
$test->same($constVersion[1] ?? '', $version, 'version constant matches the header');
$test->same($stableTag[1] ?? '', $version, 'readme Stable tag matches the header');
$test->check(strpos($readme, '= ' . $version . ' (') !== false, 'readme changelog has an entry for this version');
$test->same($headerDomain[1] ?? '', $domain, 'header Text Domain');
$test->same($config['text_domain'] ?? '', $domain, 'config text_domain matches the header');
$test->same($domainPath[1] ?? '', $config['domain_path'] ?? '', 'header Domain Path matches config');

$potFile = $root . '/language/' . $domain . '.pot';
$test->check(is_file($potFile), 'POT file exists');
$test->check(is_file($potFile) && strpos(file_get_contents($potFile), 'X-Domain: ' . $domain) !== false, 'POT is for this text domain');

$test->finish();
