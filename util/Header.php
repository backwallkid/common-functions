<?php
class Header
{
    public static function json()
    {
        header('Content-type: application/json');
    }

    public static function text()
    {
        header('Content-Type="text/plain";charset=UTF-8');
    }

    public static function download($filename)
    {
        header('Content-Disposition: attachment; filename='.$filename);
    }
}