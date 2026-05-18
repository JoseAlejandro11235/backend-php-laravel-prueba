<?php

namespace App\Enums;

enum StockMovementType: string
{
    case In = 'entrada';
    case Out = 'salida';
}
