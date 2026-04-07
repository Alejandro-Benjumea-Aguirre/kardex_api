<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JWT Secret
    |--------------------------------------------------------------------------
    |
    | Clave secreta usada para firmar los access tokens (HS256).
    | Generá un valor seguro con:
    |   php artisan key:generate --show | base64
    |
    | Guardala en .env como JWT_SECRET y nunca la expongas.
    |
    */

    'secret' => env('JWT_SECRET'),

];
