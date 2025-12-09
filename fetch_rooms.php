<?php

date_default_timezone_set('Europe/Paris');
$apiKey = 'xLrMCpR7uE2i6UI1kxeG65d9IIxxnckYvRZuk2hi161GXz1u4U'; // Remplacez par votre clé API sécurisée

// Vérification de la clé API
if (!isset($_GET['api_key']) || $_GET['api_key'] !== $apiKey) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$current_date = strtotime('now');
$url = 'https://nepbelgium.digital/teosAPI/today.php?user_id=dashboard&password=uXOpj56Uoy1Yllg1yO5nVOBqzd6K4fyt7kTwqnDjE5UEapKgns';
$nonEmptyOnly = false;
// Vérification du paramètre GET `date`
if (isset($_GET['date'])) {
    $current_date = strtotime($_GET['date']);
    $url .= '&date=' . $_GET['date'];
}

// Vérification du paramètre GET `date`
if (isset($_GET['nonEmptyOnly'])) {
    $url .= '&nonEmptyOnly=yes';
    $nonEmptyOnly = true;
}



// Vérification du paramètre `allEvents`
$allEvents = isset($_GET['allEvents']) && $_GET['allEvents'] === 'yes';
if ($allEvents) {
    $url .= "&allEvents=yes";
}

$rooms = [
    "Polak", "Patio", "Salon d'Honneur", "Maelbeek", "Passage", "Mirrors Room", "Club", "Salon",
    "Restaurant (Bar Side)", "Restaurant (Window Side)", "Restaurant (full)", "Restaurant",
    "Rooftop terrace", "Magritte", "Ensor", "J@ys", "Broodthaers", "Media Room"
];

function getTodayEventsFromXml($url, $current_date, $allEvents = false) {
    global $nonEmptyOnly;
    $events = [];
    $xml = @simplexml_load_file($url);

    if ($xml === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to load XML data']);
        exit;
    }

    foreach ($xml->event as $event) {
        $location = (string)$event->location;
        $part = (string)$event->part;
        $summary = (string)$event->summary;
        $title = (string)$event->title;
        $dstart_tzid = (string)$event->dtstart_tzid_europe_paris;
        $dend_tzid = (string)$event->dtend_tzid_europe_paris;
        $catering = (string)$event->description_details->catering;
        $audiovisual = (string)$event->description_details->audiovisual;

        if (!empty($dstart_tzid) && !empty($dend_tzid)) {
            $event_start = strtotime($dstart_tzid);
            $event_end = strtotime($dend_tzid);
            $one_hour_ago = strtotime('-1 hour', $current_date);
            $one_hour_ahead = strtotime('+1 hour', $current_date);

            // Comparaison basée sur la date entière
            if (date('Y-m-d', $current_date) === date('Y-m-d', $event_start)) {
                $status = 'free';
                if ($current_date >= $event_start && $current_date <= $event_end) {
                    $status = 'busy';
                } elseif ($event_end <= $current_date && $event_end >= $one_hour_ago) {
                    $status = 'past1h';
                } elseif ($current_date < $event_start && $event_start <= $one_hour_ahead) {
                    $status = 'waiting1h';
                } elseif ($current_date < $event_start) {
                    $status = 'waitingToday';
                }

                $eventDetails = [
                    'summary' => $summary ?: 'Untistled event',
                    'title' => $title ?:'',
                    'time' => date('H:i', $event_start) . " ➔ " . date('H:i', $event_end),
                    'start' => date('H:i', $event_start),
                    'end' => date('H:i', $event_end),
                    'status' => $status,
                    'catering' => strtolower($catering) === 'yes',
                    'audiovisual' => strtolower($audiovisual) === 'yes',
                    'location'=> $location,
                    'part'=>$part
                ];

                if($nonEmptyOnly){
                    if ($status != "free") {
                        // Ajouter l'événement pour les salles non "free"
                        if ($allEvents) {
                            $events[$location][] = $eventDetails;
                        } else {
                            $events[$location] = $eventDetails;
                        }
                    }
                } else {
                     // Ajouter tous les événements, y compris "free"
                     if ($allEvents) {
                        $events[$location][] = $eventDetails;
                    } else {
                        $events[$location] = $eventDetails;
                    }
                }

                
                
                
            }
        }
    }

    return $events;
}

$events = getTodayEventsFromXml($url, $current_date, $allEvents);

if($nonEmptyOnly){
    $roomStatus = $events;
} else {
    // Initialisation des statuts des salles
$roomStatus = [];
foreach ($rooms as $room) {
    $roomStatus[$room] = $allEvents ? [] : [
        'status' => 'free',
        'summary' => '',
        'title' => '',
        'time' => '',
        'catering' => false,
        'audiovisual' => false
    ];
}

// Mise à jour des statuts des salles en fonction des événements
foreach ($events as $location => $event) {
    if (in_array($location, $rooms)) {
        if ($allEvents) {
            // Ajoute chaque événement si `allEvents` est activé
            $roomStatus[$location] = $event;
        } else {
            // Sinon, un seul événement par salle
            $roomStatus[$location] = [
                'status' => $event['status'],
                'summary' => $event['summary'],
                'title' => $event['title'],
                'time' => $event['start'] . " ➔ " . $event['end'],
                'start' => $event['start'],
                'end' => $event['end'],
                'catering' => $event['catering'],
                'audiovisual' => $event['audiovisual']
            ];
        }
    }
}

// Logique pour la gestion des événements des restaurants
if (!$allEvents) {
    $restaurantRooms = ["Restaurant (Bar Side)", "Restaurant (Window Side)", "Restaurant (full)"];
    $restaurantEvent = null;
    foreach ($restaurantRooms as $room) {
        if (isset($events[$room]) && in_array($events[$room]['status'], ['busy', 'past1h', 'waiting1h'])) {
            $restaurantEvent = $events[$room];
            break; // Priorise le premier événement correspondant
        }
    }

    if ($restaurantEvent) {
        $roomStatus['Restaurant'] = [
            'status' => $restaurantEvent['status'],
            'summary' => $restaurantEvent['summary'],
            'title' => $event['title'],
            'time' => $restaurantEvent['start'] . " ➔ " . $restaurantEvent['end'],
            'start' => $restaurantEvent['start'],
            'end' => $restaurantEvent['end'],
            'catering' => $restaurantEvent['catering'],
            'audiovisual' => $restaurantEvent['audiovisual']
        ];
    }
}
}


header('Content-Type: application/json');

echo json_encode($roomStatus);
