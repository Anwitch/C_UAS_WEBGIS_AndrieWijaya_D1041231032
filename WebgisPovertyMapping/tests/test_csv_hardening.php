<?php
header('Content-Type: text/plain');

$root = realpath(__DIR__ . '/..');
$pass = 0;
$fail = 0;

function check($label, $ok) {
    global $pass, $fail;
    if ($ok) {
        echo "PASS: {$label}\n";
        $pass++;
    } else {
        echo "FAIL: {$label}\n";
        $fail++;
    }
}

$import = file_get_contents($root . '/api/import/csv.php');
$export = file_get_contents($root . '/api/export/csv.php');

check('import checks upload error', strpos($import, 'UPLOAD_ERR_OK') !== false);
check('import checks csv extension', strpos($import, 'PATHINFO_EXTENSION') !== false || strpos($import, 'pathinfo(') !== false);
check('import checks file size', strpos($import, "csv_file']['size']") !== false || (strpos($import, '$file[\'size\']') !== false && strpos($import, '1024') !== false));
check('export defines csv safe cell helper', strpos($export, 'function csv_safe_cell') !== false);
check('export neutralizes spreadsheet formulas', strpos($export, "['=', '+', '-', '@']") !== false || strpos($export, 'str_starts_with') !== false);

echo "\n--- {$pass} passed, {$fail} failed ---\n";
exit($fail > 0 ? 1 : 0);
