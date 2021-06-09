<?php

namespace VincentTrotot\Event;

class Event extends \Timber\Post
{

    public $start;
    public $end;
    public $location;
    public $now;

    public function __construct($pid = null)
    {
        parent::__construct($pid);
        $this->start = (int) $this->meta('vt_events_startdate');
        $this->end = (int) $this->meta('vt_events_enddate');
        $this->location = $this->meta('vt_events_location');
        $this->now = current_datetime()->getTimestamp() + current_datetime()->getOffset();
    }

    /**
     * Retourne les $nb prochains événenements  \
     * sans celui "actif" si exclude est à true  \
     * sous forme de PostQuery
     */
    public function getNextEvents(int $nb, $exclude = false) : \Timber\PostQuery
    {
        $post_not_in[] = $exclude ? $this->id : null;
        $args = [
            'post_type' => 'vt_events',
            'posts_per_page' => $nb,
            'orderby' => 'meta_value',
            'post__not_in' => $post_not_in,
            'meta_query' => [
                [
                    'key' => 'vt_events_enddate',
                    // on affiche les événements jusqu'à la fin de la journée
                    'value' => strtotime(date("Ymd", $this->now)),
                    'compare' => '>',
                ],
            ],
            'order' => 'ASC',
        ];
        return new \Timber\PostQuery($args, Event::class);
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
    public function getDate($sidebar = false)
    {
            // événement sur 1 jour
        if ($this->isOneDayEvent()) {
            return $this->oneDayEvent($sidebar);
        }
    
        // événement sur deux jours dans le même mois
        if ($this->isTwoDaysSameMonthEvent()) {
            return $this->twoDaysSameMonthEvent($sidebar);
        }
        
        // événement sur plus de deux jours dans le même mois
        if ($this->isMoreDaysSameMonthEvent()) {
            return $this->moreDaysSameMonthEvent($sidebar);
        }
    
        // événement sur plusieurs jours dans des mois différents
        return $this->differentMonthEvent($sidebar);
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
    public function oneDayEvent($sidebar)
    {
        $res = $this->getStartInFrench('L j f Y – G\hi');
        if ($this->getStartInFrench('G\hi') != $this->getEndInFrench('G\hi')) {
            $res .= ' > ' . $this->getEndInFrench('G\hi');
        }
        return $res;
    }
    
    /**
     * L'événement est-il sur deux jour consécutifs dans le même mois?
     */
    public function isTwoDaysSameMonthEvent() : bool
    {
        return $this->getStartInFrench('f') === $this->getEndInFrench('f') &&
            (int) $this->getEndInFrench('j') - (int) $this->getStartInFrench('j') == 1;
    }

    /**
     * Retourne la date formatée pour un événement sur deux jours consécutifs dans le même mois
     */
    public function twoDaysSameMonthEvent($sidebar) : string
    {
        return $this->getStartInFrench('L j').' et '.$this->getEndInFrench('l j f Y');
    }
    
    /**
     * L'événement est-il sur plus de deux jours dans le même mois ?
     */
    public function isMoreDaysSameMontEvent() : bool
    {
        return $this->getStartInFrench('f') === $this->getEndInFrench('f') &&
            (int) $this->getEndInFrench('j') - (int) $this->getStartInFrench('j') > 1;
    }

    /**
     * Retourne la date formatée pour un événement sur plus de deux jours dans le même mois
     */
    public function moreDaysSameMonthEvent($sidebar) : string
    {
        return 'Du '.$this->getStartInFrench('l j'). ' au '. $this->getEndInFrench('l j f Y');
    }
    
    /**
     * Retourne la date formatée pour un événement dont le début et la fin ne sont pas dans le même mois
     */
    public function differentMonthEvent($sidebar)
    {
        $consecutive = Event::inFrench('j', strtotime('+1 day', $this->start)) == $this->getEndInFrench('j');
        if ($consecutive) {
            return $this->getStartInFrench('L j f').' et '.$this->getEndInFrench('l j f Y');
        }
        return 'Du '.$this->getStartInFrench('l j f') .' au '.$this->getEndInFrench('l j f Y');
    }
}