<?php

$secret = "shahge";
echo $secret;exit;

if ($_GET['key'] !== $secret) {
    die("Access denied");
}
// composer install --no-dev &&
echo shell_exec('
cd /home/u472752505/domains/ismailfashionparadise.com/public_html/projects/posdemo &&
git pull origin development &&
php artisan migrate --force &&
php artisan cache:clear &&
php artisan config:cache
2>&1');
