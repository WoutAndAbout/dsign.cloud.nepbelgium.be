<?php

/**
 * Script PHP pour récupérer, filtrer et convertir des événements d'un fichier ICS en XML.
 *
 * Fonctionnalités principales :
 * 1. Authentification :
 *    - Vérifie les informations d'identification (user_id et password) fournies via les paramètres GET.
 *    - Empêche l'accès non autorisé (403 Forbidden).
 *
 * 2. Gestion des salles :
 *    - Une liste des salles disponibles est définie dans le tableau `$salles`.
 *    - Si aucun événement n'est trouvé pour une salle, un événement vide est retourné.
 *
 * 3. Analyse des fichiers ICS :
 *    - Le fichier ICS est récupéré depuis une URL distante.
 *    - Les événements sont extraits et parsés pour en récupérer les détails (date, heure, description, etc.).
 *
 * 4. Fonctions de filtrage :
 *    - `filterEventsToday` : Filtre les événements pour une date spécifique (aujourd'hui, demain ou une date passée en paramètre GET).
 *    - `filterEventsByLocation` : Filtre les événements associés à une salle donnée.
 *    - `filterEventNow` : Retourne l'événement en cours ou le prochain pour une salle spécifique.
 *
 * 5. Conversion des données :
 *    - Les événements sont convertis en XML via la fonction `convertToXML`.
 *    - La description des événements est parsée pour extraire des champs spécifiques (catering, équipements, etc.).
 *
 * 6. Paramètres GET disponibles :
 *    - `user_id` et `password` : Informations d'identification obligatoires.
 *    - `location` : Nom de la salle pour filtrer les événements.
 *    - `date` : Filtrer les événements pour une date spécifique (au format `YYYY-MM-DD`).
 *    - `tomorrow` : Filtrer les événements pour demain (`tomorrow=true`).
 *    - `allEvents` : Affiche tous les événements pour chaque salle (`allEvents=yes`), sinon uniquement le premier événement.
 *    - `now` : Affiche l'événement en cours pour une salle donnée (`now=yes`).
 *
 * 7. Gestion des erreurs :
 *    - Retourne une erreur si le fichier ICS est inaccessible ou si aucune information n'est trouvée.
 *
 * Exemple d'appel :
 *   - `script.php?user_id=dashboard&password=uXOpj56Uoy1Yllg1yO5nVOBqzd6K4fyt7kTwqnDjE5UEapKgns&location=Polak&now=yes`
 *
 * Auteur : Baptiste Campion
 * Date : Novembre
 */

 // URL du fichier ICS
 $icsUrl = 'https://ipc.diesesoftware.com/sp_ics-VT4BJgxj-ATALbQ==-ADoEYwI3-12,13,7,11,8,1,3,4,5,6,10-0.ics';





$salles = [
    'Polak',
    'Patio',
    'Salon d\'Honneur',
    'Maelbeek',
    'Passage',
    'Club',
    'Restaurant (Bar Side)',
    'Restaurant (Window Side)',
    'Restaurant (full)',
    'Restaurant',
    'Salon',
    'Magritte',
    'Ensor',
    'J@ys',
    'Broodthaers',
    'Mirrors Room',
    'Rooftop terrace',
    'Media Room'
];

$emptyEventDescription = [
    'catering' => 'No',
    'audiovisual' => 'No',
    'title' => '',
    'room_composition' => '',
    'fixed_equipment' => [],
    'mobile_equipment' => [],
    'number_of_invitees' => '',
    'organisation_details' => [],
    'minister' => '',
    'part'=>''
];

$emptyEvent = [
    'DSTART'=> '',
    'DEND'=> '',
];

$nonEmptyOnly = isset($_GET['nonEmptyOnly']) && $_GET['nonEmptyOnly'] === 'yes';

$users = [
    'teosServer' => 'Enkip0EsLF6FK7QRI1Il88MB3oruRRH0jNQVZHsvVOX5FhuvsT',
    'dashboard' => 'uXOpj56Uoy1Yllg1yO5nVOBqzd6K4fyt7kTwqnDjE5UEapKgns',
    'test'=>'b6iWePzM8fu4Oz6xzh8Apo0PYkdAxEo8SbolMZwoTkxrRdTZ3kQax8B5grgZGz5M'
];

