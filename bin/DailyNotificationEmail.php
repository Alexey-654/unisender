<?php

require_once __DIR__ . '/../vendor/autoload.php';

use function Unisender\parsExcel;
use function Unisender\getBornToday;
use function Unisender\makeDailyHtmlList;
use function Unisender\makePersonal;
use function Unisender\sendDataToUnisender;
use function Unisender\rightToLog;

const LOG_FILE_DAILY = __DIR__ . '/../logs/DailyNotification.json';

$methodCreateEmail = 'createEmailMessage';
$methodCreateCampaign = 'createCampaign';
$url1 = "https://api.unisender.com/ru/api/{$methodCreateEmail}";
$url2 = "https://api.unisender.com/ru/api/{$methodCreateCampaign}";
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

$DataForMethodCreateEmail = [
    'format' => 'json',
    'api_key' => $key,
    'sender_name' => 'СК "Семья"',
    'sender_email' => 'box@family-yug.ru',
    'subject' => 'Сегодня день рождения у сотрудников',
    'body' => $bodyMessage,
    'list_id' => $emailsListId,
];
$response = sendDataToUnisender($url1, $DataForMethodCreateEmail, LOG_FILE_DAILY);
$message_id = json_decode($response, true);


$DataForMethodCreateCampaign = [
    'format' => 'json',
    'api_key' => $key,
    'message_id' => $message_id['result']['message_id'],
];
sendDataToUnisender($url2, $DataForMethodCreateCampaign, LOG_FILE_DAILY);
