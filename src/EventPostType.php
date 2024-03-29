<?php

namespace VincentTrotot\Event;

use Timber\Timber;
use Symfony\Component\HttpFoundation\Request;

class EventPostType
{

    protected $slug;

    public function __construct($options = [])
    {
        add_action('init', [$this, 'createPostType']);
        add_action('init', [$this, 'createTaxonomy'], 0);

        add_action('pre_get_posts', [$this, 'defaultOrderBy']);
        add_action('pre_get_posts', [$this, 'customOrderBy']);
        add_filter('manage_edit-vt_events_columns', [$this, 'editColumns']);
        add_action('manage_posts_custom_column', [$this, 'customColumns']);
        add_filter('manage_edit-vt_events_sortable_columns', [$this, 'sortableColumns']);
        add_action('restrict_manage_posts', [$this, 'filterColumns']);
        add_filter('parse_query', [$this, 'sortStartdate']);

        add_action('admin_init', [$this, 'setupMetabox']);

        add_action('save_post', [$this, 'save']);
        add_filter('post_updated_messages', [$this, 'updatedMessages']);


        add_action('admin_print_scripts', [$this, 'enqueueScripts'], 1000);
        add_action('admin_print_scripts-post.php', [$this, 'enqueueScripts'], 1000);
        add_action('admin_print_scripts-post-new.php', [$this, 'enqueueScripts'], 1000);

        add_filter('dashboard_glance_items', [$this, 'customGlanceItems'], 10, 1);

        $this->slug = isset($options['slug']) ? $options['slug'] : 'agenda';

        add_action('admin_bar_menu', [$this, 'add_custom_toolbar'], 90);

        add_filter('query_vars', [$this, 'custom_query_vars']);
        add_action('template_redirect', [$this, 'cancel_event_route']);
    }

    /**
     * Création du PostType  \
     * hook: init
     */
    public function createPostType()
    {
        $labels = [
            'name' => _x('Agenda', 'calendrier'),
            'all_items' => __('Tous les événements'),
            'singular_name' => _x('Événement', 'event'),
            'add_new' => _x('Ajouter un événement', 'events'),
            'add_new_item' => __('Ajouter un événement'),
            'edit_item' => __('Modifier l\'événement'),
            'new_item' => __('Nouvel événement'),
            'view_item' => __('Voir l\'événement'),
            'search_items' => __('Rechercher dans le calendrier'),
            'not_found' =>  __('Pas d\'événement trouvé'),
            'not_found_in_trash' => __('Pas d\'événement trouvé dans la corbeille'),
            'parent_item_colon' => '',
        ];

        $args = [
            'label' => __('Agenda'),
            'labels' => $labels,
            'public' => true,
            'can_export' => true,
            'show_ui' => true,
            'show_in_rest' => true,
            '_builtin' => false,
            '_edit_link' => 'post.php?post=%d',
            'capability_type' => 'post',
            'menu_icon' => 'dashicons-calendar-alt',
            'hierarchical' => false,
            'rewrite' => ['slug' => $this->slug],
            'has_archive' => $this->slug,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'author'],
            'show_in_nav_menus' => true,
            'taxonomies' => ['vt_eventcategory']
        ];

