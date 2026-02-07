<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Nama printer thermal untuk struk
    |--------------------------------------------------------------------------
    | Mac/Linux: gunakan nama dari CUPS (lpstat -a)
    | Windows: nama printer di Devices
    | Contoh: XP-58, XP58, Epson_TM-T20
    */
    'receipt_printer' => env('RECEIPT_PRINTER', 'XP-58'),
];
