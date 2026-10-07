<?php
// Count comment lines per directory and per file, to find where a cleanup pays off.
// usage: php comment-survey.php <repo-root> [top-n]
//        php comment-survey.php <repo-root> --compare <git-ref> [top-n]
// --compare counts files changed since <git-ref> (including untracked) at that ref
// and in the working tree, to report how much a cleanup cut.
// PHP is counted with the tokenizer; JS/JSX/TS/Vue/CSS/SCSS with a simple scanner
// that skips quoted strings, so treat those numbers as close estimates.

$root = rtrim($argv[1] ?? '.', '/');
$ref  = null;
if (($argv[2] ?? '') === '--compare') {
    $ref = $argv[3] ?? '';
    if ($ref === '') {
        fwrite(STDERR, "usage: php comment-survey.php <repo-root> --compare <git-ref> [top-n]\n");
        exit(2);
    }
    $top = (int) ($argv[4] ?? 20);
} else {
    $top = (int) ($argv[2] ?? 20);
}
$skip = '#/(vendor|node_modules|assets|builds|dist|\.git|\.claude|language|\.playwright-mcp)/#';
$exts = ['php', 'js', 'jsx', 'mjs', 'ts', 'tsx', 'vue', 'css', 'scss'];

function phpCommentLines(string $src): int
{
    $n = 0;
    foreach (token_get_all($src) as $t) {
        if (is_array($t) && ($t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT)) {
            $n += substr_count(rtrim($t[1], "\n"), "\n") + 1;
        }
    }
    return $n;
}

function scanCommentLines(string $src, bool $lineComments): int
{
    $n = 0; $len = strlen($src); $i = 0; $quote = null;
    while ($i < $len) {
        $c = $src[$i];
        if ($quote) {
            if ($c === '\\') { $i += 2; continue; }
            if ($c === $quote) { $quote = null; }
            $i++; continue;
        }
        if ($c === '"' || $c === "'" || $c === '`') { $quote = $c; $i++; continue; }
        if ($c === '/' && ($src[$i + 1] ?? '') === '*') {
            $end = strpos($src, '*/', $i + 2);
            $end = $end === false ? $len : $end + 2;
            $n += substr_count(substr($src, $i, $end - $i), "\n") + 1;
            $i = $end; continue;
        }
        if ($lineComments && $c === '/' && ($src[$i + 1] ?? '') === '/' && ($i === 0 || $src[$i - 1] !== ':')) {
            $n++;
            $end = strpos($src, "\n", $i);
            $i = $end === false ? $len : $end; continue;
        }
        $i++;
    }
    return $n;
}

function commentLines(string $src, string $ext): int
{
    return $ext === 'php' ? phpCommentLines($src) : scanCommentLines($src, $ext !== 'css');
}

function pct(int $before, int $after): string
{
    return $before ? sprintf('%+.0f%%', 100 * ($after - $before) / $before) : 'new';
}

if ($ref !== null) {
    $git = 'git -C ' . escapeshellarg($root) . ' ';
    exec($git . 'rev-parse --verify --quiet ' . escapeshellarg($ref . '^{commit}'), $out, $rc);
    if ($rc !== 0) {
        fwrite(STDERR, "Unknown git ref: $ref\n");
        exit(2);
    }
    $changed = [];
    exec($git . 'diff --name-only ' . escapeshellarg($ref) . ' --', $changed);
    exec($git . 'ls-files --others --exclude-standard', $changed);

    $rows = []; $dirs = []; $before = 0; $after = 0;
    foreach (array_unique($changed) as $rel) {
        $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
        if (preg_match($skip, '/' . $rel) || !in_array($ext, $exts, true)) {
            continue;
        }
        $old = (string) shell_exec($git . 'show ' . escapeshellarg($ref . ':' . $rel) . ' 2>/dev/null');
        $path = $root . '/' . $rel;
        $new = is_file($path) ? (string) file_get_contents($path) : '';
        $b = $old === '' ? 0 : commentLines($old, $ext);
        $a = $new === '' ? 0 : commentLines($new, $ext);
        if ($a === $b) {
            continue;
        }
        $rows[$rel] = [$b, $a];
        $dir = dirname($rel);
        $dirs[$dir] = [($dirs[$dir][0] ?? 0) + $b, ($dirs[$dir][1] ?? 0) + $a];
        $before += $b; $after += $a;
    }

    if (!$rows) {
        echo "No comment-line changes since $ref.\n";
        exit(0);
    }
    $byCut = fn($x, $y) => ($x[1] - $x[0]) <=> ($y[1] - $y[0]);
    uasort($rows, $byCut);
    uasort($dirs, $byCut);

    printf("Comment lines since %s: %d -> %d (%+d, %s) in %d files\n\n", $ref, $before, $after, $after - $before, pct($before, $after), count($rows));
    echo "By directory:\n";
    foreach (array_slice($dirs, 0, $top, true) as $d => [$b, $a]) {
        printf("  %6d -> %-6d %+6d  %5s  %s\n", $b, $a, $a - $b, pct($b, $a), $d);
    }
    echo "\nBy file:\n";
    foreach (array_slice($rows, 0, $top, true) as $f => [$b, $a]) {
        printf("  %6d -> %-6d %+6d  %5s  %s\n", $b, $a, $a - $b, pct($b, $a), $f);
    }
    exit(0);
}

$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    $path = $file->getPathname();
    $rel = substr($path, strlen($root) + 1);
    if (preg_match($skip, '/' . $rel) || !in_array(strtolower($file->getExtension()), $exts, true)) {
        continue;
    }
    $src = (string) file_get_contents($path);
    $ext = strtolower($file->getExtension());
    $comments = commentLines($src, $ext);
    $lines = substr_count($src, "\n") + 1;
    $files[$rel] = [$comments, $lines];
}

$dirs = []; $total = 0; $totalLines = 0;
foreach ($files as $rel => [$c, $l]) {
    $dir = dirname($rel);
    $dirs[$dir] = ($dirs[$dir] ?? 0) + $c;
    $total += $c; $totalLines += $l;
}
arsort($dirs);
uasort($files, fn($a, $b) => $b[0] <=> $a[0]);

printf("%d comment lines in %d files (%.1f%% of %d lines)\n\n", $total, count($files), $totalLines ? 100 * $total / $totalLines : 0, $totalLines);
echo "By directory:\n";
foreach (array_slice($dirs, 0, $top, true) as $d => $c) {
    printf("  %6d  %s\n", $c, $d);
}
echo "\nHeaviest files (comment lines, % of file):\n";
foreach (array_slice($files, 0, $top, true) as $f => [$c, $l]) {
    printf("  %6d  %5.1f%%  %s\n", $c, 100 * $c / max(1, $l), $f);
}
