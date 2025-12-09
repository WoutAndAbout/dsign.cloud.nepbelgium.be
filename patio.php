<?php
// Vérification de l'authentification API
$users = [
    'teosServer' => 'Enkip0EsLF6FK7QRI1Il88MB3oruRRH0jNQVZHsvVOX5FhuvsT',
    'dashboard' => 'uXOpj56Uoy1Yllg1yO5nVOBqzd6K4fyt7kTwqnDjE5UEapKgns',
    'tech'=> 'SvqDpl3rbNWhHmos3URFWGRWi57gnVzUg6wwXxhID2KbqMOzRp'
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


$defaultRooms = [
    "Polak" => [
        'arrow' => '',
        'level' => ''
    ],
    "Patio" => [
        'arrow' => '',
        'level' => ''
    ],
    "Maelbeek" => [
        'arrow' => '',
        'level' => ''
    ],
    "Passage" => [
        'arrow' => '',
        'level' => ''
    ],
    "Salon d'Honneur" => [
        'arrow' => '',
        'level' => ''
    ],
    "Mirrors Room" => [
        'arrow' => '',
        'level' => ''
    ],
    "Club" => [
        'arrow' => '',
        'level' => ''
    ],
    "Salon" => [
        'arrow' => '',
        'level' => ''
    ],
    "Restaurant (Bar Side)" => [
        'arrow' => '',
        'level' => ''
    ],
    "Restaurant (Window Side)" => [
        'arrow' => '',
        'level' => ''
    ],
    "Restaurant (full)" => [
        'arrow' => '',
        'level' => ''
    ],
    "Restaurant" => [
        'arrow' => '',
        'level' => ''
    ],
    "Rooftop terrace" => [
        'arrow' => '',
        'level' => ''
    ],
    "Magritte" => [
        'arrow' => '',
        'level' => ''
    ],
    "Ensor" => [
        'arrow' => '',
        'level' => ''
    ],
    "J@ys" => [
        'arrow' => '',
        'level' => ''
    ],
    "Broodthaers" => [
        'arrow' => '',
        'level' => ''
    ],
    "Media Room" => [
        'arrow' => '',
        'level' => ''
    ]
];


if (isset($_GET['side'])) {
    switch ($_GET['side']) {
        case 'L':
            $rooms = [
                "Polak" => ['arrow' => 'left', 'level' => ''],
                "Maelbeek" => ['arrow' => '90deg-left', 'level' => ''],
                "Passage" => ['arrow' => '90deg-left', 'level' => ''],
                "Salon d'Honneur" => ['arrow' => 'up', 'level' => ''],
                "Mirrors Room" => ['arrow' => '90deg-left', 'level' => '-2']
            ];
            break;
        case 'R':
            $rooms = [
                "Restaurant" => ['arrow' => 'right', 'level' => ''],
                "Club" => ['arrow' => 'right', 'level' => ''],
                "Salon" => ['arrow' => '90deg-right', 'level' => ''],
                "Salon d'Honneur" => ['arrow' => '90deg-right', 'level' => ''],
                "Mirrors Room" => ['arrow' => '90deg-right', 'level' => '-2']
            ];
            break;
        default:
            $rooms = $defaultRooms;
            break;
    }
} else {
    $rooms = $defaultRooms;
}

// Convertir les salles en JSON pour le JavaScript
$roomsJson = json_encode($rooms);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            font-family: Verdana;
            font-size: 2.5em;
            background-color: #222;
            background-image: url('assets/patio_bg.png');
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .dashboard {
            width: 100%;
            color: #fff;
            padding: 20px;
            height: 100vh;
        }
        .room {
            background-color: rgba(255,255,255,0.7);
            color: black;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        .arrow {
            display: block;
            color: #111;
        }
        #roomsContainer{
            max-height:70vh;
            overflow-y: hidden; /* Empêche le défilement manuel */
            position: relative;
        }
        .rotateArrow::after{
            transform: rotate(-90deg);
        }
    </style>
</head>
<body>
    <div class="container-fluid dashboard">
        <h1 class="text-center mb-2 display-1">Today's Events</h1>
        <div class="last-update text-center mb-3 display-6"><small>Last update: <span id="lastUpdateTime">--:--:--</span></small></div>
        <div class="row g-4 overflow-hidden mt-3" id="roomsContainer">
            <!-- Rooms will be dynamically inserted here via JavaScript -->
        </div>
    </div>
    <script>
        const allowedRooms = <?php echo $roomsJson; ?>; // Les salles autorisées avec leurs propriétés

        const roomsContainer = document.getElementById('roomsContainer');

        document.addEventListener('DOMContentLoaded', function () {
            const apiKey = 'xLrMCpR7uE2i6UI1kxeG65d9IIxxnckYvRZuk2hi161GXz1u4U'; // Remplacer avec votre clé API
            const roomsContainer = document.getElementById('roomsContainer');

            function fetchRoomStatus() {
                fetch('fetch_rooms.php?api_key=' + apiKey + '&nonEmptyOnly=yes'<?=isset($_GET['date'])? "+'&date=".$_GET['date']."'":""?>)
                    .then(response => response.json())
                    .then(data => {
                        const filteredData = filterRooms(data, allowedRooms);
                        updateRoomStatus(filteredData);
                        updateLastUpdateTime();

                        
                        setTimeout(enableInfiniteScroll(roomsContainer), 3000);

                        
                    })
                    .catch(error => console.error('Error fetching room status:', error));
            }

            function updateLastUpdateTime() {
                const now = new Date();
                const hours = now.getHours().toString().padStart(2, '0');
                const minutes = now.getMinutes().toString().padStart(2, '0');
                const seconds = now.getSeconds().toString().padStart(2, '0');
                document.getElementById('lastUpdateTime').textContent = `${hours}:${minutes}:${seconds}`;
            }

            function filterRooms(data, allowedRooms) {
                const filtered = {};
                Object.keys(data).forEach(room => {
                    if (allowedRooms[room]) { // Vérifie si la salle existe dans allowedRooms
                        filtered[room] = data[room];
                    }
                });
                return filtered;
            }

            function updateRoomStatus(data) {
                roomsContainer.innerHTML = ''; // Efface les salles actuelles

                Object.keys(data).forEach(room => {
                    const roomData = allowedRooms[room];
                    const roomDetails = data[room]; // Données de la salle (événements)

                    // Vérifier si la salle a des événements
                    if (roomDetails && roomDetails.summary) {
                        const roomDiv = document.createElement('div');
                        roomDiv.className = 'col-12 ';
                        roomDiv.innerHTML = `
                            <div class="room p-3 rounded text-center">
                                <h1 class="display-4">${room}</h2>
                                <p class="display-5">${roomDetails.summary}</p>
                                <p class="display-6">${roomDetails.title}</p>
                                <p class="arrow">${roomData.arrow ? `<img src="assets/arrow/arrow-${roomData.arrow}.png">` : ''} ${roomData.level || ''}</p>
                                <!--<p>${roomDetails.time}</p>-->
                            </div>
                        `;
                        roomsContainer.appendChild(roomDiv);
                    }
                });
            }

           

            function enableInfiniteScroll(container) {
                let scrollSpeed = 1; // Vitesse du défilement (pixels par intervalle)
                let scrollInterval = 40; // Intervalle en millisecondes

                // Duplication du contenu si nécessaire
                function duplicateContent() {
                    
                    const originalHeight = container.scrollHeight;
                    const visibleHeight = container.offsetHeight;
                    console.log(originalHeight + " ==> "  +visibleHeight);
                    if (originalHeight > visibleHeight) {
                        
                        const clone = container.innerHTML; // Dupliquer le contenu
                        container.innerHTML += clone; // Ajouter la duplication
                       
                    }
                }

                // Fonction pour gérer le défilement
                function scrollContent() {
                    container.scrollTop += scrollSpeed;

                    // Réinitialiser le défilement en haut si on atteint la fin
                    if (container.scrollTop >= container.scrollHeight / 2) {
                        container.scrollTop = 0;
                    }
                }

                // Démarrer
                duplicateContent();
                setInterval(scrollContent, scrollInterval);
            }

            // Activer le défilement infini une fois que le contenu est chargé
            document.addEventListener('DOMContentLoaded', function () {
                const roomsContainer = document.getElementById('roomsContainer');
                enableInfiniteScroll(roomsContainer);
            });




            // Initialisation
            fetchRoomStatus();

            
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
