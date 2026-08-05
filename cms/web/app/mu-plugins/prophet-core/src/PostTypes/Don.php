<?php

declare(strict_types=1);

namespace ProphetCore\PostTypes;

use ProphetCore\Don\ExportCsv;
use ProphetCore\Options;
use ProphetCore\Paiement\PaiementRepository;
use ProphetCore\Paiement\Statut;

final class Don
{
    public const SLUG = 'don';
    public const META_PREFIX = '_don_';

    public const STATUT_EN_ATTENTE = 'don_en_attente';
    public const STATUT_RECU = 'don_recu';
    public const STATUT_ECHOUE = 'don_echoue';

    private const STATUTS = [
        self::STATUT_EN_ATTENTE => 'En attente',
        self::STATUT_RECU => 'Reçu',
        self::STATUT_ECHOUE => 'Échoué',
    ];

    public static function metaKey(string $champ): string
    {
        return self::META_PREFIX . $champ;
    }

    public static function statutLabel(string $statut): string
    {
        return self::STATUTS[$statut] ?? $statut;
    }

    public static function register(): void
    {
        add_action('init', static function (): void {
            register_post_type(self::SLUG, [
                'labels' => [
                    'name' => 'Dons',
                    'singular_name' => 'Don',
                    'edit_item' => 'Détail du don',
                    'search_items' => 'Rechercher un don',
                ],
                'public' => false,
                'show_ui' => true,
                'publicly_queryable' => false,
                'exclude_from_search' => true,
                'menu_icon' => 'dashicons-heart',
                'supports' => ['title'],
                // edit_posts est accordé aux Contributeurs par défaut : sans
                // ces capacités explicites, la liste des dons (nom, email,
                // téléphone de chaque donateur) leur restait lisible.
                // manage_options est réservé à l'Administrateur.
                'capabilities' => [
                    'create_posts' => 'do_not_allow',
                    'edit_post' => 'manage_options',
                    'read_post' => 'manage_options',
                    'delete_post' => 'manage_options',
                    'edit_posts' => 'manage_options',
                    'edit_others_posts' => 'manage_options',
                    'publish_posts' => 'manage_options',
                    'read_private_posts' => 'manage_options',
                    'delete_posts' => 'manage_options',
                ],
                'map_meta_cap' => true,
                // register_post_type() active la réécriture par défaut (même
                // quand public=false) avec le nom du type comme slug — ici
                // « don », identique au slug explicite du CPT public
                // motif_paiement (rewrite/URL partageable). Les deux généraient
                // alors la même règle de réécriture, et celle posée en dernier
                // (ce type-ci) écrasait silencieusement celle de
                // motif_paiement dans la table fusionnée : le lien profond
                // /don/<slug>/ ne résolvait plus le motif mais retombait sur
                // la page d'accueil. Ce type n'a de toute façon aucune raison
                // d'être visité par URL : privé, non interrogeable.
                'rewrite' => false,
            ]);

            foreach (self::STATUTS as $slug => $libelle) {
                register_post_status($slug, [
                    'label' => $libelle,
                    'public' => false,
                    'internal' => false,
                    'protected' => true,
                    'show_in_admin_all_list' => true,
                    'show_in_admin_status_list' => true,
                    'label_count' => _n_noop($libelle . ' (%s)', $libelle . ' (%s)'),
                ]);
            }
        });

        add_filter('manage_' . self::SLUG . '_posts_columns', [self::class, 'adminColumns']);
        add_action('manage_' . self::SLUG . '_posts_custom_column', [self::class, 'renderColumn'], 10, 2);

        add_action('restrict_manage_posts', static function (string $postType): void {
            if ($postType !== self::SLUG) {
                return;
            }

            self::renderFiltreMotif();
            self::renderFiltreStatut();
        });

        add_action('pre_get_posts', static function ($query): void {
            if (! is_admin() || ! $query->is_main_query() || $query->get('post_type') !== self::SLUG) {
                return;
            }

            $metaQuery = self::filtresMetaQuery();

            if ($metaQuery !== []) {
                $query->set('meta_query', $metaQuery);
            }
        });

        add_action('manage_posts_extra_tablenav', static function (string $which): void {
            global $typenow;

            if ($which !== 'top' || $typenow !== self::SLUG) {
                return;
            }

            self::renderTotalEtExport();
        });

        // Les trois chemins qui règlent un paiement (retour navigateur,
        // webhook, changement manuel dans l'admin) écrivent tous la méta
        // `_don_paiement_statut` par le même appel WordPress
        // (update_post_meta()/carbon_set_post_meta() finissent tous deux dans
        // update_metadata()) : observer ce point d'écriture unique, plutôt que
        // de dupliquer la synchronisation dans chacun des trois appelants,
        // garantit qu'aucun chemin ne peut l'oublier. `added_post_meta` couvre
        // la toute première écriture (après initialisation du paiement),
        // `updated_post_meta` couvre les suivantes.
        add_action('updated_post_meta', [self::class, 'synchroniserStatutDepuisPaiement'], 10, 4);
        add_action('added_post_meta', [self::class, 'synchroniserStatutDepuisPaiement'], 10, 4);
    }

