<?php

require_once __DIR__ . '/../vendor/autoload.php';

use function Unisender\parsExcel;
use function Unisender\getBornInNextMonth;
use function Unisender\sortByDate;
use function Unisender\makeMonthlyHtmlList;
use function Unisender\getNextMonthName;
use function Unisender\sendDataToUnisender;

const LOG_FILE_MONTHLY = __DIR__ . '/../logs/MonthlyNotification.json';

$methodCreateEmail = 'createEmailMessage';
$methodCreateCampaign = 'createCampaign';
$url1 = "https://api.unisender.com/ru/api/{$methodCreateEmail}";
$url2 = "https://api.unisender.com/ru/api/{$methodCreateCampaign}";
$key = 'key-from-unisender';
$emailsListId = '19989539';


$bornInNextMonthEmployees = sortByDate(getBornInNextMonth(parsExcel(__DIR__ . '/../input-data/birthday.xlsx')));
$htmlTemplate = file_get_contents(__DIR__ . '/../html-templates/MonthlyNotification.html');
$birthdayHtmlList = makeMonthlyHtmlList($bornInNextMonthEmployees);
$nextMonthInCalendar = getNextMonthName();

$bodyMessage = str_replace("{next month}", $nextMonthInCalendar, $htmlTemplate);
$bodyMessage = str_replace("{list}", $birthdayHtmlList, $bodyMessage);

$DataForMethodCreateEmail = [
    'format' => 'json',
    'api_key' => $key,
    'sender_name' => 'СК "Семья"',
    'sender_email' => 'box@family-yug.ru',
    'subject' => 'Дни рождения у сотрудников в ' . $nextMonthInCalendar,
    'body' => $bodyMessage,
    'list_id' => $emailsListId,
];

$response = sendDataToUnisender($url1, $DataForMethodCreateEmail, LOG_FILE_MONTHLY);
$message_id = json_decode($response, true);

$DataForMethodCreateCampaign = [
    'format' => 'json',
    'api_key' => $key,
    'message_id' => $message_id['result']['message_id'],
];

sendDataToUnisender($url2, $DataForMethodCreateCampaign, LOG_FILE_MONTHLY);
