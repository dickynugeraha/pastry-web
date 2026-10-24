<?php

namespace App\Services;

use App\Models\Order;

class OrderReportPdf
{
    private const HEIGHT = 841.89;

    /**
     * @param  array<string, mixed>  $summary
     * @param  iterable<Order>  $orders
     */
    public function render(array $summary, iterable $orders): string
    {
        $pages = [];
        $content = $this->header($summary, true);
        $rowTop = 302;
        $hasOrders = false;

        foreach ($orders as $order) {
            $hasOrders = true;

            if ($rowTop > 770) {
                $pages[] = $content;
                $content = $this->header($summary, false);
                $rowTop = 178;
            }

            $content .= $this->rule($rowTop + 22);
            $content .= $this->tableDividers($rowTop - 16, $rowTop + 3);
            $content .= $this->text(48, $rowTop, $this->shorten($order->order_code, 24), 8, 'F3');
            $content .= $this->text(165, $rowTop, $this->shorten($order->user?->name ?? '-', 22), 9, 'F3');
            $content .= $this->text(312, $rowTop, ucfirst($order->status), 9, 'F3');
            $content .= $this->text(394, $rowTop, $order->created_at->format('d/m/Y'), 9, 'F3');
            $total = $this->currency((float) $order->total);
            $content .= $this->text(548 - mb_strlen($total) * 5.4, $rowTop, $total, 9, 'F3');
            $paymentDetails = implode(' | ', [
                $order->payment_channel?->value ?? '-',
                $order->manual_payment_method?->value ?? $order->payment_type ?? '-',
                $order->payment_status,
                $order->paid_at?->format('d/m/Y H:i') ?? '-',
                $order->payment?->confirmedBy?->name ?? '-',
            ]);
            $content .= $this->text(48, $rowTop + 15, $this->shorten($paymentDetails, 105), 8);
            $rowTop += 40;
        }

        if (! $hasOrders) {
            $content .= $this->text(48, 313, 'Belum ada pesanan pada periode ini.', 10);
        }

        $pages[] = $content;

        foreach ($pages as $index => $page) {
            $pages[$index] .= $this->rule(801);
            $pages[$index] .= $this->text(40, 820, 'Bunkue Pastry - Laporan pesanan', 8);
            $pages[$index] .= $this->text(487, 820, 'Halaman '.($index + 1).' / '.count($pages), 8);
        }

        return $this->document($pages);
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    private function header(array $summary, bool $firstPage): string
    {
        $content = $this->text(40, 44, 'Bunkue.', 23, 'F2');
        $content .= $this->text(40, 72, 'LAPORAN PESANAN', 10, 'F2');
        $content .= $this->text(400, 72, now()->format('d/m/Y H:i'), 9);
        $content .= $this->rule(96);

        if ($firstPage) {
            $content .= $this->text(40, 132, 'Periode: '.$summary['period']['from'].' s.d. '.$summary['period']['to'], 10);
            $content .= $this->card(40, 'Total pesanan', number_format($summary['total_orders'], 0, ',', '.'));
            $content .= $this->card(215, 'Total pendapatan', $this->currency($summary['total_revenue']));
            $content .= $this->card(390, 'Rata-rata pesanan', $this->currency($summary['average_order_value']));
            $content .= $this->text(40, 248, 'Total pendapatan hanya dari pembayaran paid.', 9);
            $tableTop = 260;
        } else {
            $content .= $this->text(40, 126, 'Lanjutan: '.$summary['period']['from'].' s.d. '.$summary['period']['to'], 10);
            $tableTop = 136;
        }

        $content .= $this->rectangle(40, $tableTop, 515, 26);
        $content .= $this->tableDividers($tableTop, $tableTop + 26);

        foreach ([48 => 'Kode', 165 => 'Pelanggan', 312 => 'Status', 394 => 'Tanggal', 510 => 'Total'] as $left => $label) {
            $content .= $this->text($left, $tableTop + 18, $label, 9, 'F2');
        }

        return $content;
    }

    private function card(float $left, string $label, string $value): string
    {
        return $this->rectangle($left, 148, 165, 78)
            .$this->text($left + 12, 174, $label, 9)
            .$this->text($left + 12, 202, $value, mb_strlen($value) > 16 ? 11 : 15, 'F2');
    }

    private function currency(float $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    private function shorten(string $value, int $length): string
    {
        return mb_strlen($value) > $length ? mb_substr($value, 0, $length - 3).'...' : $value;
    }

    private function text(float $left, float $top, string $value, int $size, string $font = 'F1'): string
    {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', $value) ?: '';
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded);
        $bottom = self::HEIGHT - $top;

        return "0 g BT /{$font} {$size} Tf 1 0 0 1 {$left} {$bottom} Tm ({$escaped}) Tj ET\n";
    }

    private function rectangle(float $left, float $top, float $width, float $height): string
    {
        $bottom = self::HEIGHT - $top - $height;

        return "0 G 0.6 w {$left} {$bottom} {$width} {$height} re S\n";
    }

    private function rule(float $top): string
    {
        $bottom = self::HEIGHT - $top;

        return "0 G 0.6 w 40 {$bottom} m 555 {$bottom} l S\n";
    }

    private function tableDividers(float $top, float $bottom): string
    {
        $upper = self::HEIGHT - $top;
        $lower = self::HEIGHT - $bottom;
        $content = '';

        foreach ([40, 157, 304, 386, 474, 555] as $left) {
            $content .= "0 G 0.6 w {$left} {$upper} m {$left} {$lower} l S\n";
        }

        return $content;
    }

    /**
     * @param  array<int, string>  $pages
     */
    private function document(array $pages): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Courier /Encoding /WinAnsiEncoding >>',
        ];
        $references = [];

        foreach ($pages as $content) {
            $pageId = count($objects) + 1;
            $streamId = $pageId + 1;
            $references[] = "{$pageId} 0 R";
            $objects[$pageId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources << /Font << /F1 3 0 R /F2 4 0 R /F3 5 0 R >> >> /Contents {$streamId} 0 R >>";
            $objects[$streamId] = '<< /Length '.strlen($content)." >>\nstream\n{$content}endstream";
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $references).'] /Count '.count($pages).' >>';
        ksort($objects);
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];

        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach (array_keys($objects) as $id) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$id])."\n";
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";
    }
}
