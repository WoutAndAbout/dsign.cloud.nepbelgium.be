<?php

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

// Configuration du fuseau horaire
date_default_timezone_set('Europe/Brussels');

// URL de l'API iRail pour les prochains trains à la gare de Schuman
$station = 'Schuman';
$apiUrl = "https://api.irail.be/liveboard/?station=$station&fast=true&format=json";

// Récupération des données depuis l'API iRail
$response = file_get_contents($apiUrl);
$data = json_decode($response, true);

// Vérification de la récupération des données
if (!$data || !isset($data['departures']['departure'])) {
    echo "<div class='alert alert-danger'>Unable to retrieve train data.</div>";
    exit;
}

// Limitation aux 16 premiers trains
$trains = array_slice($data['departures']['departure'], 0, 16);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upcoming Trains - Schuman</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Verdana;
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
        .table {
            background-color: #333; /* Dark background for the table */
        }
        tr {
            min-height: 2em;
        }
        .room {
            background-color: #333;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        .badge {
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <h1 class="text-center mb-2">Upcoming Trains at Schuman Station</h1>
        <div class="last-update text-center mb-3">Last update: <span id="lastUpdateTime"><?= date("H:i:s"); ?></span></div>
        
        <div class="table-responsive" style="font-size : 1.5em">
            <table class="table table-bordered table-hover text-white table-striped">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 250px">Departure Time</th>
                        <th>Destination</th>
                        <th class="text-center" style="width: 120px">Platform</th>
                        <th class="text-center" style="width: 170px">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trains as $train): ?>
                        <?php 
                        $delayMinutes = $train['delay'] / 60;
                        $cancelled = isset($train['canceled']) && $train['canceled'] === "1";
                        $platform = $train['platform'] !== '?' ? htmlspecialchars($train['platform']) : '';
                        ?>
                        <tr class="table-dark">
                            <td class="table-dark">
                                <?php echo date('H:i', $train['time']); ?>
                                <?php if ($delayMinutes > 0): ?>
                                    <span class="badge bg-warning  text-dark ms-2">+<?php echo $delayMinutes; ?> min</span>
                                <?php endif; ?>
                            </td>
                            <td class="table-dark">
                                <?php echo htmlspecialchars($train['station']); ?>
                            </td>
                            <td class="table-dark text-center"><?php echo $platform; ?></td>
                            <td class="table-dark text-center">
                                <?php if ($cancelled): ?>
                                    <span class="badge bg-danger">Cancelled</span>
                                <?php elseif ($delayMinutes > 0): ?>
                                    <span class="badge bg-warning text-dark">Delayed</span>
                                <?php else: ?>
                                    <span class="badge bg-success">On Time</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
