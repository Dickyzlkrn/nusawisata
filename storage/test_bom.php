<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(\App\Services\DatasetImportService::class);
$tmp = sys_get_temp_dir() . '/test_bom_' . uniqid() . '.csv';
file_put_contents($tmp, chr(239).chr(187).chr(191)."User_Id,Place_Id,Place_Ratings,Place_Name,Province,Category,Price,Destination_Rating\n1,1,5.0,Pantai,Bali,Alam,0,4.5\n");

$res = $service->validateFile($tmp);
echo json_encode($res, JSON_PRETTY_PRINT) . PHP_EOL;
@unlink($tmp);
