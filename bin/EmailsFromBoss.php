<?php

require_once __DIR__ . '/../vendor/autoload.php';

use function Unisender\parsExcel;
use function Unisender\getBornToday;
use function Unisender\addShortName;
use function Unisender\addGender;
use function Unisender\makePersonalHtmlFromBoss;
use function Unisender\sendDataToUnisender;
use function Unisender\rightToLog;

const LOG_FILE_BOSS = __DIR__ . '/../logs/FromBoss.json';

$method = 'sendEmail';
$url = "https://api.unisender.com/ru/api/{$method}";
$key = 'key-from-unisender';
$emailsListId = '19989539';

$allEmployees = parsExcel(__DIR__ . '/../input-data/birthday.xlsx');
$bornTodayEmployees = addGender(addShortName(getBornToday($allEmployees)));

if (empty($bornTodayEmployees)) {
    rightToLog('{"today no cake"}', LOG_FILE_BOSS);
    return;
}

$htmlTemplate = file_get_contents(__DIR__ . '/../html-templates/FromBoss.html');

foreach ($bornTodayEmployees as $employee) {
    if (empty($employees['email'])) {
        rightToLog('{"no email adress"}', LOG_FILE_BOSS);
        break;
    }
    $personalHtml = makePersonalHtmlFromBoss($htmlTemplate, $employee);
    $postData = [
        'format' => 'json',
        'api_key' => $key,
        'email' => $employee['email'],
        'sender_name' => 'Воруков Руслан Рамазанович',
        'sender_email' => 'ruslan.vorukov@family-yug.ru',
        'subject' => 'С Днем Рождения!',
        'body' => $personalHtml,
        'list_id' => $emailsListId,
    ];
    sendDataToUnisender($url, $postData, LOG_FILE_BOSS);
}
