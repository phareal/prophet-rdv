<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Support;

use ProphetCore\Support\Journal;
use ProphetCore\Tests\TestCase;

final class JournalTest extends TestCase
{
    /**
     * Une référence (don_ref/ref) est un jeton de 32 caractères qui ouvre la
     * page de retour d'un visiteur — pas une donnée personnelle, mais un
     * jeton exploitable. Huit caractères suffisent à corréler les lignes
     * d'un même incident dans les journaux sans y faire figurer le jeton
     * complet.
     */
    public function test_seuls_les_huit_premiers_caracteres_sont_conserves(): void
    {
        $this->assertSame(
            'ABCDEFGH',
            Journal::tronquerReference('ABCDEFGH1234567890IJKLMNOPQRSTUV')
        );
    }

    public function test_une_reference_plus_courte_que_huit_caracteres_est_inchangee(): void
    {
        $this->assertSame('ABC', Journal::tronquerReference('ABC'));
    }

    public function test_une_reference_vide_reste_vide(): void
    {
        $this->assertSame('', Journal::tronquerReference(''));
    }
}
