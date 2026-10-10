<?php

namespace App\Enums;

enum ShippingStatus: string
{
  case PENDING = 'pending';
  case PROCESSING = 'processing';
  case DELIVERING = 'delivering';
  case READY_FOR_PICKUP = 'ready_for_pickup';
  case COMPLETED = 'completed';
  case CANCELLED = 'cancelled';

  public function label(): string
  {
    return match ($this) {
      self::PENDING => 'Menunggu Diproses',
      self::PROCESSING => 'Sedang disiapkan',
      self::DELIVERING => 'Sedang Dikirim',
      self::READY_FOR_PICKUP => 'Siap Diambil',
      self::COMPLETED => 'Selesai',
      self::CANCELLED => 'Dibatalkan',
    };
  }

  public static function forDelivery(): array
  {
    return [
      self::PENDING,
      self::PROCESSING,
      self::DELIVERING,
      self::COMPLETED,
      self::CANCELLED,
    ];
  }

  public static function forPickup(): array
  {
    return [
      self::PENDING,
      self::PROCESSING,
      self::READY_FOR_PICKUP,
      self::COMPLETED,
      self::CANCELLED,
    ];
  }

  public function isFinal(): bool
  {
    return in_array($this, [self::COMPLETED, self::CANCELLED]);
  }
}
