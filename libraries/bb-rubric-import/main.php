<?php
require_once('libs'.DIRECTORY_SEPARATOR.'Resources.php');
require_once('libs'.DIRECTORY_SEPARATOR.'Rubrics.php');

$resources = new BB_Resources();

$resources->parse();

$rubrics = new BB_Rubrics($resources->rubric->bbFile);

//$rubrics->parse();

/*print_r($rubrics->getVaLuesByTag("TITLE"));

print_r($rubrics->getVaLuesByTag("DESCRIPTION"));

print_r($rubrics->getVaLuesByTag("RUBRICROWS"));

print_r($rubrics->getVaLuesByTag("ROW"));*/

//print_r($rubrics->getVaLuesByNodePath("LEARNRUBRICS::TITLE"));

$rubrics->simpleParse();
