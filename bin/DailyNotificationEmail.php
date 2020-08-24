<?php

require_once __DIR__ . '/../vendor/autoload.php';

use function Unisender\parsExcel;
use function Unisender\getBornToday;
use function Unisender\makeDailyHtmlList;
use function Unisender\sendDataToUnisender;
use function Unisender\rightToLog;

const LOG_FILE_DAILY = __DIR__ . '/../logs/DailyNotification.json';

$methods = ['createEmailMessage', 'createCampaign'];
$urlCreateEmail = "https://api.unisender.com/ru/api/{$methods[0]}";
$urlCreateCampaign = "https://api.unisender.com/ru/api/{$methods[1]}";
$key = 'key-from-unisender';
$emailsListId = '19989539';


$bornTodayEmployees = getBornToday(parsExcel(__DIR__ . '/../input-data/birthday.xlsx'));

if (empty($bornTodayEmployees)) {
    rightToLog('{"today no cake"}', LOG_FILE_DAILY);
    return;
}

$htmlTemplate = file_get_contents(__DIR__ . '/../html-templates/DailyNotification.html');
$birthdayHtmlList = makeDailyHtmlList($bornTodayEmployees);
$bodyMessage = str_replace("{emloyees-list}", $birthdayHtmlList, $htmlTemplate);

$dataForMethodCreateEmail = [
    'format' => 'json',
    'api_key' => $key,
    'sender_name' => 'СК "Семья"',
    'sender_email' => 'box@family-yug.ru',
    'subject' => 'Сегодня день рождения у сотрудников',
    'body' => $bodyMessage,
    'list_id' => $emailsListId,
];

$responseForCreateEmail = sendDataToUnisender($urlCreateEmail, $dataForMethodCreateEmail, LOG_FILE_DAILY);
$response_params = json_decode($responseForCreateEmail, true);

$dataForMethodCreateCampaign = [
    'format' => 'json',
    'api_key' => $key,
    'message_id' => $response_params['result']['message_id'],
];

$responseForCreateCampaign = sendDataToUnisender($urlCreateCampaign, $dataForMethodCreateCampaign);
rightToLog($responseForCreateCampaign, LOG_FILE_DAILY);