    /**
     * Fait suivre au `post_status` natif le statut de paiement, pour que les
     * compteurs natifs WordPress (« Reçu (n) », « Échoué (n) ») en haut de la
     * liste reflètent enfin la réalité — jusqu'ici seule la colonne
     * personnalisée (méta) la reflétait.
     *
     * Idempotent par construction : si le statut natif porte déjà la cible,
     * aucun appel à wp_update_post() n'est fait. Combiné au fait que
     * PaiementRepository::appliquerStatut() n'écrit la méta que lorsque le
     * statut n'est pas déjà final, un statut déjà réglé ne redéclenche jamais
     * cette transition — la garantie « idempotent et monotone » du paiement
     * n'est donc jamais contournée ici.
     *
     * @param int|string $metaId  fourni par les hooks WordPress, non utilisé
     * @param mixed      $metaValue
     */
    public static function synchroniserStatutDepuisPaiement($metaId, int $postId, string $metaKey, $metaValue): void
    {
        if ($metaKey !== self::metaKey('paiement_statut') || get_post_type($postId) !== self::SLUG) {
            return;
        }

        $cible = match ((string) $metaValue) {
            Statut::PAYE => self::STATUT_RECU,
            Statut::ECHOUE, Statut::ANNULE => self::STATUT_ECHOUE,
            default => null,
        };

        if ($cible === null || get_post_status($postId) === $cible) {
            return;
        }

        wp_update_post(['ID' => $postId, 'post_status' => $cible]);
    }

    public static function adminColumns(array $colonnes): array
    {
        return [
            'cb' => $colonnes['cb'] ?? '<input type="checkbox" />',
            'title' => 'Donateur',
            'don_motif' => 'Motif',
            'don_montant' => 'Montant',
            'don_paiement' => 'Statut',
            'don_date' => 'Date',
        ];
    }

    public static function renderColumn(string $colonne = '', int $postId = 0): void
    {
        switch ($colonne) {
            case 'don_motif':
                echo esc_html((string) get_post_meta($postId, self::metaKey('motif_titre'), true));
                break;
            case 'don_montant':
                echo esc_html(
                    get_post_meta($postId, self::metaKey('montant'), true) . ' '
                    . get_post_meta($postId, self::metaKey('devise'), true)
                );
                break;
            case 'don_paiement':
                $statut = (new PaiementRepository(self::metaKey(''), self::SLUG))->statut($postId);
                echo esc_html(Statut::libelle($statut));
                break;
            case 'don_date':
                echo esc_html(get_the_date('d/m/Y H:i', $postId));
                break;
        }
    }

    /**
     * Construit le meta_query commun à la liste des dons et à l'export CSV, à
     * partir des filtres actifs dans l'URL : les deux doivent toujours
     * montrer le même périmètre.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function filtresMetaQuery(): array
    {
        $metaQuery = [];

        if (! empty($_GET['motif'])) {
            $metaQuery[] = ['key' => self::metaKey('motif'), 'value' => (int) $_GET['motif']];
        }

        if (! empty($_GET['statut_paiement'])) {
            $metaQuery[] = [
                'key' => self::metaKey('paiement_statut'),
                'value' => sanitize_text_field(wp_unslash($_GET['statut_paiement'])),
            ];
        }

        return $metaQuery;
    }

    /**
     * Somme des montants effectivement encaissés (paiement_statut = paye)
     * dans le périmètre visible par le trésorier — les filtres actifs, pas
     * la table entière. Un don en attente ou échoué n'est pas de l'argent
     * reçu : le compter gonflerait un total censé être fiable.
     */
    public static function totalPeriodeVisible(): int
    {
        return array_sum(array_column(self::totauxParMotif(), 'total'));
    }

