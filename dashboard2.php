<?php
date_default_timezone_set('Europe/Paris');

$users = [
    'teosServer' => 'Enkip0EsLF6FK7QRI1Il88MB3oruRRH0jNQVZHsvVOX5FhuvsT',
    'dashboard' => 'uXOpj56Uoy1Yllg1yO5nVOBqzd6K4fyt7kTwqnDjE5UEapKgns',
    'tech' => 'SvqDpl3rbNWhHmos3URFWGRWi57gnVzUg6wwXxhID2KbqMOzRp'
];

if (!isset($_GET['user_id']) || !isset($_GET['password'])) {
    http_response_code(403);
    die('ERROR 403');
}

$user_id = $_GET['user_id'];
$password = $_GET['password'];

if (!array_key_exists($user_id, $users) || $users[$user_id] !== $password) {
    http_response_code(403);
    die('ERROR 403');
}

// Vérifier si l'option 'tomorrow' est passée
$isTomorrow = isset($_GET['tomorrow']) && $_GET['tomorrow'] === 'yes';
$title = "Today's Events";

// Calculer la date de demain si nécessaire
if ($isTomorrow) {
    $hasDate = isset($_GET['date']);
    $title = "Tomorrow's Events";
    $date = date('Ymd', strtotime('+1 day'));
    $apiUrl = 'https://nepbelgium.digital/teosAPI/fetch_rooms.php?api_key=xLrMCpR7uE2i6UI1kxeG65d9IIxxnckYvRZuk2hi161GXz1u4U&allEvents=yes&date=' . $date;
    $dayToShow = strtotime('+1 day');

    while (true) {
       
        $jsonContent = file_get_contents($apiUrl);
        $data = json_decode($jsonContent, true);

        $i=0;
        foreach($data as $d){
            if (!empty($d)) {
                $i ++;
            }
        }

        // Si des événements existent, arrêter la boucle
        if ($i) {
            $title = date('l\'\s \E\v\e\n\t\s', $dayToShow);
            break;
        }

        // Si c'est un samedi, tester le dimanche
        if (date('N', $dayToShow) == 6) {
            $dayToShow = strtotime('+1 day', $dayToShow);
            $date = date('Ymd', $dayToShow);
            $apiUrl = 'https://nepbelgium.digital/teosAPI/fetch_rooms.php?api_key=xLrMCpR7uE2i6UI1kxeG65d9IIxxnckYvRZuk2hi161GXz1u4U&allEvents=yes&date=' . $date;
            continue;
        }

        // Si c'est un dimanche, passer au lundi
        if (date('N', $dayToShow) == 7) {
            $dayToShow = strtotime('next Monday');
            $date = date('Ymd', $dayToShow);
            $apiUrl = 'https://nepbelgium.digital/teosAPI/fetch_rooms.php?api_key=xLrMCpR7uE2i6UI1kxeG65d9IIxxnckYvRZuk2hi161GXz1u4U&allEvents=yes&date=' . $date;
            continue;
        }

        // Sinon, afficher le jour cible sans événements
        $title = date('l\'\s \E\v\e\n\t\s', $dayToShow);
        break;
    }
} elseif(isset($_GET['date'])) {
    $hasDate = isset($_GET['date']);
    $dayToShow = strtotime($_GET['date']);
    $title = date('d/m/Y \E\v\e\n\t\s', $dayToShow);
    $apiUrl = 'https://nepbelgium.digital/teosAPI/fetch_rooms.php?api_key=xLrMCpR7uE2i6UI1kxeG65d9IIxxnckYvRZuk2hi161GXz1u4U&allEvents=yes&date=' . $_GET['date'];
} else {
    $hasDate = isset($_GET['date']);
    $apiUrl = 'https://nepbelgium.digital/teosAPI/fetch_rooms.php?api_key=xLrMCpR7uE2i6UI1kxeG65d9IIxxnckYvRZuk2hi161GXz1u4U&allEvents=yes';
}

// Récupération du contenu JSON
$jsonContent = file_get_contents($apiUrl);

// Décodage du JSON en tableau associatif
$data = json_decode($jsonContent, true);

// Vérification de la présence des données
if (!$data) {
    die("Erreur : Impossible de récupérer les données des salles.");
}

// Liste des salles spécifiques à afficher
$roomsToShow = [
    "Polak", "Patio", "Maelbeek", "Passage", 
    "Salon d'Honneur", "Mirrors Room", "Club", "Salon", 
    "Restaurant", "Rooftop terrace", "Magritte", "Ensor", "J@ys", 
    "Broodthaers", "Media Room"
];

// Heure actuelle pour la logique de la ligne
$currentHour = date('G');
$currentMinute = date('i') / 60;
$currentTime = strtotime(date('H:i'));

