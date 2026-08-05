<?php

declare(strict_types=1);

namespace ProphetCore\Don;

use ProphetCore\Paiement\PaiementRepository;
use ProphetCore\Paiement\Statut;
use ProphetCore\PostTypes\Don;

/**
 * C'est un trésorier qui ouvrira ce fichier dans un tableur, jamais dans la
 * base : l'ordre des colonnes est stable (le test le fige) et le flux
 * commence par un BOM UTF-8, sans quoi Excel affiche les accents en
 * mojibake — la différence entre un export utilisable et un appel au support.
 *
 * La construction des lignes (lignes()) reste pure et testable ; le
 * streaming (telecharger()) est séparé, car phpunit.xml impose
 * failOnWarning et beStrictAboutOutputDuringTests : un export qui écrirait
 * directement sur la sortie ferait échouer la suite dès qu'on le teste
 * naïvement.
 */
final class ExportCsv
{
    public const ACTION = 'prophet_export_dons';
    public const NONCE = 'prophet_export_dons_nonce';

    private const ENTETE = [
        'Date', 'Motif', 'Montant', 'Devise', 'Prénom', 'Nom', 'Email', 'Téléphone', 'Statut', 'Référence',
    ];

    public static function register(): void
    {
        add_action('admin_post_' . self::ACTION, [self::class, 'telecharger']);
    }

    /**
     * @param array<int, array<string, mixed>> $dons
     * @return array<int, array<int, int|string>>
     */
    public static function lignes(array $dons): array
    {
        $lignes = [self::ENTETE];

        foreach ($dons as $don) {
            $lignes[] = [
                (string) ($don['date'] ?? ''),
                self::protegerFormule((string) ($don['motif_titre'] ?? '')),
                (int) ($don['montant'] ?? 0),
                (string) ($don['devise'] ?? ''),
                self::protegerFormule((string) ($don['prenom'] ?? '')),
                self::protegerFormule((string) ($don['nom'] ?? '')),
                self::protegerFormule((string) ($don['email'] ?? '')),
                self::protegerFormule((string) ($don['telephone'] ?? '')),
                Statut::libelle((string) ($don['statut_paiement'] ?? '')),
                (string) ($don['ref'] ?? ''),
            ];
        }

        return $lignes;
    }

    /**
     * sanitize_text_field() laisse passer =, +, -, @ : un tableur qui ouvre
     * le CSV interprète une cellule commençant par l'un de ces caractères
     * (ou par une tabulation/un retour chariot, mêmes déclencheurs dans
     * Excel) comme une formule. Un prénom `=HYPERLINK(...)` l'exécuterait
     * chez le trésorier. Préfixer d'une apostrophe neutralise
     * l'interprétation sans changer la valeur affichée.
     */
    private static function protegerFormule(string $valeur): string
    {
        return preg_match('/^[=+\-@\t\r]/', $valeur) === 1 ? "'" . $valeur : $valeur;
    }

    /**
     * Le dump contient noms, emails et téléphones des donateurs : capacité et
     * nonce sont non négociables, un visiteur non connecté ne doit jamais
     * pouvoir l'atteindre.
     */
    /**
     * manage_options, pas edit_posts : edit_posts est accordé aux
     * Contributeurs par défaut, qui pourraient sinon télécharger la liste
     * complète des donateurs (nom, email, téléphone).
     */
    public static function telecharger(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die('Action non autorisée.', 'Accès refusé', ['response' => 403]);
        }

        check_admin_referer(self::ACTION, self::NONCE);

        $lignes = self::lignes(self::recupererDons());

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="dons-' . gmdate('Y-m-d-His') . '.csv"');

        $flux = fopen('php://output', 'w');
        // BOM UTF-8 : sans lui, Excel ouvre « Dîmes » en « DÃ®mes ».
        fwrite($flux, "\xEF\xBB\xBF");

        foreach ($lignes as $ligne) {
            fputcsv($flux, $ligne, ';');
        }

        fclose($flux);
        exit;
    }

    /**
     * Respecte les mêmes filtres motif / statut que la liste des dons : l'export
     * correspond à ce que le trésorier a sous les yeux, pas à la table entière.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function recupererDons(): array
    {
        $args = [
            'post_type' => Don::SLUG,
            'post_status' => 'any',
            'posts_per_page' => -1,
            'no_found_rows' => true,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $metaQuery = Don::filtresMetaQuery();

        if ($metaQuery !== []) {
            $args['meta_query'] = $metaQuery;
        }

        $paiements = new PaiementRepository(Don::metaKey(''), Don::SLUG);

        return array_map(static function ($post) use ($paiements): array {
            $postId = (int) $post->ID;
            $lire = static fn (string $champ): string => (string) get_post_meta($postId, Don::metaKey($champ), true);

            return [
                'date' => get_the_date('Y-m-d H:i:s', $postId),
                'motif_titre' => $lire('motif_titre'),
                'montant' => (int) $lire('montant'),
                'devise' => $lire('devise'),
                'prenom' => $lire('prenom'),
                'nom' => $lire('nom'),
                'email' => $lire('email'),
                'telephone' => $lire('telephone'),
                'statut_paiement' => $paiements->statut($postId),
                'ref' => $lire('ref'),
            ];
        }, get_posts($args));
    }
}
