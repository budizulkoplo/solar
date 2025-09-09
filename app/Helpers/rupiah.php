<?php
if (! function_exists('format_rupiah')) {
    function format_rupiah($angka, $prefix = 'Rp ')
    {
        $angka = (float) $angka ?: 0;
        return $prefix . number_format($angka, 0, ',', '.');
    }
}
