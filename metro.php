<?php
// Replace with your STIB/MIVB API key
$apiKey = '8c661a2b9a17f9d1349009ac3297c28fd96c963acaed7d7dbd19309e';
// Replace with the ID of the Schuman stop
$stopId = '8061';
$stopId2 = '8062';
// API URL for real-time waiting times
$apiUrl = "https://stibmivb.opendatasoft.com/api/explore/v2.1/catalog/datasets/waiting-time-rt-production/records?apikey=$apiKey&refine=pointid%3A$stopId";
$apiUrl2 = "https://stibmivb.opendatasoft.com/api/explore/v2.1/catalog/datasets/waiting-time-rt-production/records?apikey=$apiKey&refine=pointid%3A$stopId2";

$response = file_get_contents($apiUrl, false);
$data = json_decode($response, true);

$response2 = file_get_contents($apiUrl2, false);
$data2 = json_decode($response2, true);

// Configure the time zone
date_default_timezone_set('Europe/Brussels');

// Check if the data retrieval was successful
if (!$data || !isset($data['results'])) {
    echo "<div class='alert alert-danger'>Unable to retrieve metro data.</div>";
    exit;
}

if (!$data2 || !isset($data2['results'])) {
    echo "<div class='alert alert-danger'>Unable to retrieve metro data.</div>";
    exit;
}

// Extract passages by decoding the waiting times
$passages = [];
foreach ($data['results'] as $result) {
    $lineId = $result['lineid'];
    $passingTimes = json_decode($result['passingtimes'], true);
    foreach ($passingTimes as $passingTime) {
        $expectedTime = strtotime($passingTime['expectedArrivalTime']);
        $timeRemaining = ceil(($expectedTime - time()) / 60); // Remaining time in minutes

        $passages[] = [
            'line' => $lineId,
            'destination' => $passingTime['destination']['fr'], // Destination in French
            'expectedArrivalTime' => date('H:i', strtotime($passingTime['expectedArrivalTime'])),
            'timeRemaining' => $timeRemaining
        ];
    }
}
foreach ($data2['results'] as $result) {
    $lineId = $result['lineid'];
    $passingTimes = json_decode($result['passingtimes'], true);
    foreach ($passingTimes as $passingTime) {
        $expectedTime = strtotime($passingTime['expectedArrivalTime']);
        $timeRemaining = ceil(($expectedTime - time()) / 60); // Remaining time in minutes
        $passages[] = [
            'line' => $lineId,
            'destination' => $passingTime['destination']['fr'], // Destination in French
            'expectedArrivalTime' => date('H:i', strtotime($passingTime['expectedArrivalTime'])),
            'timeRemaining' => $timeRemaining
        ];
    }
}
array_multisort(array_column($passages, 'expectedArrivalTime'), SORT_ASC, $passages);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upcoming Metros - Schuman</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
            background-color: #333;
        }
        tr {
            min-height: 2em;
        }
        .badge {
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <h1 class="text-center mb-2">Upcoming Metros at Schuman Station</h1>
        <div class="last-update text-center mb-3">Last updated: <span id="lastUpdateTime"><?= date("H:i:s"); ?></span></div>
        
        <div class="table-responsive" style="font-size : 1.5em">
            <table class="table table-bordered table-hover text-white table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Line</th>
                        <th>Destination</th>
                        <th>Expected Arrival</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($passages as $passage): ?>
                        <tr class="table-dark">
                            <td>
                                <?= $passage['line'] == 1 ? '<span class="badge" style="background:#c4008f">'.htmlspecialchars($passage['line']).'</span>' : "" ?> 
                                <?= $passage['line'] == 5 ? '<span class="badge" style="background:#E6B012">'.htmlspecialchars($passage['line']).'</span>' : "" ?>
                            </td>
                            <td><?php echo htmlspecialchars($passage['destination']); ?></td>
                            <td>
                                <?= $passage['timeRemaining'] > 0 
                                    ? '<span class="badge bg-success">'.htmlspecialchars($passage['timeRemaining']) .' min </span>' 
                                    : '<span class="badge bg-warning"><i class="bi bi-arrow-down m-0"></i><i class="bi bi-arrow-down m-0"></i><i class="bi bi-arrow-down m-0"></i></span>' ?> 
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
