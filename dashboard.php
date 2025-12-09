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

// Liste des salles disponibles
$rooms = [
    "Polak", "Patio",  "Maelbeek", "Passage", 
    "Salon d'Honneur", "Mirrors Room", "Club","Salon", 
    "Restaurant (Bar Side)", "Restaurant (Window Side)", "Restaurant (full)", "Restaurant",
    "Rooftop terrace", "Magritte", "Ensor", "J@ys", 
    "Broodthaers", "Media Room"
];
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
            font-family : Verdana;
            background-color: #222;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .dashboard {
            width: 100%;
            background-color: #222;
            color: #fff;
            padding: 20px;
            height: 100vh;
        }
        .room {
            background-color: #333;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        .free {
            background-color: #4caf50;
        }
        .busy {
            background-color: #f44336;
        }
        .waiting1h {
            background-color: #f48f36;
        }
        .waitingToday {
            background-color: #afa54c;
        }
        .past1h {
            background-color: #8a2442;
        }
        .icons {
            font-size: 1.2em;
        }
        .icon {
            margin-right: 8px;
            display: inline-block;
        }
        .ticker-container {
            width: 100%;
            overflow: hidden;
            white-space: nowrap;
            position: relative;
        }
        .animate-ticker {
            display: inline-block;
            animation: scrollText 5s linear infinite alternate;
        }

        @keyframes scrollText {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(var(--scroll-distance));
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid dashboard">
        <h1 class="text-center mb-2">Today's Events</h1>
        <div class="last-update  text-center mb-3">Last update : <span id="lastUpdateTime">--:--:--</span></div>
        <div class="row g-4">
            <?php foreach ($rooms as $room): ?>
                <div class="col-12 col-md-6 col-lg-3 align-self-center">
                    <div class="room free p-3 rounded text-center" data-room="<?= htmlspecialchars($room) ?>">
                        <h2 class="h5"><?= htmlspecialchars($room) ?></h2>
                        
                        <p class="mb-1 ticker-container">
                            <strong class="summary ticker">Loading...</strong>
                        </p>
                        <p class="small time">--:-- ➔ --:--</p>
                        <div class="icons">
                            <span class="icon icon-catering" title="Catering available">
                                <i class="bi bi-cup-hot-fill" style="color: #555;"></i>
                            </span>
                            <span class="icon icon-audiovisual" title="Audiovisual available">
                                <i class="bi bi-camera-reels-fill" style="color: #555;"></i>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="col-12 col-md-6 col-lg-3 align-self-center h-100" style="min-height:157.8px">
                    <div class="p-3 rounded text-left" style="background : #333" data-room="legend">
                        <h2 class="h5 mb-4 text-center">Legend</h2>
                        <div class="row">
                        <div class="col-6">
                            <p class="small time"><span class="badge rounded-pill busy me-2" style="min-height:1.5em; min-width :1.5em"> </span> Busy</p>
                        </div>
                        <div class="col-6">
                            <p class="small time"><span class="badge rounded-pill waitingToday me-2" style="min-height:1.5em; min-width :1.5em"> </span> Event today</p>
                        </div>
                        <div class="col-6">
                            <p class="small time"><span class="badge rounded-pill waiting1h me-2" style="min-height:1.5em; min-width :1.5em"> </span> Start in less than 1h</p>
                        </div>
                        <div class="col-6">
                            <p class="small time"><span class="badge rounded-pill past1h me-2" style="min-height:1.5em; min-width :1.5em"> </span> Ended less than 1h ago </p>
                        </div>
                            
                        </div>
                       
                        
                    </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3 align-self-center h-100" style="min-height:157.8px">
                <div class="p-3 rounded text-center" style="background : #fff" data-room="logo">
                    <img src="assets/logo.png" alt="logo" class="img-fluid w-100 h-100" >
                </div>
                
            </div>

        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const apiKey = 'xLrMCpR7uE2i6UI1kxeG65d9IIxxnckYvRZuk2hi161GXz1u4U'; // Replace with your actual API key

            function fetchRoomStatus() {
                fetch('fetch_rooms.php?api_key=' + apiKey)
                    .then(response => response.json())
                    .then(data => {
                        updateRoomStatus(data);
                        updateLastUpdateTime();
                        scheduleNextFetch();
                        setTimeout(setTicker, 3000);
                        
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


            function updateRoomStatus(roomStatus) {
                Object.keys(roomStatus).forEach(room => {
                    const details = roomStatus[room];
                    const roomDiv = document.querySelector(`[data-room="${room}"]`);
                    if (roomDiv) {
                        roomDiv.className = `room ${details.status} p-3 rounded text-center`;
                        roomDiv.querySelector('.summary').innerHTML = details.summary || '<br>';
                        roomDiv.querySelector('.time').innerHTML = details.time || '<br>';

                        const cateringIcon = roomDiv.querySelector('.icon-catering i');
                        cateringIcon.style.color = details.catering ? 'white' : '#555';

                        const audiovisualIcon = roomDiv.querySelector('.icon-audiovisual i');
                        audiovisualIcon.style.color = details.audiovisual ? 'white' : '#555';
                    }
                });
            }

            function scheduleNextFetch() {
                const now = new Date();
                const nextMinute = new Date(now.getTime());
                nextMinute.setSeconds(1); // Assurez-vous que l'exécution se fait à la première seconde de la minute suivante
                nextMinute.setMilliseconds(0);
                nextMinute.setMinutes(now.getMinutes() + 1);

                const timeUntilNextFetch = nextMinute - now;
                setTimeout(fetchRoomStatus, timeUntilNextFetch);
            }

            // Fetch data every 5 minutes
            //setInterval(fetchRoomStatus, 60000); // 300000ms = 5 minutes
            fetchRoomStatus(); // Initial fetch


            function setTicker(){
                const tickers = document.querySelectorAll('.ticker');

            tickers.forEach(ticker => {
                
                const container = ticker.parentElement;
                
                // Vérifier si le texte dépasse la largeur du conteneur
                if (ticker.offsetWidth > container.offsetWidth) {
                    const scrollDistance = container.offsetWidth - ticker.offsetWidth + 'px';
                    ticker.style.setProperty('--scroll-distance', scrollDistance);
                    ticker.classList.add('animate-ticker');
                }
            });
            }
            
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