        register_post_type('vt_events', $args);
    }

    /**
     * Création des catégories pour les événements  \
     * hook: init
     */
    public function createTaxonomy()
    {
        $labels = [
            'name' => _x('Type d\'événement', 'vt_eventcategory'),
            'singular_name' => _x('Type d\'événement', 'vt_eventcategory'),
            'search_items' =>  __('Rechercher dans les types d\'événements'),
            'popular_items' => __('Types d\'événements populaires'),
            'all_items' => __('Tous les types d\'événements'),
            'parent_item' => null,
            'parent_item_colon' => null,
            'edit_item' => __('Modifier le type d\'événement'),
            'update_item' => __('Mettre à jour le type d\'événement'),
            'add_new_item' => __('Ajouter un type d\'événement'),
            'new_item_name' => __('Nom du nouveau type d\'événement'),
            'separate_items_with_commas' => __('Séparez les types d\'événements avec des virgules'),
            'add_or_remove_items' => __('Ajouter ou supprimer un type d\'événement'),
            'choose_from_most_used' => __('Choisir parmi les types d\'événements les plus utilisées'),
        ];

        $args = [
            'label' => __('Catégorie d\'événement'),
            'labels' => $labels,
            'hierarchical' => true,
            'show_ui' => true,
            'query_var' => true,
            'show_admin_column'   => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'event-category'],
        ];

        register_taxonomy('vt_eventcategory', 'vt_events', $args);
    }

    /**
     * Tri par défaut des événements : date de début  \
     * hook: pre_get_post
     */
    public function defaultOrderBy($query)
    {
        global $pagenow;
        $request = new Request($_GET);
        $post_type = $request->query->get('post_type', '');
        $orderby = $request->query->get('orderby', false);
        if (
            is_admin()
            && 'edit.php' === $pagenow
            && $post_type === 'vt_events'
            && !$orderby
        ) {
            $query->set('meta_key', 'vt_events_startdate');
            $query->set('orderby', 'meta_value');
            $query->set('order', 'DESC');
        }
    }

    /**
     * Tri possible sur les dates de début des événements  \
     * hook: pre_get_post
     */
    public function customOrderBy($query)
    {
        if (!is_admin()) {
            return;
        }

        $orderby = $query->get('orderby');

        if ('vt_events_startdate' == $orderby) {
            $query->set('meta_key', 'vt_events_startdate');
            $query->set('orderby', 'meta_value_num');
        }
    }

    /**
     * Paramétrage des colonnes triables  \
     * hook: vt_events_sortable_columns
     */
    public function sortableColumns($columns)
    {
        $columns['vt_col_ev_date'] = 'vt_events_startdate';
        return $columns;
    }

    /**
     * Paramétrage des en-têtes du tableau  \
     * hook: manage_edit-vt_events_columns
     */
    public function editColumns($columns)
    {
        $columns = [
            "cb" => "<input type=\"checkbox\" />",
            "title" => "&Eacute;vénement",
            "vt_col_ev_date" => "Dates",
            "vt_col_ev_desc" => "Description",
            "vt_col_ev_cat" => "Catégorie",
            "vt_col_ev_home" => "Sur la page d'accueil",
            "vt_col_ev_author" => "Auteur",
        ];

        return $columns;
    }

    /**
     * Paramétrage des colonnes  \
     * hook: manage_posts_custom_column
     */
    public function customColumns($column)
    {
        global $post;
        $custom = get_post_custom();
        switch ($column) {
            case 'vt_col_ev_cat':
                // - show taxonomy terms -
                $eventcats = get_the_terms($post->ID, "vt_eventcategory");
                $eventcats_html = [];
                if ($eventcats) {
                    foreach ($eventcats as $eventcat) {
                        array_push($eventcats_html, $eventcat->name);
                    }
                    echo implode(", ", $eventcats_html);
                } else {
                    _e('None', 'themeforce');;
                }
                break;

            case 'vt_col_ev_date':
                // - show dates -
                $startd = $custom["vt_events_startdate"][0];
                $endd = $custom["vt_events_enddate"][0];
                $startdate = Event::inFrench("L j f Y", $startd);
                $enddate = Event::inFrench("L j f Y", $endd);

                $startt = $custom["vt_events_startdate"][0];
                $endt = $custom["vt_events_enddate"][0];
                $time_format = get_option('time_format');
                $starttime = Event::inFrench('G\hi', $startt);
                $endtime = Event::inFrench('G\hi', $endt);
                $day = false;

                if ($starttime == '0h') {
                    $day = true;
                }

                echo $startdate;
                if (!$day) {
                    echo " - " . $starttime;
                }
                if ($startdate != $enddate) {
                    echo '<br /><em>' . $enddate;
                    if (!$day) {
                        echo ' - ' . $endtime;
                    }
                    echo '</em>';
                } else {
                    echo ' > ' . $endtime;
                }
                break;

            case 'vt_col_ev_home':
                $home = $custom["vt_events_display_home"][0] ?? false;
                if ($home == true) {
                    echo "<p>✅</p>";
                } else {
                    echo "<p>❌</p>";
                }
                break;

            case 'vt_col_ev_home':
                $cancelled = $custom["vt_events_is_cancelled"][0] ?? false;
                if ($cancelled == true) {
                    echo "<p>❌</p>";
                }
                break;

            case 'vt_col_ev_desc':
                the_excerpt();
                break;

            case 'vt_col_ev_author':
                the_author();
                break;
        }
    }

    /**
     * Ajoute la possibilité de filtrer les événements par date de début ou de fin  \
     * hook: restrict_manage_posts
     */
    public function filterColumns()
    {
        global $typenow;
        if ($typenow == 'vt_events') { // Your custom post type slug
            $request = new Request($_GET);
            $context['after'] = $request->query->get('vt_events_startdate_after', '');
            $context['before'] = $request->query->get('vt_events_startdate_before', '');

            Timber::render('templates/event-filter-columns.html.twig', $context);
        }
    }

    /**
     * Modification de la requête pour le tri par date de début  \
     * hook: parse_query
     */
    public function sortStartdate($query)
    {
        global $pagenow;
        // Get the post type
        $request = new Request($_GET);
        $post_type = $request->query->get('post_type', '');
        $after = $request->query->get('vt_events_startdate_after');
        $before = $request->query->get('vt_events_startdate_before');
        if (!isset($query->query_vars['meta_query'])) {
            $query->query_vars['meta_query'] = [];
        }

        // append to meta_query array
        if (is_admin() && $pagenow == 'edit.php' && $post_type == 'vt_events') {
            // date de début (événements après la date)
            if ($after) {
                $meta = [
                    'key'  =>   'vt_events_startdate',
                    'value' =>   strtotime(str_replace('/', '-', $after) . '00:00'),
                    'compare' => '>'
                ];
                $query->query_vars['meta_query'][] = $meta;
            }

            // date de fin (événements avant la date)
            if ($before) {
                $meta = [
                    'key'  =>   'vt_events_startdate',
                    'value' =>   strtotime(str_replace('/', '-', $before) . '23:59'),
                    'compare' => '<'
                ];
                $query->query_vars['meta_query'][] = $meta;
            }
        }
    }

    /**
     * Paramétrage de la meta box  \
     * hook: admin_init
     */
    public function setupMetabox()
    {
        add_meta_box(
            'vt_events_meta',
            'Date & lieu',
            [$this, 'customMetabox'],
            'vt_events',
            'side',
            'high'
        );
    }

    /**
     * Affichage de la meta box
     */
    public function customMetabox()
    {

        $context['post'] = new Event();
        $context['nonce'] = wp_create_nonce('vt-events-nonce');
        Timber::render('templates/event-meta-box.html.twig', $context);
    }

    /**
     * Enregistre l'événement en base de donnée  \
     * hook: save_post
     */
    public function save()
    {
        global $post;

        if (!isset($_POST['vt-events-nonce'])) {
            return;
        }

        if (
            !wp_verify_nonce($_POST['vt-events-nonce'], 'vt-events-nonce') ||
            !current_user_can('edit_post', $post->ID)
        ) {
            return $post->ID;
        }

        if (
            !isset($_POST["vt_events_startdate"]) ||
            !isset($_POST["vt_events_enddate"]) ||
            !isset($_POST["vt_events_location"]) ||
            !isset($_POST["vt_events_display_home"]) ||
            !isset($_POST["vt_events_is_cancelled"])
        ) {
            //return $post;
        }

        update_post_meta(
            $post->ID,
            "vt_events_startdate",
            strtotime($_POST["vt_events_startdate"])
        );

        update_post_meta(
            $post->ID,
            "vt_events_enddate",
            strtotime($_POST["vt_events_enddate"])
        );

        update_post_meta(
            $post->ID,
            "vt_events_location",
            $_POST["vt_events_location"]
        );

        update_post_meta(
            $post->ID,
            "vt_events_display_home",
            !empty($_POST["vt_events_display_home"])
        );

        update_post_meta(
            $post->ID,
            "vt_events_is_cancelled",
            !empty($_POST["vt_events_is_cancelled"])
        );
    }

    /**
     * Paramétrage des messages de mise à jour  \
     * hook: post_updated_messages
     */
    public function updatedMessages($messages)
    {
        global $post, $post_ID;
        $request = new Request($_GET);
        $revision = $request->query->get('revision');


        $messages['vt_events'] = [
            0 => '', // Unused. Messages start at index 1.
            1 => sprintf(
                __('&Eacute;vénement mis à jour. <a href="%s">Voir l\'événement</a>'),
                esc_url(get_permalink($post_ID))
            ),
            2 => __('Champ mis à jour.'),
            3 => __('Champ supprimé.'),
            4 => __('&Eacute;vénement mis à jour.'),
            /* translators: %s: date and time of the revision */
            5 => $revision ? sprintf(
                __('Event restored to revision from %s'),
                wp_post_revision_title((int) $revision, false)
            ) : false,
            6 => sprintf(
                __('&Eacute;vénement publié. <a href="%s">Voir l\'événement</a>'),
                esc_url(get_permalink($post_ID))
            ),
            7 => __('&Eacute;vénement sauvegardé.'),
            8 => sprintf(
                __('&Eacute;vénement soumis. <a target="_blank" href="%s">Prévisualiser l\'événement</a>'),
                esc_url(add_query_arg('preview', 'true', get_permalink($post_ID)))
            ),
            9 => sprintf(
                __(
                    '&Eacute;vénement programmé pour : '
                        . '<strong>%1$s</strong>. '
                        . '<a target="_blank" href="%2$s">Prévisualiser l\'événement</a>'
                ),
                date_i18n(__('M j, Y @ G:i'), strtotime($post->post_date)),
                esc_url(get_permalink($post_ID))
            ),
            10 => sprintf(
                __('Brouillon de l\'événement mis à jour. <a target="_blank" href="%s">Prévisualiser l\'événement</a>'),
                esc_url(add_query_arg('preview', 'true', get_permalink($post_ID)))
            ),
        ];

        return $messages;
    }

    /**
     * Ajoute les scripts nécessaires  \
     * hook: admin_print_scripts  \
     * hook: admin_print_scripts-post.php  \
     * hook: admin_print_scripts-post-new.php  \
     */
    public function enqueueScripts()
    {
        global $post_type;
        if ('vt_events' != $post_type) {
            return;
        }
        wp_enqueue_script('custom_script', str_replace(ABSPATH, '/', __DIR__) . '/js/vt_events.js');
    }

    /**
     * Ajoute les événements au panneaux D'un coup d'oeil  \
     * hook: dashboard_glance_items
     */
    public function customGlanceItems($items = [])
    {
        $post_types = ['vt_events'];
        foreach ($post_types as $type) {
            if (!post_type_exists($type)) {
                continue;
            }
            $num_posts = (new Event())->getNbFutureEvents();
            if ($num_posts) {
                $post_type = get_post_type_object($type);
                $text = strtolower(_n('%s événement', '%s événements', $num_posts, 'your_textdomain'));
                $text = sprintf($text, number_format_i18n($num_posts)) . ' à venir';
                if (current_user_can($post_type->cap->edit_posts)) {
                    $output =
                        '<a href="edit.php?post_type='
                        . $post_type->name
                        . '&vt_events_startdate_after='
                        . date('d')
                        . '%2F'
                        . date('m')
                        . '%2F'
                        . date('Y')
                        . '" >'
                        . $text
                        . '</a>';
                    echo '<li class="post-count ' . $post_type->name . '-count">' . $output . '</li>';
                } else {
                    $output = '<span>' . $text . '</span>';
                    echo '<li class="post-count ' . $post_type->name . '-count">' . $output . '</li>';
                }
            }
        }
        return $items;
    }

    /**
     * Ajoute un bouton dans la barre admin de wordpress pour annuler ou maintenir un événement \
     * hook: admin_bar_menu
     */
    function add_custom_toolbar($admin_bar)
    {
        wp_reset_postdata();
        global $post;

        //Si on est pas sur un post ni sur un vt_events, on ne fait rien
        if ($post == null) return;
        if ($post->post_type !== "vt_events") return;

        $is_cancelled = (bool)get_post_meta($post->ID, 'vt_events_is_cancelled')[0];
        $title =
            $is_cancelled ?
            '<span class="ab-icon dashicons dashicons-yes-alt" style="top:3px"></span>Maintenir' :
            '<span class="ab-icon dashicons dashicons-dismiss" style="top:3px"></span>Annuler';
        if ($post->post_type == 'vt_events') {
            $admin_bar->add_menu(array(
                'id'    => 'cancel-event',
                'title' => $title,
                'href'  => esc_url(site_url() . '?&cancel_event_with_id=' . $post->ID),
            ));
        }
    }


    /**
     * Ajoute un parametre GET custom \
     * hook: query_vars
     */
    function custom_query_vars($vars)
    {
        // id du post à annuler / maintenir
        $vars[] = 'cancel_event_with_id';

        //redirection vers une route spécifique ?
        // 'archive' | null
        $vars[] = 'cancel_event_redirect_route';
        return $vars;
    }

    /**
     * Pseudo middleware pour capter le parametre GET 'cancel_event_with_id' \
     * et greffer un comportement pour annuler ou maintenir l'événement \
     * hook: template_redirect
     */
    function cancel_event_route()
    {

        // vérification que le paramètre soit bien présent
        $query = get_query_var('cancel_event_with_id');
        if ($query == "") return;

        // vérification que le post soit bien un vt_events
        // ou que l'utilisateur puissent bien annuler l'événement
        $post = get_post((int)$query);
        $post_permalink = get_post_permalink($post);
        if ($post->post_type !== 'vt_events' || !current_user_can('manage_options')) wp_redirect($post_permalink);

        // mise à jour du post meta pour maintenir ou annuler l'événement
        $is_cancelled = (bool)get_post_meta($post->ID, 'vt_events_is_cancelled')[0];
        update_post_meta(
            $post->ID,
            "vt_events_is_cancelled",
            !$is_cancelled
        );

        // force un post update pour invalider un éventuel cache
        get_post($post);
        wp_update_post($post);

        // si une redirection est demandée, on la fait
        $redirect = get_query_var('cancel_event_redirect_route');
        if ($redirect == "archive") wp_redirect(get_post_type_archive_link($post->post_type));
        // sinon, on redirige vers le post
        else wp_redirect($post_permalink);
    }
}
