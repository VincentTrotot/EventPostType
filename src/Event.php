<?php

namespace VincentTrotot\Event;

class Event extends \Timber\Post
{

    public $start;
    public $end;
    public $location;
    public $home;
    public $now;

    public function __construct($pid = null)
    {
        parent::__construct($pid);
        $this->now = current_datetime()->getTimestamp() + current_datetime()->getOffset();
        $this->start = (int) $this->meta('vt_events_startdate') != 0 ? (int) $this->meta('vt_events_startdate') : $this->now;
        $this->end = (int) $this->meta('vt_events_enddate') != 0 ? (int) $this->meta('vt_events_enddate') : $this->now;
        $this->home = $this->meta('vt_events_display_home') !== null ? $this->meta('vt_events_display_home') : true;
        $this->location = $this->meta('vt_events_location');
    }

    /**
     * Retourne les $nb prochains événenements  \
     * sans celui "actif" si exclude est à true  \
     * sous forme de PostQuery
     */
    public function getNextEvents(int $nb, $exclude = false, $onHome = false) : \Timber\PostQuery
    {
        $post_not_in[] = $exclude ? $this->id : null;
        $meta = [];
        if($onHome) {
            $meta = [
                'key' => 'vt_events_display_home',
                'value' => true
            ];
        }
        $args = [
            'post_type' => 'vt_events',
            'posts_per_page' => $nb,
            'post__not_in' => $post_not_in,
            'meta_query' => [
                [
                    'key' => 'vt_events_enddate',
                    // on affiche les événements jusqu'à la fin de la journée
                    'value' => strtotime(date("Ymd", $this->now)),
                    'compare' => '>=',
                ],
                $meta,
            ],
            'meta_key' => 'vt_events_startdate',
            'orderby' => 'meta_value',
            'order' => 'ASC',
        ];
        return new \Timber\PostQuery($args, Event::class);
    }

