<?php
$output_directory = "api_documentation/trunk";
$parse_directory = "lib";
$template = 'HTML:Smarty:Doctrine';
$title = 'Doctrine Documentation';

$command = "phpdoc -pp on -s on -dn Doctrine -d $parse_directory -ti $title -t $output_directory -o $template";

@exec($command);