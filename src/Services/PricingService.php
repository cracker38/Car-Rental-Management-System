<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Money;
use Crms\Settings;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Server-side pricing. Browser totals are ignored.
 *
 * Policy (documented):
 * - Return must be after pickup.
 * - Billable days = max(1, ceil(duration in seconds / 86400)).
 *   A 26-hour hire is 2 days.
 * - Monthly rate is used for each full 30-day block when a monthly rate exists.
 * - Weekly rate is used for each remaining full 7-day block when a weekly rate exists.
 * - Remaining days use the daily rate.
 * - Tax = (subtotal - discount) * tax_rate_percent / 100. Default tax rate is 0 until configured.
 * - Late fee = ceil(late seconds / 3600) * late_fee_per_hour (setting, default 0).
 * - Extra mileage = max(0, used_km - included_km_per_day * billable_days) * extra_km_rate.
 */
final class PricingService
{
    public function billableDays(string $pickup, string $returnAt): int
    {
        $p = new DateTimeImmutable($pickup);
        $r = new DateTimeImmutable($returnAt);
        if ($r <= $p) {
            throw new InvalidArgumentException('Return must be after pickup.');
        }
        $seconds = $r->getTimestamp() - $p->getTimestamp();
        return max(1, (int) ceil($seconds / 86400));
    }

    public function quoteRates(array $vehicle, string $pickup, string $returnAt, string $discount = '0'): array
    {
        $days = $this->billableDays($pickup, $returnAt);
        $daily = (float) $vehicle['daily_rate'];
        $weekly = $vehicle['weekly_rate'] !== null && $vehicle['weekly_rate'] !== '' ? (float) $vehicle['weekly_rate'] : null;
        $monthly = $vehicle['monthly_rate'] !== null && $vehicle['monthly_rate'] !== '' ? (float) $vehicle['monthly_rate'] : null;

        $remaining = $days;
        $base = 0.0;
        $months = 0;
        $weeks = 0;
        $dayUnits = 0;
        if ($monthly !== null && $remaining >= 30) {
            $months = intdiv($remaining, 30);
            $base += $months * $monthly;
            $remaining -= $months * 30;
        }
        if ($weekly !== null && $remaining >= 7) {
            $weeks = intdiv($remaining, 7);
            $base += $weeks * $weekly;
            $remaining -= $weeks * 7;
        }
        $dayUnits = $remaining;
        $base += $dayUnits * $daily;

        $discountAmt = max(0, (float) $discount);
        if ($discountAmt > $base) {
            throw new InvalidArgumentException('Discount cannot exceed the rental subtotal.');
        }
        $taxRate = (float) Settings::get('tax_rate_percent', '0');
        $taxable = $base - $discountAmt;
        $tax = (float) Money::round($taxable * $taxRate / 100);
        $total = (float) Money::add(Money::sub($base, $discountAmt), $tax);
        $deposit = (float) Settings::get('default_deposit_amount', '0');

        return [
            'estimated_days' => $days,
            'months' => $months,
            'weeks' => $weeks,
            'day_units' => $dayUnits,
            'agreed_daily_rate' => Money::round($daily),
            'estimated_amount' => Money::round($base),
            'discount_amount' => Money::round($discountAmt),
            'tax_amount' => Money::round($tax),
            'total' => Money::round($total),
            'security_deposit' => Money::round($deposit),
        ];
    }

    public function extras(?int $pickupMileage, ?int $returnMileage, ?string $pickupAt, ?string $expectedReturn, ?string $actualReturn, int $billableDays): array
    {
        $extraKmFee = '0.00';
        $lateFee = '0.00';
        if ($pickupMileage !== null && $returnMileage !== null) {
            if ($returnMileage < $pickupMileage) {
                throw new InvalidArgumentException('Return mileage cannot be below pickup mileage.');
            }
            $used = $returnMileage - $pickupMileage;
            $included = (int) Settings::get('included_km_per_day', '0') * $billableDays;
            $extra = max(0, $used - $included);
            $rate = (float) Settings::get('extra_km_rate', '0');
            $extraKmFee = Money::mul($extra, $rate);
        }
        if ($expectedReturn && $actualReturn) {
            $exp = new DateTimeImmutable($expectedReturn);
            $act = new DateTimeImmutable($actualReturn);
            if ($act > $exp) {
                $hours = (int) ceil(($act->getTimestamp() - $exp->getTimestamp()) / 3600);
                $lateFee = Money::mul($hours, Settings::get('late_fee_per_hour', '0'));
            }
        }
        return ['extra_mileage_fee' => $extraKmFee, 'late_return_fee' => $lateFee];
    }
}
