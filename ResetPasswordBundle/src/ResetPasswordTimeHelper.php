<?php

namespace SymfonyCasts\Bundle\ResetPassword;

class ResetPasswordTimeHelper
{
    /**
     * @TODO WIP
     * turn 3600 seconds into something human friendly for templates...
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
            if (!empty($time)) {
                $time .= ' ';
            }

            $time .= "$minutes minutes";
        }

        return $time;
    }
}