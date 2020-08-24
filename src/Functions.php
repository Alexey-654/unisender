<?php

namespace Unisender;

use SimpleXLSX;
use Carbon\Carbon;

const TIME_ZONE = 'Europe/Moscow';
const LOCALE = 'ru_RU';

function parsExcel(string $inputFile): array
{
    $headerValues = [];
    $rows = [];
    $xlsx = SimpleXLSX::parse($inputFile);
    foreach ($xlsx->rows() as $key => $row) {
        if ($key === 0) {
            $headerValues = $row;
            continue;
        }
        $rows[] = array_combine($headerValues, $row);
    }
    
    return $rows;
}


function getBornToday(array $employees): array
{
    $employeesBirthdayToday = array_filter($employees, function ($employee) {
        if (empty($employee['born'])) {
            return;
        }
        $born = Carbon::createFromDate($employee['born']);
        $today = Carbon::now();
        if ($born->isBirthday($today)) {
            return $employee;
        }
    });

    return $employeesBirthdayToday;
}


function getBornInNextMonth(array $employees): array
{
    $employeesBirthdayinNextMonth = array_filter($employees, function ($employee) {
        if (empty($employee['born'])) {
            return;
        }
        if (Carbon::createFromDate($employee['born'])->isNextMonth()) {
            return $employee;
        }
    });
    
    return sortByDate($employeesBirthdayinNextMonth);
}


function addShortName($employeesBirthday)
{
    $nameAdded = array_map(function ($employee) {
        $name = mb_split(" ", $employee['full_name']);
        $employee['first and middle name'] = $name[1] . " " . $name[2];
        return $employee;
    }, $employeesBirthday);

    return $nameAdded;
}


function addGender($employeesBirthday)
{
    $genderAdded = array_map(function ($employee) {
        $gender = genGender($employee['full_name']);
        $employee['gender'] = $gender;
        return $employee;
    }, $employeesBirthday);

    return $genderAdded;
}


function genGender(string $fullName): string
{
    $femaleSuffixs = ['на'];
    $maleSuffixs = ['ич'];
    $suffixFromfullName = mb_substr($fullName, -2);
    if (in_array($suffixFromfullName, $femaleSuffixs)) {
        return 'female';
    } elseif (in_array($suffixFromfullName, $maleSuffixs)) {
        return 'male';
    } else {
        return 'absent';
    }
}


function sendDataToUnisender(string $url, array $postData)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}


function rightToLog(string $logString, string $logFile)
{
    $timeNow = Carbon::now(TIME_ZONE)->toDateTimeString();
    $logString = "{$timeNow} {$logString}\n";
    file_put_contents($logFile, $logString, FILE_APPEND);
}


function makeDailyHtmlList(array $bornTodayEmployees): string
{
    $result = array_reduce($bornTodayEmployees, function ($acc, $employee) {
        $acc = $acc . "<span>{$employee['full_name']}</span><br>\n";
        $acc = $acc . "<span style='color: #1d1d1b; font-size: 18px;'>{$employee['position']}</span><br><br>\n";
        return $acc;
    });

    return $result;
}


function makeMonthlyHtmlList(array $borninNextMonthEmployees): string
{
    $result = array_reduce($borninNextMonthEmployees, function ($acc, $employee) {
            $BornDate = Carbon::createFromDate($employee['born'])->format('d.m');
            $acc = $acc . "<li><span style='font-size:19px;'>{$BornDate} {$employee['full_name']}</span><br>\n";
            $acc = $acc . "<span style='font-size:16px;'>{$employee['position']}</span></li>\n";
            return $acc;
    });

    return $result;
}


function makePersonalHtmlFromBoss(string $htmlTemplate, array $employee): string
{
    $personalHtml = str_replace("{name}", $employee['first and middle name'], $htmlTemplate);
    switch ($employee['gender']) {
        case 'female':
            $personalHtml = str_replace("{treatment}", 'Уважаемая', $personalHtml);
            break;
        case 'male':
            $personalHtml = str_replace("{treatment}", 'Уважаемый', $personalHtml);
            break;
        default:
            $personalHtml = str_replace("{treatment}", '', $personalHtml);
    }

    return $personalHtml;
}


function getNextMonthName(): string
{
    $nextMonthInCalendar = Carbon::now()->locale('ru_RU')->addMonthNoOverflow()->month;
    $months = [
        'январе',
        'феврале',
        'марте',
        'апреле',
        'мае',
        'июне',
        'июле',
        'августе',
        'сентябре',
        'октябре',
        'ноябре',
        'декабре'
    ];

    return $months[$nextMonthInCalendar - 1];
}


function sortByDate($employees)
{
    usort(
        $employees,
        fn($a, $b) => Carbon::createFromDate($a['born'])->day <=> Carbon::createFromDate($b['born'])->day
    );

    return $employees;
}