// Vérification des informations d'identification
if (!isset($_GET['user_id']) || !isset($_GET['password'])) {
    http_response_code(403); // Si user_id ou password est manquant
    die('ERROR 403');
}

$user_id = $_GET['user_id'];
$password = $_GET['password'];

// Vérifier si l'utilisateur existe et si le mot de passe est correct
if (!array_key_exists($user_id, $users) || $users[$user_id] !== $password) {
    http_response_code(403); // Si l'utilisateur n'existe pas ou si le mot de passe est incorrect
    die('ERROR 403');
}

// Fonction pour parser un fichier ICS
function parseICS($icsContent) {
    $lines = explode("\n", $icsContent);
    $events = [];
    $currentEvent = null;

    foreach ($lines as $line) {
        

        $line = trim($line);
        

        if (stripos($line, "BEGIN:VEVENT") === 0) {
            $currentEvent = [];
        } elseif (stripos($line, "END:VEVENT") === 0 && $currentEvent !== null) {
            $events[] = $currentEvent;
            $currentEvent = null;
        } elseif ($currentEvent !== null) {
            if (strpos($line, ":") !== false) {
                list($key, $value) = explode(":", $line, 2);
                
        if($key =="DTSTART;TZID=Europe/Paris"){
            $currentEvent["DSTART"] =convertirDate(trim($value));
        } elseif($key == "DTEND;TZID=Europe/Paris"){
            $currentEvent["DEND"] =convertirDate(trim($value));
        }
                $currentEvent[trim($key)] = trim($value);
            }
        }
    }

    return $events;
}

function convertirDate($dateAPI) {
    // Vérifie si la date est dans le bon format avec une longueur de 15 caractères
    if (strlen($dateAPI) !== 15) {
        return "Format de date incorrect";
    }
    
    // Extraction des heures et minutes de la chaîne
    $heure = substr($dateAPI, 9, 2);
    $minute = substr($dateAPI, 11, 2);

    // Concatène pour obtenir le format hh:mm
    return $heure . ":" . $minute;
}


function normalizeLocation($location) {
    return strtolower(trim($location));
}

function filterEventsByLocation($events, $location) {
    global $emptyEvent, $nonEmptyOnly;
    $filteredEvents = [];
    $isEvent = false;

    foreach ($events as $event) {
        // Normaliser la salle
        $eventLocation = normalizeLocation($event['LOCATION'] ?? '');

        // Cas 1 : Si la salle correspond directement
        if ($eventLocation === normalizeLocation($location)) {
            $isEvent = true;
            $event['PART'] = $event['LOCATION'];
            $event['LOCATION'] = $location; // Normalisation
            $filteredEvents[] = $event;
        }

        // Cas 2 : Si la salle est "Restaurant" et correspond aux sous-sections
        if (normalizeLocation($location) === "restaurant" && 
            in_array($eventLocation, [
                "restaurant (bar side)",
                "restaurant (window side)",
                "restaurant (full)"
            ])
        ) {
            $isEvent = true;
            $event['PART'] = $event['LOCATION']; // Garder la section spécifique
            $event['LOCATION'] = "Restaurant";  // Regrouper sous "Restaurant"
            $filteredEvents[] = $event;
        }
    }

    // Si aucun événement trouvé et `nonEmptyOnly` désactivé, ajouter un événement vide
    if (!$isEvent && !$nonEmptyOnly) {
        $emptyEvent['location'] = $location;
        $filteredEvents[] = $emptyEvent;
    }

    return $filteredEvents;
}


