<?php

namespace App\Enums;

enum DeliveryMethod: string
{
  case PICKUP = 'pickup';
  case DELIVERY = 'delivery';

  public function label(): string
  {
    return match ($this) {
      self::PICKUP => 'Ambil Sendiri',
      self::DELIVERY => 'Diantar',
    };
  }
}
