<?php

declare(strict_types=1);

namespace ProphetCore\PostTypes;

use ProphetCore\Paiement\PaiementRepository;
use ProphetCore\Paiement\Statut;

final class RendezVous
{
    public const SLUG = 'rendez_vous';

    public const STATUT_EN_ATTENTE = 'prophet_en_attente';
    public const STATUT_CONFIRME = 'prophet_confirme';
    public const STATUT_ANNULE = 'prophet_annule';

    private const STATUTS = [
        self::STATUT_EN_ATTENTE => 'En attente',
        self::STATUT_CONFIRME => 'Confirmé',
        self::STATUT_ANNULE => 'Annulé',
    ];

    /**
     * Carbon Fields préfixe toute méta de publication d'un `_`, sans possibilité
     * de le désactiver. Toute lecture ou écriture passe par metaKey() : une clé
     * littérale écrite ailleurs créerait un second jeu de données que
     * l'administration n'afficherait pas.
     */
    public const META_PREFIX = '_rdv_';

    public static function metaKey(string $champ): string
    {
        return self::META_PREFIX . $champ;
    }

    public static function register(): void
    {
        add_action('init', static function (): void {
            register_post_type(self::SLUG, [
                'labels' => [
                    'name' => 'Rendez-vous',
                    'singular_name' => 'Rendez-vous',
                    'edit_item' => 'Détail du rendez-vous',
                    'search_items' => 'Rechercher un rendez-vous',
                ],
                'public' => false,
                'show_ui' => true,
                'publicly_queryable' => false,
                'exclude_from_search' => true,
                'menu_icon' => 'dashicons-calendar-alt',
                'supports' => ['title'],
                'capabilities' => ['create_posts' => 'do_not_allow'],
                'map_meta_cap' => true,
            ]);

            foreach (self::STATUTS as $slug => $libelle) {
                register_post_status($slug, [
                    'label' => $libelle,
                    'public' => false,
                    'internal' => false,
                    'protected' => true,
                    'show_in_admin_all_list' => true,
                    'show_in_admin_status_list' => true,
                    'label_count' => _n_noop(
                        $libelle . ' (%s)',
                        $libelle . ' (%s)'
                    ),
                ]);
            }
        });

        add_filter('manage_' . self::SLUG . '_posts_columns', [self::class, 'adminColumns']);
        add_action('manage_' . self::SLUG . '_posts_custom_column', [self::class, 'renderColumn'], 10, 2);

        add_action('restrict_manage_posts', static function (string $postType): void {
            if ($postType !== self::SLUG) {
                return;
            }

            $courant = isset($_GET['paiement']) ? sanitize_text_field(wp_unslash($_GET['paiement'])) : '';

            echo '<select name="paiement"><option value="">Tous les paiements</option>';

            foreach ([Statut::NON_REQUIS, Statut::EN_ATTENTE, Statut::PAYE, Statut::ECHOUE, Statut::ANNULE] as $statut) {
                printf(
                    '<option value="%s"%s>%s</option>',
                    esc_attr($statut),
                    selected($courant, $statut, false),
                    esc_html(Statut::libelle($statut))
                );
            }

            echo '</select>';
        });

        add_action('pre_get_posts', static function ($query): void {
            if (! is_admin() || ! $query->is_main_query()) {
                return;
            }

            if ($query->get('post_type') !== self::SLUG || empty($_GET['paiement'])) {
                return;
            }

            $query->set('meta_query', [[
                'key' => self::metaKey('paiement_statut'),
                'value' => sanitize_text_field(wp_unslash($_GET['paiement'])),
            ]]);
        });
    }

    public static function adminColumns(array $colonnes): array
    {
        return [
            'cb' => $colonnes['cb'] ?? '<input type="checkbox" />',
            'title' => 'Demandeur',
            'rdv_date' => 'Date et heure',
            'rdv_type' => 'Consultation',
            'rdv_statut' => 'Statut',
            'rdv_paiement' => 'Paiement',
        ];
    }

    public static function renderColumn(string $colonne = '', int $postId = 0): void
    {
        switch ($colonne) {
            case 'rdv_date':
                echo esc_html(
                    get_post_meta($postId, self::metaKey('date'), true) . ' — '
                    . get_post_meta($postId, self::metaKey('heure'), true)
                );
                break;
            case 'rdv_type':
                echo esc_html((string) get_post_meta($postId, self::metaKey('type_consultation'), true));
                break;
            case 'rdv_statut':
                echo esc_html(self::STATUTS[get_post_status($postId)] ?? '—');
                break;
            case 'rdv_paiement':
                $paiement = (new PaiementRepository())->donnees($postId);
                echo esc_html(
                    $paiement['montant'] > 0
                        ? Statut::libelle($paiement['statut'])
                            . ' — ' . $paiement['montant'] . ' ' . $paiement['devise']
                        : Statut::libelle($paiement['statut'])
                );
                break;
        }
    }

    public static function statutLabel(string $statut): string
    {
        return self::STATUTS[$statut] ?? $statut;
    }
}
