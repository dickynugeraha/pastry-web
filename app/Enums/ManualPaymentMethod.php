<?php

namespace App\Enums;

enum ManualPaymentMethod: string
{
    case BCA = 'bca';
    case DANA = 'dana';
    case GOPAY = 'gopay';
}