    /**
     * Retourne les événenements  \
     * compris entre $startdate et $enddate  \
     * sous forme de PostQuery
     */
    public function getRangeEvents(string $startdate, string $enddate) : \Timber\PostQuery
    {
        //echo strtotime($startdate);
        $args = [
            'post_type' => 'vt_events',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'vt_events_enddate',
                    'value' => strtotime($startdate),
                    'compare' => '>=',
                ],
                [
                    'key' => 'vt_events_startdate',
                    'value' => strtotime($enddate),
                    'compare' => '<=',
                ],
            ],
            'meta_key' => 'vt_events_startdate',
            'orderby' => 'meta_value',
            'order' => 'ASC',
        ];
        return new \Timber\PostQuery($args, Event::class);
    }

    /**
     * l'événement se produit-il aujourd'hui ?
     */
    public function isToday()
    {
        return
            strtotime(date("Ymd", $this->start)) <= strtotime(date("Ymd", $this->now)) &&
            strtotime(date("Ymd", $this->end)) >= strtotime(date("Ymd", $this->now));
    }

    /**
     * l'événement se produit-il cette semaine ?
     */
    public function isThisWeek()
    {
        return date("W", $this->start) <= date("W", $this->now) &&
            date("W", $this->end) >= date("W", $this->now);
    }
    
    /**
     * Retourne le nombre d'événements à venir
     */
    public function getNbFutureEvents()
    {
        return ($this->getNextEvents(-1))->found_posts;
    }

    /**
     * Retourne le $timestamp formaté en français selon le $format
     */
    public static function inFrench($format, $timestamp = null)
    {
        $param_d =[
            '',
            'lun',
            'mar',
            'mer',
            'jeu',
            'ven',
            'sam',
            'dim'
        ];
        $param_D =[
            '',
            'Lun',
            'Mar',
            'Mer',
            'Jeu',
            'Ven',
            'Sam',
            'Dim'
        ];
        $param_l =[
            '',
            'lundi',
            'mardi',
            'mercredi',
            'jeudi',
            'vendredi',
            'samedi',
            'dimanche'
        ];
        $param_L =[
            '',
            'Lundi',
            'Mardi',
            'Mercredi',
            'Jeudi',
            'Vendredi',
            'Samedi',
            'Dimanche'
        ];
        $param_f =[
            '',
            'janvier',
            'février',
            'mars',
            'avril',
            'mai',
            'juin',
            'juillet',
            'août',
            'septembre',
            'octobre',
            'novembre',
            'décembre'
        ];
        $param_F =[
            '',
            'Janvier',
            'Février',
            'Mars',
            'Avril',
            'Mai',
            'Juin',
            'Juillet',
            'Août',
            'Septembre',
            'Octobre',
            'Novembre',
            'Décembre'
        ];
        $param_m =[
            '',
            'jan',
            'fév',
            'mar',
            'avr',
            'mai',
            'jun',
            'jul',
            'aoû',
            'sep',
            'oct',
            'nov',
            'déc'
        ];
        $param_M =[
            '',
            'Jan',
            'Fév',
            'Mar',
            'Avr',
            'Mai',
            'Jun',
            'Jul',
            'Aoû',
            'Sep',
            'Oct',
            'Nov',
            'Déc'
        ];
        $return = '';
        if (is_null($timestamp)) {
            $timestamp = current_datetime()->getTimestamp() + current_datetime()->getOffset();
        }
        for ($i = 0, $len = strlen($format); $i < $len; $i++) {
            switch ($format[$i]) {
                case '\\': // fix.slashes
                    $i++;
                    $return .= isset($format[$i]) ? $format[$i] : '';
                    break;
                case 'd':
                    $return .= $param_d[date('N', $timestamp)];
                    break;
                case 'D':
                    $return .= $param_D[date('N', $timestamp)];
                    break;
                case 'l':
                    $return .= $param_l[date('N', $timestamp)];
                    break;
                case 'L':
                    $return .= $param_L[date('N', $timestamp)];
                    break;
                case 'f':
                    $return .= $param_f[date('n', $timestamp)];
                    break;
                case 'F':
                    $return .= $param_F[date('n', $timestamp)];
                    break;
                case 'm':
                    $return .= $param_m[date('n', $timestamp)];
                    break;
                case 'M':
                    $return .= $param_M[date('n', $timestamp)];
                    break;
                case 'i':
                    $return .= date($format[$i], $timestamp) == "00" ? '' : date($format[$i], $timestamp);
                    break;
                case 'j':
                    $return .= date($format[$i], $timestamp) == "1" ? '1<sup>er</sup>' : date($format[$i], $timestamp);
                    break;
                default:
                    $return .= date($format[$i], $timestamp);
                    break;
            }
        }
        return $return;
    }

    /**
     * Retourne la date de début de l'événement formatée en français  \
     * selon le $format
     */
    public function getStartInFrench(string $format) : string
    {
        return Event::inFrench($format, $this->start);
    }

    /**
     * Retourne la date de fin de l'événement formatée en français  \
     * selon le $format
     */
    public function getEndInFrench(string $format) : string
    {
        return Event::inFrench($format, $this->end);
    }

    /**
     * Retourne la date formatée différement selon si l'événement est sur un jour,  \
     * plusieurs jours, ou sur des mois différents.
     */
    public function getDate($hour = false)
    {
            // événement sur 1 jour
        if ($this->isOneDayEvent()) {
            return $this->oneDayEvent($hour);
        }
    
        // événement sur deux jours dans le même mois
        if ($this->isTwoDaysSameMonthEvent()) {
            return $this->twoDaysSameMonthEvent($hour);
        }
        
        // événement sur plus de deux jours dans le même mois
        if ($this->isMoreDaysSameMonthEvent()) {
            return $this->moreDaysSameMonthEvent($hour);
        }
    
        // événement sur plusieurs jours dans des mois différents
        return $this->differentMonthEvent($hour);
    }
    
    /**
     * Retourne l'heure formatée différement selon si l'événement a une heure de début, \
     * de fin, ou si il est sur plusieurs jours.
     */
    public function getHour()
    {
        $start = Event::inFrench('G\hi', $this->start);
        $end = Event::inFrench('G\hi', $this->end);

        // Si le début et la fin sont à '0h',
        // on affiche pas d'heure de début
        if($start == '0h' && $end == '0h') {
            return false;
        }

        $res = $start;

        // Si la date de fin est différente de la date de début
        // et que l'événement est sur un jour, on l'affiche
        if ($start != $end && $this->isOneDayEvent()) {
            $res .= ' > ' . $end;
        }
    
        return $res;

    }
    
    /**
     * L'événement est-il sur un jour?
     */
    public function isOneDayEvent() : bool
    {
        return $this->getStartInFrench('l j f') === $this->getEndInFrench('l j f');
    }

    /**
     * Retourne la date formatée pour un événement sur un jour
     */
    public function oneDayEvent($hour) : string
    {
        $res = Event::inFrench('L j f Y', $this->start);
        if ($hour &&
            Event::inFrench('G\hi', $this->start) != '0h' &&
            Event::inFrench('G\hi', $this->end) != '0h'
        ) {
            $res .= ' | ' . Event::inFrench('G\hi', $this->start);
            if (Event::inFrench('G\hi', $this->start) != Event::inFrench('G\hi', $this->end)) {
                $res .= ' > ' . Event::inFrench('G\hi', $this->end);
            }
        }
        return $res;
    }
    
    /**
     * L'événement est-il sur deux jour consécutifs dans le même mois?
     */
    public function isTwoDaysSameMonthEvent() : bool
    {
        return $this->getStartInFrench('f') == $this->getEndInFrench('f') &&
            (int) $this->getEndInFrench('j') - (int) $this->getStartInFrench('j') == 1;
    }

    /**
     * Retourne la date formatée pour un événement sur deux jours consécutifs dans le même mois
     */
    public function twoDaysSameMonthEvent($hour) : string
    {
        $res = Event::inFrench('L j', $this->start).' et '.Event::inFrench('l j f Y', $this->end);
        if ($hour &&
            Event::inFrench('G\hi', $this->start) != '0h' &&
            Event::inFrench('G\hi', $this->end) != '0h'
        ) {
            $res .=
                ' | Démarrage à '
                .Event::inFrench('G\hi', $this->start)
                .' le '.Event::inFrench('l', $this->start);
        }
        return $res;
    }
    
    /**
     * L'événement est-il sur plus de deux jours dans le même mois ?
     */
    public function isMoreDaysSameMonthEvent() : bool
    {
        return $this->getStartInFrench('f') === $this->getEndInFrench('f') &&
            (int) $this->getEndInFrench('j') - (int) $this->getStartInFrench('j') > 1;
    }

    /**
     * Retourne la date formatée pour un événement sur plus de deux jours dans le même mois
     */
    public function moreDaysSameMonthEvent($hour) : string
    {
        $res = 'Du '.Event::inFrench('l j', $this->start). ' au '. Event::inFrench('l j f Y', $this->end);
        if ($hour &&
            Event::inFrench('G\hi', $this->start) != '0h' &&
            Event::inFrench('G\hi', $this->end) != '0h'
        ) {
            $res .=
                ' | Démarrage à '
                .Event::inFrench('G\hi', $this->start)
                .' le '.Event::inFrench('l', $this->start);
        }
        return $res;
    }
    
    /**
     * Retourne la date formatée pour un événement dont le début et la fin ne sont pas dans le même mois
     */
    public function differentMonthEvent($hour) : string
    {
        $consecutive = Event::inFrench('j', strtotime('+1 day', $this->start)) === Event::inFrench('j', $this->end);
        if ($consecutive) {
            $res = Event::inFrench('L j f', $this->start).' et '.Event::inFrench('l j f Y', $this->end);
        } else {
            $res = 'Du '. Event::inFrench('l j f', $this->start) .' au '. Event::inFrench('l j f Y', $this->end);
        }
        if ($hour &&
            Event::inFrench('G\hi', $this->start) != '0h' &&
            Event::inFrench('G\hi', $this->end) != '0h'
        ) {
            $res .=
                ' | Démarrage à '
                .Event::inFrench('G\hi', $this->start)
                .' le '.Event::inFrench('l', $this->start);
        }
        return $res;
    }
}
