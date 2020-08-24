<?php

require_once __DIR__ . '/../vendor/autoload.php';

use function Unisender\parsExcel;
use function Unisender\getBornInNextMonth;
use function Unisender\makeMonthlyHtmlList;
use function Unisender\getNextMonthName;
use function Unisender\sendDataToUnisender;

const LOG_FILE_MONTHLY = __DIR__ . '/../logs/MonthlyNotification.json';

$methods = ['createEmailMessage', 'createCampaign'];
$urlCreateEmail = "https://api.unisender.com/ru/api/{$methods[0]}";
$urlCreateCampaign = "https://api.unisender.com/ru/api/{$methods[1]}";
$key = 'key-from-unisender';
$emailsListId = '19989539';

$employeesBirthday = parsExcel(__DIR__ . '/../input-data/birthday.xlsx');
$bornInNextMonthEmployees = getBornInNextMonth($employeesBirthday);
$birthdayHtmlList = makeMonthlyHtmlList($bornInNextMonthEmployees);
$htmlTemplate = file_get_contents(__DIR__ . '/../html-templates/MonthlyNotification.html');

$nextMonthInCalendar = getNextMonthName();
$bodyMessage = str_replace("{next month}", $nextMonthInCalendar, $htmlTemplate);
$bodyMessage = str_replace("{list}", $birthdayHtmlList, $bodyMessage);

$dataForMethodCreateEmail = [
    'format' => 'json',
    'api_key' => $key,
    'sender_name' => 'СК "Семья"',
    'sender_email' => 'box@family-yug.ru',
    'subject' => 'Дни рождения у сотрудников в ' . $nextMonthInCalendar,
    'body' => $bodyMessage,
    'list_id' => $emailsListId,
];

$responseForCreateEmail = sendDataToUnisender($urlCreateEmail, $dataForMethodCreateEmail);
$response_params = json_decode($responseForCreateEmail, true);

$dataForMethodCreateCampaign = [
    'format' => 'json',
    'api_key' => $key,
    'message_id' => $response_params['result']['message_id'],
];

$responseForCreateCampaign = sendDataToUnisender($urlCreateCampaign, $dataForMethodCreateCampaign);
rightToLog($responseForCreateCampaign, LOG_FILE_MONTHLY);
