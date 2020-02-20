<?php

namespace SymfonyCasts\Bundle\ResetPassword;

class ResetPasswordTimeHelper
{
    /**
     * Convert seconds into a human readable string
     *
     * Providing 8100 will return 2 hours 15 minutes
     */
    public static function getFormattedSeconds(int $seconds): string
    {

        $hours = (int) floor($seconds / 3600);
        $minutes = (int) floor(($seconds / 60) % 60);

        $time = '';

        if ($hours === 1) {
            $time .= "$hours hour";
        }

        if ($hours >= 2) {
            $time .= "$hours hours";
        }

        if ($minutes > 0) {
            $time = (self::addSpace($time)) . "$minutes minutes";
        }

        return $time;
    }

    private static function addSpace(string $time): string
    {
        if (empty($time)) {
            return '';
        }

        return $time . ' ';
    }
}