<?php
namespace App\Enum;

enum TipoUsuario: string
{
    case ANONIMO = 'anonimo';
    case CLIENTE = 'cliente';
    case NO_CLIENTE = 'no_cliente';
}