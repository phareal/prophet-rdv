<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Cli;

use ProphetCore\Cli\SeedCommand;
use ProphetCore\Tests\TestCase;

final class SeedCommandTest extends TestCase
{
    public function test_les_douze_services_du_site_sont_presents(): void
    {
        $services = SeedCommand::services();

        $this->assertCount(12, $services);
        $this->assertSame('Mariage', $services[0]['titre']);
        $this->assertSame('heart', $services[0]['icone']);
        $this->assertSame('#c2185b', $services[0]['couleur']);
        $this->assertSame('Année 2026', $services[11]['titre']);
    }

    public function test_les_six_temoignages_du_site_sont_presents(): void
    {
        $temoignages = SeedCommand::temoignages();

        $this->assertCount(6, $temoignages);
        $this->assertSame('Marie-Claire D.', $temoignages[0]['nom']);
        $this->assertSame('MC', $temoignages[0]['initiales']);
        $this->assertSame("Côte d'Ivoire", $temoignages[0]['pays']);
    }

    public function test_les_quatre_chiffres_cles_du_site_sont_presents(): void
    {
        $stats = SeedCommand::stats();

        $this->assertSame(
            [
                ['valeur' => '15+', 'libelle' => 'Ans de ministère'],
                ['valeur' => '40+', 'libelle' => 'Nations touchées'],
                ['valeur' => '10K+', 'libelle' => 'Consultations'],
                ['valeur' => '100%', 'libelle' => 'Parole accomplie'],
            ],
            $stats
        );
    }

    public function test_chaque_service_a_une_icone_et_une_description(): void
    {
        foreach (SeedCommand::services() as $service) {
            $this->assertNotSame('', $service['icone'], $service['titre']);
            $this->assertNotSame('', $service['description'], $service['titre']);
        }
    }
}
