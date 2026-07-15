<?php
$out = __DIR__ . DIRECTORY_SEPARATOR . 'check_phpunit_env.txt';
$fh = fopen($out, 'wb');
if (!$fh) {
    exit(1);
}
function writeln($fh, $line) {
    fwrite($fh, $line . "\n");
}
writeln($fh, 'START');
$php = 'C:\\xampp\\php\\php.exe';
if (file_exists($php)) {
    writeln($fh, "PHP exists: $php");
    $ver = shell_exec("\"$php\" -v 2>&1");
    writeln($fh, "PHP version: " . trim($ver));
} else {
    writeln($fh, 'PHP not found');
}
$paths = [
    'vendor/phpunit/phpunit',
    'vendor/bin/phpunit',
    'vendor/autoload.php',
];
foreach ($paths as $path) {
    writeln($fh, "$path exists: " . (file_exists(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $path) ? 'yes' : 'no'));
}
$composer = shell_exec('where composer 2>&1');
writeln($fh, 'where composer: ' . trim($composer));
$composer = shell_exec('composer --version 2>&1');
writeln($fh, 'composer --version: ' . trim($composer));
$pwd = shell_exec('cd 2>&1');
writeln($fh, 'pwd: ' . trim($pwd));
fclose($fh);
echo 'DONE';