// Fonction pour filtrer les événements d'aujourd'hui
function filterEventsToday($events) {
    $today = new DateTime("today", new DateTimeZone('Europe/Paris')); // Date d'aujourd'hui avec le fuseau horaire
    $filteredEvents = [];

    foreach ($events as $event) {
       
        if (isset($event['DTSTART;TZID=Europe/Paris'])) {
            
            $eventDate = DateTime::createFromFormat('Ymd\THis', $event['DTSTART;TZID=Europe/Paris'], new DateTimeZone('Europe/Paris'));
            if (isset($_GET['date'])) {
                $checkDate = new DateTime($_GET['date'], new DateTimeZone('Europe/Paris'));
            } elseif(isset($_GET['tomorrow'])&&$_GET['tomorrow']==true){
                $checkDate = new DateTime('tomorrow', new DateTimeZone('Europe/Paris'));
            } else{
                $checkDate = $today;
            }
            if ($eventDate && $eventDate->format('Y-m-d') === $checkDate->format('Y-m-d')) {
               $event['description_details'] = "";
                $filteredEvents[] = $event; // Ajouter l'événement s'il est aujourd'hui
            } 
        }
    }

    return $filteredEvents;
}

function returnEventwithAllLocation($events, $salles){
global $emptyEventDescription, $nonEmptyOnly;
    $allEvents = [];
    $restoEvent = [];
    $showAllEvents = isset($_GET['allEvents']) && $_GET['allEvents'] === 'yes';
    foreach ($salles as $salle) {
        
        $event = filterEventsByLocation($events,$salle);
        if($event){
            if ($showAllEvents) {
                foreach ($event as $ev) {
                    $allEvents[] = $ev;
                    if($ev['LOCATION']=="Restaurant (Bar Side)"||$ev['LOCATION']=="Restaurant (Window Side)"||$ev['LOCATION']=="Restaurant (full)") {
                        $ev['LOCATION']=="Restaurant";
                        $restoEvent = $ev;
                    }
                    $allEvents[]= $restoEvent;
                }
            } else {
                // Sinon, n'ajoutez que le premier événement trouvé pour la salle
                $allEvents[] = $event[0];
            }
        } elseif (!$nonEmptyOnly) {
            // Ajoutez un événement vide uniquement si `nonEmptyOnly` n'est pas activé
            $emptyEvent = $emptyEventDescription;
            $emptyEvent['location'] = $salle;
            $allEvents[] = $emptyEvent;
        }
        
    }

    return $allEvents;
}

// Fonction pour parser les champs personnalisés de la description (pas de changement ici)
function parseEventDescription($description) {
    $result = [
        'catering' => 'No',
        'audiovisual' => 'No',
        'title' => '',
        'room_composition' => '',
        'fixed_equipment' => [],
        'mobile_equipment' => [],
        'number_of_invitees' => '',
        'organisation_details' => [],
        'minister' => '',
        'part'=>''
    ];

    $description = str_replace("<br>", "", $description);

    $lines = preg_split('/\r\n|\r|\n/', trim($description));
   

    $lines = explode('\n',trim($description));

    $lines = array_filter(array_map('trim', $lines), function($line) {
        return !empty($line); // Ne conserve que les lignes non vides
    });

    foreach ($lines as $line) {
        if (preg_match('/^Catering\s*\?\s*:\s*(Yes|No)/i', $line, $matches)) {
            $result['catering'] = trim($matches[1]);
        } elseif (preg_match('/^Audiovisual\s*\?\s*:\s*(Yes|No)/i', $line, $matches)) {
            $result['audiovisual'] = trim($matches[1]);
        } elseif (preg_match('/^Title for signage\s*:\s*(.+)/i', $line, $matches)) {
            $result['title'] = trim($matches[1]);
        } elseif (preg_match('/^Room composition\s*:\s*(.+)/i', $line, $matches)) {
            $result['room_composition'] = trim($matches[1]);
        } elseif (preg_match('/^Fixed equipment\s*:\s*(.+)/i', $line, $matches)) {
            $equipments = array_map('trim', explode(",", $matches[1]));
            $result['fixed_equipment'] = $equipments;
        } elseif (preg_match('/^Mobile equipment\s*:\s*(.+)/i', $line, $matches)) {
            $equipments = array_map('trim', explode(",", $matches[1]));
            $result['mobile_equipment'] = $equipments;
        } elseif (preg_match('/^Number of invitees\s*:\s*(.+)/i', $line, $matches)) {
            $result['number_of_invitees'] = trim($matches[1]);
        } elseif (preg_match('/^Organisation details\s*:\s*(.+)/i', $line, $matches)) {
            $details = array_map('trim', explode("<br />", $matches[1]));
            $result['organisation_details'] = $details;
        } elseif (preg_match('/^Minister\s*\?\s*:\s*(.+)/i', $line, $matches)) {
            $result['minister'] = trim($matches[1]);
        } else {
            if (!empty(trim($line))) {
                $result['organisation_details'][] = trim($line);
            }
        }
    }

    return $result;
}