    /**
     * Regroupe les dons payés par motif — « Total encaissé par motif »
     * (spec) : un trésorier qui reconcilie un mois veut distinguer les
     * dîmes des offrandes, pas une seule somme opaque. Un filtre motif actif
     * restreint naturellement le résultat à ce seul motif.
     *
     * fields => 'ids' court-circuite l'amorçage automatique du cache de méta
     * que WP_Query fait pour des objets complets (_prime_post_caches() dans
     * WP_Query::get_posts(), sauté quand 'fields' vaut 'ids') : sans l'appel
     * explicite ci-dessous, chaque tour de la boucle de regroupement
     * interrogerait la base séparément pour lire motif/motif_titre/montant —
     * invisible avec une poignée de dons, sensible à deux cents lors d'un
     * culte, et cette liste tourne à chaque chargement de page admin.
     *
     * @return array<int, array{motif_id: int, titre: string, total: int}>
     */
    public static function totauxParMotif(): array
    {
        $args = [
            'post_type' => self::SLUG,
            'post_status' => 'any',
            'posts_per_page' => -1,
            'no_found_rows' => true,
            'fields' => 'ids',
        ];

        $metaQuery = self::filtresMetaQuery();
        $metaQuery[] = ['key' => self::metaKey('paiement_statut'), 'value' => Statut::PAYE];

        if (count($metaQuery) > 1) {
            $metaQuery['relation'] = 'AND';
        }

        $args['meta_query'] = $metaQuery;

        $ids = array_map('intval', get_posts($args));

        _prime_post_caches($ids, false, true);

        $totaux = [];

        foreach ($ids as $id) {
            $motifId = (int) get_post_meta($id, self::metaKey('motif'), true);
            $titre = (string) get_post_meta($id, self::metaKey('motif_titre'), true);
            $montant = (int) get_post_meta($id, self::metaKey('montant'), true);

            if (! isset($totaux[$motifId])) {
                $totaux[$motifId] = ['motif_id' => $motifId, 'titre' => $titre, 'total' => 0];
            }

            $totaux[$motifId]['total'] += $montant;
        }

        return array_values($totaux);
    }

    private static function renderFiltreMotif(): void
    {
        $motifs = get_posts([
            'post_type' => MotifPaiement::SLUG,
            'post_status' => 'any',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'no_found_rows' => true,
        ]);

        $courant = isset($_GET['motif']) ? (int) $_GET['motif'] : 0;

        echo '<select name="motif"><option value="">Tous les motifs</option>';

        foreach ($motifs as $motif) {
            printf(
                '<option value="%d"%s>%s</option>',
                (int) $motif->ID,
                selected($courant, (int) $motif->ID, false),
                esc_html($motif->post_title)
            );
        }

        echo '</select>';
    }

    private static function renderFiltreStatut(): void
    {
        $courant = isset($_GET['statut_paiement']) ? sanitize_text_field(wp_unslash($_GET['statut_paiement'])) : '';

        echo '<select name="statut_paiement"><option value="">Tous les statuts</option>';

        foreach ([Statut::EN_ATTENTE, Statut::PAYE, Statut::ECHOUE, Statut::ANNULE] as $statut) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($statut),
                selected($courant, $statut, false),
                esc_html(Statut::libelle($statut))
            );
        }

        echo '</select>';
    }

    private static function renderTotalEtExport(): void
    {
        $urlExport = wp_nonce_url(
            add_query_arg(
                array_filter([
                    'action' => ExportCsv::ACTION,
                    'motif' => isset($_GET['motif']) ? (int) $_GET['motif'] : '',
                    'statut_paiement' => isset($_GET['statut_paiement'])
                        ? sanitize_text_field(wp_unslash($_GET['statut_paiement']))
                        : '',
                ]),
                admin_url('admin-post.php')
            ),
            ExportCsv::ACTION,
            ExportCsv::NONCE
        );

        printf(
            '<div class="alignleft actions don-total-encaisse">'
            . '<strong>%s</strong>'
            . ' — <a href="%s" class="button">Exporter en CSV</a>'
            . '</div>',
            self::libelleTotal(),
            esc_url($urlExport)
        );
    }

    /**
     * Filtre motif actif : le total de ce seul motif. Sinon : une
     * répartition par motif, plus le total général — c'est la question du
     * trésorier (« combien de dîmes ce mois-ci ? ») qui pilote l'affichage,
     * pas une seule somme qui la noie.
     */
    private static function libelleTotal(): string
    {
        $totaux = self::totauxParMotif();
        $devise = esc_html(Options::devise());

        if (! empty($_GET['motif'])) {
            $ligne = $totaux[0] ?? ['titre' => '', 'total' => 0];

            return sprintf(
                'Total encaissé — %s : %s %s',
                esc_html($ligne['titre'] !== '' ? $ligne['titre'] : 'Motif'),
                esc_html(number_format_i18n($ligne['total'])),
                $devise
            );
        }

        if ($totaux === []) {
            return sprintf('Total encaissé (période visible) : %s %s', esc_html(number_format_i18n(0)), $devise);
        }

        $parties = array_map(
            static fn (array $ligne): string => sprintf(
                '%s : %s %s',
                esc_html($ligne['titre'] !== '' ? $ligne['titre'] : 'Sans motif'),
                esc_html(number_format_i18n($ligne['total'])),
                $devise
            ),
            $totaux
        );

        $grandTotal = array_sum(array_column($totaux, 'total'));

        return 'Total encaissé par motif — ' . implode(' · ', $parties)
            . sprintf(' · Total général : %s %s', esc_html(number_format_i18n($grandTotal)), $devise);
    }
}
