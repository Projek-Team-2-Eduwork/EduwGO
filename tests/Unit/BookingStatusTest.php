<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BookingStatusTest extends TestCase
{
    /**
     * Semua transisi yang sah sesuai state machine (SPEC §6).
     * Dipakai juga oleh BookingServiceTransitionTest.
     */
    public static function validTransitions(): array
    {
        return [
            'pending → paid' => [BookingStatus::Pending, BookingStatus::Paid],
            'pending → expired' => [BookingStatus::Pending, BookingStatus::Expired],
            'pending → cancelled' => [BookingStatus::Pending, BookingStatus::Cancelled],
            'paid → rented' => [BookingStatus::Paid, BookingStatus::Rented],
            'paid → cancelled' => [BookingStatus::Paid, BookingStatus::Cancelled],
            'rented → returned' => [BookingStatus::Rented, BookingStatus::Returned],
        ];
    }

    /**
     * Semua kombinasi6×5 kurang daftar valid di atas = transisi terlarang.
     */
    public static function invalidTransitions(): array
    {
        $validKeys = array_map(
            static fn (array $pair): string => $pair[0]->value.'→'.$pair[1]->value,
            self::validTransitions(),
        );

        $invalid = [];
        foreach (BookingStatus::cases() as $from) {
            foreach (BookingStatus::cases() as $to) {
                $key = $from->value.'→'.$to->value;
                if (! in_array($key, $validKeys, true)) {
                    $invalid[$key] = [$from, $to];
                }
            }
        }

        return $invalid;
    }

    public function test_label_and_color_returns_correct_values(): void
    {
        $this->assertSame('Lunas', BookingStatus::from('paid')->label());
        $this->assertSame('success', BookingStatus::Paid->color());
        $this->assertSame('Pending', BookingStatus::Pending->label());
        $this->assertSame('Sedang disewa', BookingStatus::Rented->label());
        $this->assertSame('Sudah kembali', BookingStatus::Returned->label());
        $this->assertSame('Batal', BookingStatus::Cancelled->label());
        $this->assertSame('Expired', BookingStatus::Expired->label());
    }

    #[DataProvider('validTransitions')]
    public function test_all_valid_transitions_are_allowed(BookingStatus $from, BookingStatus $to): void
    {
        $this->assertTrue($from->canTransitionTo($to));
        $this->assertContains($to, $from->allowedTransitions());
    }

    #[DataProvider('invalidTransitions')]
    public function test_all_invalid_transitions_are_rejected(BookingStatus $from, BookingStatus $to): void
    {
        $this->assertFalse($from->canTransitionTo($to));
        $this->assertNotContains($to, $from->allowedTransitions());
    }

    public function test_final_statuses_have_no_outgoing_transitions(): void
    {
        foreach ([BookingStatus::Returned, BookingStatus::Cancelled, BookingStatus::Expired] as $final) {
            $this->assertSame([], $final->allowedTransitions());
        }
    }

    public function test_is_active(): void
    {
        $this->assertTrue(BookingStatus::Pending->isActive());
        $this->assertTrue(BookingStatus::Paid->isActive());
        $this->assertTrue(BookingStatus::Rented->isActive());

        $this->assertFalse(BookingStatus::Returned->isActive());
        $this->assertFalse(BookingStatus::Cancelled->isActive());
        $this->assertFalse(BookingStatus::Expired->isActive());
    }
}
