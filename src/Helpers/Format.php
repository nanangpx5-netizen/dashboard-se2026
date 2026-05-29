<?php

namespace App\Helpers;

final class Format
{
    public static function number(mixed $number, int $decimals = 0): string
    {
        return number_format((float) $number, $decimals, ',', '.');
    }

    public static function decimal(mixed $number, int $decimals = 2): string
    {
        return number_format((float) $number, $decimals, ',', '.');
    }

    public static function percent(mixed $value, mixed $total, int $decimals = 2): string
    {
        if ((float) $total === 0.0) {
            return '0,00%';
        }
        return self::decimal(((float) $value / (float) $total) * 100, $decimals) . '%';
    }

    public static function date(string $date, string $format = 'd/m/Y'): string
    {
        if (empty($date) || $date === '0000-00-00') {
            return '-';
        }
        return date($format, strtotime($date));
    }

    public static function datetime(string $datetime, string $format = 'd/m/Y H:i'): string
    {
        if (empty($datetime)) {
            return '-';
        }
        return date($format, strtotime($datetime));
    }

    public static function timeAgo(string $datetime): string
    {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;

        if ($diff < 60) return 'baru saja';
        if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
        if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
        if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';

        return date('d/m/Y', $timestamp);
    }

    public static function truncate(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length) . $suffix;
    }

    public static function phone(string $number): string
    {
        $number = preg_replace('/[^0-9]/', '', $number);
        if (strlen($number) <= 7) return $number;
        if (str_starts_with($number, '62')) {
            return '+62 ' . substr($number, 2, 3) . '-' . substr($number, 5, 4) . '-' . substr($number, 9);
        }
        if (str_starts_with($number, '0')) {
            return '(+62) ' . substr($number, 1, 3) . '-' . substr($number, 4, 4) . '-' . substr($number, 8);
        }
        return $number;
    }

    public static function hariIndo(string $day): string
    {
        $map = [
            'Sunday'    => 'Minggu',
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu',
        ];
        return $map[$day] ?? $day;
    }

    public static function bulanIndo(string $date): string
    {
        $map = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
            '04' => 'April',   '05' => 'Mei',      '06' => 'Juni',
            '07' => 'Juli',    '08' => 'Agustus',   '09' => 'September',
            '10' => 'Oktober', '11' => 'November',  '12' => 'Desember',
        ];
        $month = date('m', strtotime($date));
        return $map[$month] ?? $month;
    }

    public static function tanggalIndo(string $date): string
    {
        if (empty($date) || $date === '0000-00-00') {
            return '-';
        }
        $t = strtotime($date);
        return self::hariIndo(date('l', $t)) . ', ' . date('j', $t) . ' '
            . self::bulanIndo($date) . ' ' . date('Y', $t);
    }
}