function filterEventNow(array $events, string $location) {
    global $emptyEvent, $nonEmptyOnly;
    $emptyEvent['location'] = $location;
    
    // Heure actuelle pour comparaison, au format `H:i`
    $currentTime = (new DateTime())->format('H:i');
    

    foreach ($events as $event) {
        // Récupération des heures de début et de fin de chaque événement
        $startTime = $event['DSTART'];
        $endTime = $event['DEND'];

        // Vérifie si l'événement est en cours
        if ($currentTime >= $startTime && $currentTime < $endTime) {
            $displayEvent[0] = $event;
         } elseif ($startTime > $currentTime) {
            if (!isset($displayEvent[0])){
                    $displayEvent[0] = $event;
            }
        } elseif (!$nonEmptyOnly) {
            $displayEvent[0] = $emptyEvent;  
        }
    }

    $return[] = $displayEvent[0];
    // Retourne l'événement en cours s'il y en a un, sinon le prochain événement
    return $return;
}

// Fonction pour nettoyer les clés (inchangée)
function cleanKey($key) {
    return preg_replace('/[^a-z0-9_]/', '_', strtolower($key));
}

// Fonction pour échapper les caractères spéciaux (inchangée)
function escapeXML($value) {
    return is_array($value) ? implode(', ', array_map('htmlspecialchars', $value)) : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Fonction pour convertir les événements ICS en XML (inchangée)
function convertToXML($events) {
    $xml = new SimpleXMLElement('<events/>');

    foreach ($events as $event) {
        $eventXML = $xml->addChild('event');
if(is_array($event)){
    foreach ($event as $key => $value) {
        if (strtolower($key) === 'summary') {
            // Supprimer "Booking - " du résumé si présent
            $value = str_replace('Booking - ', '', $value);
            $eventXML->addChild(cleanKey($key), escapeXML($value)); // Ajouter le résumé modifié
        } elseif (strtolower($key) === 'description') {
            $parsedDescription = parseEventDescription($value);
            $descriptionXML = $eventXML->addChild('description_details');
            $eventXML->addChild('title', escapeXML($parsedDescription['title']));

            foreach ($parsedDescription as $descKey => $descValue) {
                if (is_array($descValue) && !empty($descValue)) {
                    $descFieldXML = $descriptionXML->addChild(cleanKey($descKey));
                    foreach ($descValue as $descItem) {
                        $descFieldXML->addChild('item', escapeXML($descItem));
                    }
                } else {
                    $descriptionXML->addChild(cleanKey($descKey), escapeXML($descValue));
                }
            }
        } else {
            $eventXML->addChild(cleanKey($key), escapeXML($value));
        }
    }
}
        
    }

    return $xml->asXML();
}



// Récupération du contenu ICS
$icsContent = file_get_contents($icsUrl);

if ($icsContent !== false) {
    $events = parseICS($icsContent);

    // Filtrer les événements d'aujourd'hui
    $eventsToday = filterEventsToday($events);

    if (isset($_GET['location'])) {
        $location = $_GET['location'];
        $eventsToday = filterEventsByLocation($eventsToday, $location);
        if (isset($_GET['now'])&&$_GET['now']=="yes") {
            $eventsToday = filterEventNow($eventsToday, $location);
        }
    } else {
        $eventsToday = returnEventwithAllLocation($eventsToday, $salles);
    }
    

    // Conversion des événements d'aujourd'hui en XML
    $xmlContent = convertToXML($eventsToday);

    // Affichage du contenu XML
    header('Content-Type: application/xml');
    echo $xmlContent;
} else {
    echo "Erreur: Impossible de récupérer le fichier ICS.";
}
