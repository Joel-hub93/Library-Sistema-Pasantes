<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Roles del sistema
    |--------------------------------------------------------------------------
    |
    | Roles que crea el sistema y que no se pueden eliminar desde la pantalla
    | de Roles: quitarlos dejaria a los administradores sin acceso. Se usa
    | tanto en el controlador como en la vista de la lista.
    |
    */

    'roles_sistema' => ['admin', 'subadmin'],

    /*
    |--------------------------------------------------------------------------
    | Permisos de la guard 'pasante'
    |--------------------------------------------------------------------------
    |
    | Permisos del portal de pasantes que se crean y se asignan al rol
    | 'pasante'. Los comparten el comando de sincronizacion y el alta y
    | edicion de pasantes (via App\Services\AccesoPasante).
    |
    */

    'permisos_pasante' => [
        'marcar-asistencia',
        'ver-propio-registro',
        'ver-propio-historial',
    ],

];
