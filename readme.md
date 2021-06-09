# EventPostType

EventPostType est un package permettant d'ajouter un Custom Post Type `vt_events` à un theme WordPress.  
Ce Custom Post Type ajoute trois metadata : Une date de début, une date de fin, et un lieu.

La classe `VincentTrotot\Event\EventPostType` paramètre le Custom Post Type.  
La classe `VincentTrotot\Event\Event` est une espèce de wrapper du Post (la classe hérite de la classe `Timber\TimberPost`).  
Une classe `VincentTrotot\Event\SportEvent` hérite de la classe `VincentTrotot\Event\Event` en y ajoutant des méthodes pour gérer l'agenda avec une saisonalité sportive. On peut aussi ajouter une catégorie `club` à un événement pour dire si un événement est à destination des adhérents. La classe ajoute une propriété `$outdated` pour savoir si l'événement est déjà passé dans la saison.

## Installation

```bash
composer require vtrotot/event-post-type
```

## Utilisation

Votre theme doit instancier la classe `EventPostType`

```php
new VincentTrotot\Event\EventPostType();
```

Vous pouvez ensuite récupérer un Post de type Event:

```php
$post = new VincentTrotot\Event\Event();
```

Ou récupérer plusieurs posts avec :

```php
$args = [
    'post_type' => 'vt_event',
    ...
];
$posts = new Timber\TimberRequest($args, VincentTrotot\Event\Event::class);
```
