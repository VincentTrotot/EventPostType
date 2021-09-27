<?php

namespace VincentTrotot\Event;

use Timber\Post;
use Timber\Timber;
use Timber\PostQuery;

class SportEvent extends Event
{
    public $club;
    public $outdated;

    public function __construct($pid = null)
    {
        parent::__construct($pid);

        $this->outdated = '';
        if (in_array('Club', $this->terms('vt_eventcategory'))) {
            $this->class = 'club';
        } else {
            $this->class = 'not-club';
        }
    }

    public function checkIfOutdated()
    {
       
        $now = current_datetime()->getTimestamp() + current_datetime()->getOffset();

        if ($this->end < $now && $this->isInCurrentSeason()) {
            $this->outdated = 'outdated';
        }
    }

    public function isInCurrentSeason()
    {
        $saison = SportEvent::getCurrentSeason();
        if (date('m', $this->start) < 9) {
            return date('Y', $this->start) - 1 == $saison;
        } else {
            return date('Y', $this->start) == $saison;
        }
    }


    public static function getPagination($id)
    {
        //on récupère les vt_events ordonnés par date
        $events = SportEvent::getAllEvents();
        $cpt = 0;
        $evts = [];
        $current = null;
        
        if ($events->found_posts) {
            foreach ($events->get_posts() as $post) {
                $evts[$cpt] = $post->id;
                if ($post->id === $id) {
                    $current = $cpt;
                }
                $cpt++;
            }
        }
        
        // s'il n'a pas été trouvé, on quitte la fonction
        if ($current === null) {
            return;
        }

        $context = [
            'previous' => $current + 1 < count($evts) ? new Post($evts[$current + 1]) : null,
            'next' => $current - 1 >= 0 ? new Post($evts[$current - 1]) : null
        ];

        Timber::render('layout/_post-pagination.html.twig', $context);
    }

    public function getNextEvents(int $nb, $exclude = false, $onHome = false) : PostQuery
    {
        $now = current_datetime()->getTimestamp() + current_datetime()->getOffset();
        return new PostQuery([
            'post_type' => 'vt_events',
            'posts_per_page' => $nb,
            'orderby' => 'meta_value',
            'meta_query' => [
                [
                    'key' => 'vt_events_enddate',
                    'value' => strtotime(date("Ymd", $now)), // on affiche les événements jusqu'à la fin de la journée
                    'compare' => '>',
                ],
            ],
            'order' => 'ASC',
        ], SportEvent::class);
    }

    public static function getAllEvents()
    {
        return new PostQuery([
            'post_type' => 'vt_events',
            'posts_per_page' => -1,
            'orderby' => 'meta_value',
            'meta_key' => 'vt_events_startdate',
            'order' => 'ASC',
        ], SportEvent::class);
    }

    public static function getSaisonEvents($saison)
    {
        $debut_saison = strtotime('01-09-'.$saison);
        $fin_saison = strtotime('31-08-'.($saison + 1));
    
        $args = [
        'post_type' => 'vt_events',
        'posts_per_page' => -1,
        'orderby' => 'meta_value',
        'meta_query' => [
                [
                    'key' => 'vt_events_startdate',
                    'value' => $debut_saison,
                    'compare' => '>',
                ],
                [
                    'key' => 'vt_events_startdate',
                    'value' => $fin_saison,
                    'compare' => '<',
                ],
            ],
            'order' => 'ASC',
        ];
        return new PostQuery($args, SportEvent::class);
    }

    /**
     * Retourne un tableau ordoné avec les années de début de chaque saison ayant au moins un événement
     */
    public static function getSaisons() : array
    {
        $results = $GLOBALS['wpdb']->get_results(
            "SELECT DISTINCT YEAR(FROM_UNIXTIME(meta_value)) AS 'MY_YEAR', MONTH(FROM_UNIXTIME(meta_value)) AS 'MY_MONTH'
            FROM {$GLOBALS['wpdb']->prefix}postmeta
            WHERE meta_key='vt_events_startdate'
            ORDER BY MY_YEAR ASC",
            ARRAY_N
        );

        $saisons = [];
        foreach ($results as $saison) {
            if ($saison[1] > 8 && !in_array($saison[0], $saisons)) {
                $saisons[] = $saison[0];
            } else if($saison[1] <= 8 && !in_array(((int) $saison[0] - 1), $saisons)) {
                $saisons[] = (int) $saison[0] - 1;
            }

        }
        return $saisons;
    }

     /**
     * Retourne l'année de début de la saison en cours
     * Du 01/09/N au 31/08/N+1, l'année de début de saison est N
     */
    public static function getCurrentSeason() : string
    {
        $now = current_datetime()->getTimestamp() + current_datetime()->getOffset();
        if (date('m', $now) < 9) {
            return date('Y', $now) - 1;
        } else {
            return date('Y', $now);
        }
    }
}
