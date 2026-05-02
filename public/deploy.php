<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<pre>";

$secret = "shahge";

if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
    die("Access denied");
}

$output = shell_exec(
'cd /home/USERNAME/public_html/projects/posdemo &&
git pull origin development 2>&1'
);

echo $output;