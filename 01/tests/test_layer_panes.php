<?php

$root = dirname(__DIR__);

function read_file_or_fail(string $path): string
{
    if (!is_file($path)) {
        fwrite(STDERR, "Missing file: {$path}\n");
        exit(1);
    }

    return file_get_contents($path);
}

function assert_contains(string $haystack, string $needle, string $label): void
{
    if (strpos($haystack, $needle) === false) {
        fwrite(STDERR, "FAIL: {$label} must contain {$needle}\n");
        exit(1);
    }
}

$index = read_file_or_fail($root . DIRECTORY_SEPARATOR . 'index.php');
$choropleth = read_file_or_fail($root . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'choropleth.js');
$point = read_file_or_fail($root . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'point.js');
$jalan = read_file_or_fail($root . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'jalan.js');
$parsil = read_file_or_fail($root . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'parsil.js');

foreach (['choroplethPane', 'parsilPane', 'jalanPane', 'drawPane', 'pointPane'] as $pane) {
    assert_contains($index, "'{$pane}'", "index.php");
}
assert_contains($index, 'map.createPane(name)', 'index.php');
assert_contains($index, '.leaflet-pane.is-hit-disabled *', 'choropleth disabled pane CSS');
assert_contains($index, 'function setDrawBlockingLayerHitTesting(enabled)', 'draw blocking pane helper');
foreach (['choroplethPane', 'parsilPane', 'jalanPane', 'pointPane', 'drawPane'] as $pane) {
    assert_contains($index, "'{$pane}'", 'draw blocking pane list');
}
assert_contains($index, 'function setChoroplethHitTesting(enabled)', 'choropleth hit-testing helper');
assert_contains($index, "pane.style.pointerEvents = enabled ? '' : 'none';", 'choropleth hit-testing toggle');
assert_contains($index, "pane.classList.toggle('is-hit-disabled', !enabled);", 'choropleth disabled pane class toggle');
assert_contains($index, 'window._choro.setHitTesting(enabled)', 'choropleth per-feature hit-testing toggle');
assert_contains($index, 'setChoroplethHitTesting(false)', 'draw tool disables choropleth hit-testing');
assert_contains($index, 'setChoroplethHitTesting(true)', 'draw tool restores choropleth hit-testing');
assert_contains($index, 'function finishActiveDrawing()', 'global finish drawing helper');
assert_contains($index, 'onclick="finishActiveDrawing()"', 'draw hint finish button');
assert_contains($index, 'onclick="cancelActiveDrawing()"', 'draw hint cancel button');

assert_contains($choropleth, "pane: 'choroplethPane'", 'choropleth layer options');
assert_contains($choropleth, 'function isClassicDrawingActive()', 'choropleth draw guard');
assert_contains($choropleth, 'if (isClassicDrawingActive()) return;', 'choropleth draw guard usage');
assert_contains($choropleth, 'function setLayerHitTesting(leafletLayer, enabled)', 'choropleth layer hit-testing helper');
assert_contains($choropleth, "layer._path.style.pointerEvents = enabled ? '' : 'none';", 'choropleth path hit-testing toggle');
assert_contains($choropleth, 'setHitTesting: setAllHitTesting', 'choropleth public hit-testing API');
assert_contains($point, "pane: 'pointPane'", 'point marker options');
assert_contains($point, "pane: 'drawPane'", 'temporary point marker options');
assert_contains($jalan, "pane: 'jalanPane'", 'jalan polyline options');
assert_contains($jalan, "pane: 'drawPane'", 'jalan drawing preview options');
assert_contains($jalan, "map.on('dblclick', onMapDoubleClick)", 'jalan double click finish handler');
assert_contains($jalan, 'if (e.originalEvent) L.DomEvent.stop(e.originalEvent);', 'jalan double click event stop');
assert_contains($jalan, 'e.preventDefault()', 'jalan enter finish handler');
assert_contains($jalan, 'window._jalanFinishDraw = finishDrawing;', 'jalan exposed finish handler');
assert_contains($parsil, "pane: 'parsilPane'", 'parsil polygon options');
assert_contains($parsil, "pane: 'drawPane'", 'parsil drawing preview options');
assert_contains($parsil, "map.on('dblclick', onMapDoubleClick)", 'parsil double click finish handler');
assert_contains($parsil, 'if (e.originalEvent) L.DomEvent.stop(e.originalEvent);', 'parsil double click event stop');
assert_contains($parsil, 'e.preventDefault()', 'parsil enter finish handler');
assert_contains($parsil, 'window._parsilFinishDraw = finishDrawing;', 'parsil exposed finish handler');

echo "PASS: layer panes are configured\n";
