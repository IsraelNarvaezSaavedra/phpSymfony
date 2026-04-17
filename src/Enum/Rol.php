<?php

namespace App\Enum;

enum Rol: string{
    case ADMIN = "ROLE_ADMIN";
    case ADMIN_COCHE = "ROLE_ADMIN_COCHE";
    case USER = "ROLE_USER";
}