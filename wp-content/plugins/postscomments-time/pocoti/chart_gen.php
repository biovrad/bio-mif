<?php

// prevent file from being accessed directly
if ('chart_gen.php' == basename($_SERVER['SCRIPT_FILENAME']))
	die ('Please do not access this file directly. Thanks!');

require_once($dir."/src/jpgraph.php");
require_once($dir."/src/jpgraph_line.php");

// Create the graph. These two calls are always required
$graph = new Graph($options['width'], $options['height'], "auto");
$graph->img->SetImgFormat("png");
$graph->SetScale("textint");
$graph->SetY2Scale("int");
$graph->SetColor('white');
$graph->SetMarginColor("aliceblue");

// Create the linear plot
$lineplot=new LinePlot($posts);
$lineplot2=new LinePlot($comments);

// Add the plot to the graph
$graph->Add($lineplot);
$graph->AddY2($lineplot2);

// Formatting
$graph->img->SetMargin(50,60,30,40);

$graph->title->Set($title);
$graph->xaxis->SetTitle($xtitle, 'middle');
$graph->yaxis->title->Set($options['poststr']);
$graph->y2axis->title->Set($options['commentstr']);

$graph->title->SetFont(FF_FONT2,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_NORMAL);
$graph->y2axis->title->SetFont(FF_FONT1,FS_NORMAL);
$graph->xaxis->title->SetFont(FF_FONT1,FS_NORMAL);

$graph->xaxis->SetTickLabels($tickstrings);

$graph->yaxis->SetColor($options['colorpost']);
$graph->y2axis->SetColor($options['colorcomment']);

$lineplot->SetColor($options['colorpost']);
$lineplot->SetWeight(2);

$lineplot2->SetColor($options['colorcomment']);
$lineplot2->SetWeight(2);

$graph->SetShadow();

?>