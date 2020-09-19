<?php
/* Copyright 2017-2020 Ino Detelić

 - Licensed under the EUPL, Version 1.2 only (the "Licence");
 - You may not use this work except in compliance with the Licence.

 - You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

 - Unless required by applicable law or agreed to in writing, software distributed
   under the Licence is distributed on an "AS IS" basis,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 - See the Licence for the specific language governing permissions and limitations under the Licence.
*/

function calMonthDays($year, $month) {
    $cal = [];

    $monthStartStr = $year . '-' . $month . '-01 00:00:00';
    $cal['dtToday'] = new DateTimeImmutable();
    $cal['dtStart'] = new DateTimeImmutable($monthStartStr);

    $cal['dtEnd'] = new DateTime($monthStartStr);
    $cal['dtEnd']->modify('next month - 1 second');

    $cal['dtPrev'] = new DateTime($monthStartStr);
    $cal['dtPrev']->modify('last month');

    $cal['dtNext'] = new DateTime($monthStartStr);
    $cal['dtNext']->modify('next month');

    $cal['monthStartsOn'] = ((int)$cal['dtStart']->format('w') + 6) % 7;
    $cal['monthEndsOn']   = ((int)$cal['dtEnd']->format('w') + 6) % 7;
    $cal['monthDays']     = (int)$cal['dtStart']->format('t');
    $cal['monthDaysPrev'] = (int)$cal['dtPrev']->format('t');

    $cal['month'] = [];
    $cal['todayFound']     = false;
    $cal['todayThisMonth'] = false;
    $cal['todayWeek']      = null;

    $dateToday = date('Ymd');
    $relativeToToday = '';

    $year = $cal['dtPrev']->format('Y');
    $month = $cal['dtPrev']->format('m');
    for ($day = $cal['monthDaysPrev'] - $cal['monthStartsOn'] + 1; $day <= $cal['monthDaysPrev']; $day++) {
        $day = str_pad((string)$day, 2, '0', STR_PAD_LEFT);

        if ($year . $month . $day < $dateToday) {
            $relativeToToday = 'past';
        } else if ($year . $month . $day == $dateToday) {
            $relativeToToday = 'today';
            $cal['todayFound'] = true;
        } else {
            $relativeToToday = 'future';
        }

        $cal['month'][$year][$month][$day] = $relativeToToday . ' prev';
    }

    $year  = $cal['dtStart']->format('Y');
    $month = $cal['dtStart']->format('m');
    $week  = 1;
    $weekDay = $cal['monthStartsOn'];
    for ($day = 1; $day <= $cal['monthDays']; $day++) {
        if ($weekDay > 6) {
            $weekDay = 0;
            $week++;
        }
        $weekDay++;

        $day = str_pad((string)$day, 2, '0', STR_PAD_LEFT);

        if ($year . $month . $day < $dateToday) {
            $relativeToToday = 'past';
        } else if ($year . $month . $day == $dateToday) {
            $relativeToToday = 'today';
            $cal['todayFound']     = true;
            $cal['todayThisMonth'] = true;
            $cal['todayWeek']      = $week;
        } else {
            $relativeToToday = 'future';
        }

        $cal['month'][$year][$month][$day] = $relativeToToday;
    }

    $year = $cal['dtNext']->format('Y');
    $month = $cal['dtNext']->format('m');
    for ($day = 1; $day < 7 - $cal['monthEndsOn']; $day++) {
        $day = str_pad((string)$day, 2, '0', STR_PAD_LEFT);

        if ($year . $month . $day < $dateToday) {
            $relativeToToday = 'past';
        } else if ($year . $month . $day == $dateToday) {
            $relativeToToday = 'today';
            $cal['todayFound'] = true;
        } else {
            $relativeToToday = 'future';
        }

        $cal['month'][$year][$month][$day] = $relativeToToday . ' next';
    }

    if (isset($cal['month'][date('Y')][date('m')][date('d')]))
        $cal['month'][date('Y')][date('m')][date('d')] .= ' today';

    return $cal;
}