// Calcul de la position de l'heure actuelle
$currentPosition = (180 + ($currentHour - 6) * 100) + ($currentMinute * 100); // Position en pixels
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            font-family: Verdana;
            background-color: #222;
            color: #fff;
        }
        .dashboard {
            padding: 20px;
        }
        .room {
            padding: 15px;
            height: 60px;
            text-align: center;
            width: 190px;
        }
        .timeline-event {
            margin-top: 5px;
            position: absolute;
            height: 50px;
            background-color: #4caf50; /* Couleur de base des événements */
            color: #fff;
            padding: 5px;
            border-radius: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .hour-cell {
            width: 100px;
            border-right: 1px solid #555; /* Ajoute une bordure à droite */
            text-align: center;
            position: relative;
        }
        .timeline-container {
            display: flex;
            flex-direction: row;
            border-top: 0.7px solid #444; /* Pour séparer les lignes */
            border-bottom: 0.7px solid #444; /* Pour séparer les lignes */
        }
        .timeline-container .hour-cell {
            flex: 0 0 100px;
            border-right: 1px solid #555; /* Ajoute une bordure verticale à chaque cellule */
        }
        .timeline-container::after {
            content: "";
            display: block;
            clear: both;
        }
        .current-time-line {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: red;
        }
        .busy-room {
            background-color: #f44336; /* En cours */
        }
        .waiting1h-room {
            background-color: #f48f36; /* Commence dans moins d'une heure */
        }
        .past1h-room {
            background-color: #8a2442; /* Fini il y a moins d'une heure */
        }
        .waitingToday {
            background-color: #afa54c; /* Prévu dans la journée */
        }
        .free-room {
            background-color: #4caf50; /* Libre */
        }
        .default-room {
            background-color: #333; /* Par défaut */
        }
    </style>
</head>
<body>
    <div class="container dashboard" style="width: 100%; max-width: 100%; min-width: 100%">
    <h1 class="text-center mb-2"><?= $title ?></h1>
    <div class="last-update text-center mb-3">Last update : <span id="lastUpdateTime"><?= date("H:i:s"); ?></span></div>
        <div class="table-responsive position-relative">
            <!-- Ligne indiquant l'heure actuelle (affichée uniquement si aucune date n'est passée et ce n'est pas "tomorrow") -->
            <?php if (!$hasDate && !$isTomorrow): ?>
                <div class="current-time-line" style="left: <?= $currentPosition ?>px;"></div>
            <?php endif; ?>

            <div class="d-flex" style="height: 40px;">
                <div style="width: 190px;">
                    <strong> </strong>
                </div>
                <div class="d-flex flex-fill">
                    <!-- Affichage des heures -->
                    <div class="d-flex flex-row w-100">
                        <?php for ($i = 6; $i <= 22; $i++): ?>
                        <div class="hour-cell text-start ps-1">
                            <?= $i ?>h
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            <!-- Affichage des salles et des événements -->
            <?php foreach ($roomsToShow as $room): ?>
                <?php
                // Vérification si la salle existe dans les données JSON
                if (isset($data[$room]) && is_array($data[$room])) {
                    $events = $data[$room];
                    $roomStatus = 'free-room'; // Par défaut : libre
                    $hasUpcomingEvent = false;

                    if (!$hasDate && !$isTomorrow) {
                        // Logique spécifique à aujourd'hui : adaptation des couleurs en fonction de l'heure
                        foreach ($events as $event) {
                            $startTime = strtotime($event['start']);
                            $endTime = strtotime($event['end']);

                            if ($currentTime >= $startTime && $currentTime < $endTime) {
                                $roomStatus = 'busy-room'; // En cours
                                break;
                            } elseif ($startTime - $currentTime <= 3600 && $startTime > $currentTime) {
                                $roomStatus = 'waiting1h-room'; // Commence dans moins d'une heure
                            } elseif ($currentTime - $endTime <= 3600 && $currentTime >= $endTime) {
                                $roomStatus = 'past1h-room'; // Fini il y a moins d'une heure
                            } elseif ($startTime > $currentTime) {
                                $hasUpcomingEvent = true; // Indique un événement prévu plus tard dans la journée
                            }
                        }

                        if ($roomStatus === 'free-room' && $hasUpcomingEvent) {
                            $roomStatus = 'waitingToday'; // Indique un événement prévu plus tard dans la journée
                        }
                    } else {
                        // Logique pour demain ou une date spécifique : rouge si des événements, vert si vide
                        if (count($events) > 0) {
                            $roomStatus = 'busy-room'; // Rouge si des événements
                        }
                    }
                ?>
                <div class="d-flex">
                    <div style="" class="fw-bold room <?= $roomStatus ?>">
                        <?= htmlspecialchars($room) ?>
                    </div>
                    <div class="timeline-container flex-fill">
                        <?php for ($i = 6; $i <= 22; $i++): ?>
                            <div class="hour-cell"></div>
                        <?php endfor; ?>
                        <?php foreach ($events as $event): ?>
                            <?php
                            
                                // Récupération des heures de début et de fin de l'événement
                                $startTime = strtotime($event['start']);
                                $endTime = strtotime($event['end']);
                                $startHour = date('G', $startTime);
                                $startMinute = date('i', $startTime) / 60;
                                $duration = ($endTime - $startTime) / 3600;

                                // Calcul de la position de début et de la largeur de l'événement
                                $leftPosition = (($startHour - 6) * 100) + ($startMinute * 100) + 180;
                                $width = $duration * 100;
                                
                            ?>
                            <div class="timeline-event" style="left: <?= $leftPosition ?>px; width: <?= $width ?>px; line-height: 1.3;">
                            <div class="input-group mb-3">
                                <span class="form-control m-0 border-0 text-white overflow-hidden py-0 ps-0 me-2" style ="line-height: 1.3; background-color:transparent"><?= htmlspecialchars($event['summary']) ?> <br> <small><?=htmlspecialchars($event['time'])?></small> <?=($event['location']=="Restaurant")? " | <small>".$event['part'].'</small>': ""?></span>
                                <div class="input-group-append">
                                <span class="icon icon-catering" title="Catering available">
                                <i class="bi bi-cup-hot-fill" style="color: <?=$event['catering']? '#FFF':'#555'?>;"></i>
                            </span>
                            <br>
                            <span class="icon icon-audiovisual" title="Audiovisual available">
                                <i class="bi bi-camera-reels-fill" style="color: <?=$event['audiovisual']? '#FFF':'#555'?>;"></i>
                            </span>
                                </div>
                            </div>    
                            
                            
                                
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php } // Fin du bloc if ?>
            <?php endforeach; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
