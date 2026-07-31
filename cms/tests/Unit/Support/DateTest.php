<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Support;

use DateTimeImmutable;
use DateTimeZone;
use ProphetCore\Support\Date;
use ProphetCore\Tests\TestCase;

final class DateTest extends TestCase
{
    public function test_format_fr_reproduit_intl_datetimeformat_du_front(): void
    {
        $date = new DateTimeImmutable('2026-07-30 10:00:00', new DateTimeZone('UTC'));

        $this->assertSame('jeudi 30 juillet 2026', Date::formatFr($date));
    }

    public function test_format_fr_gere_un_mois_accentue(): void
    {
        $date = new DateTimeImmutable('2026-02-01 10:00:00', new DateTimeZone('UTC'));

        $this->assertSame('dimanche 1 février 2026', Date::formatFr($date));
    }

    public function test_mois_abrege_suit_la_table_du_bridge(): void
    {
        $this->assertSame('Jan', Date::monthAbbrFr(0));
        $this->assertSame('Fév', Date::monthAbbrFr(1));
        $this->assertSame('Juil', Date::monthAbbrFr(6));
        $this->assertSame('Déc', Date::monthAbbrFr(11));
    }

    public function test_heure_pleine_et_heure_avec_minutes(): void
    {
        $tz = new DateTimeZone('UTC');

        $this->assertSame('20h00', Date::formatTime(new DateTimeImmutable('2026-04-18 20:00:00', $tz)));
        $this->assertSame('09h30', Date::formatTime(new DateTimeImmutable('2026-04-18 09:30:00', $tz)));
    }
}
