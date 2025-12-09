<?php

$users = [
    'teosServer' => 'Enkip0EsLF6FK7QRI1Il88MB3oruRRH0jNQVZHsvVOX5FhuvsT'
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
    // On divise le contenu ICS en lignes individuelles
    $lines = explode("\n", $icsContent);
    $events = []; // Liste pour stocker tous les événements
    $currentEvent = null; // Événement courant en cours de traitement

    foreach ($lines as $line) {
        $line = trim($line); // Nettoyage des espaces

        // Commence un nouvel événement
        if (stripos($line, "BEGIN:VEVENT") === 0) {
            $currentEvent = [];
        }
        
        // Termine un événement et l'ajoute à la liste
        elseif (stripos($line, "END:VEVENT") === 0 && $currentEvent !== null) {
            $events[] = $currentEvent; // Ajout de l'événement à la liste
            $currentEvent = null; // Réinitialisation de l'événement courant
        }

        // Récupération des propriétés d'un événement
        elseif ($currentEvent !== null) {
            if (strpos($line, ":") !== false) { // Vérifie si la ligne contient un ':' (donc une clé et une valeur)
                list($key, $value) = explode(":", $line, 2); // Sépare la clé et la valeur
                $currentEvent[trim($key)] = trim($value); // Ajoute à l'événement courant
            }
        }
    }

    return $events; // Retourne la liste des événements
}

// Fonction pour parser les champs personnalisés de la description
function parseEventDescription($description) {
    // Structure initiale avec des valeurs par défaut
    $result = [
        'catering' => 'No',
        'audiovisual' => 'No',
        'title' => '',
        'room_composition' => '',
        'fixed_equipment' => [],
        'mobile_equipment' => [],
        'number_of_invitees' => '',
        'organisation_details' => [],
        'minister' => ''
    ];

    // Sépare la description par lignes, nettoie les lignes
    //$lines = array_map('trim', explode(separator: "\n", trim($description)));
    $lines = explode('\n',trim($description));
//echo"<pre>";
    //var_dump($lines);
    //echo"</pre> <br><br>";
    foreach ($lines as $line) {
        // Vérifie les catégories avec des expressions régulières
        if (preg_match('/^Catering\s*\?\s*:\s*(Yes|No)/i', $line, $matches)) {
            $result['catering'] = trim($matches[1]); // Affecte la valeur correspondante
        } elseif (preg_match('/^Audiovisual\s*\?\s*:\s*(Yes|No)/i', $line, $matches)) {
            $result['audiovisual'] = trim($matches[1]);
        } elseif (preg_match('/^Title\s*:\s*(.+)/i', $line, $matches)) {
            $result['title'] = trim($matches[1]);
        } elseif (preg_match('/^Room composition\s*:\s*(.+)/i', $line, $matches)) {
            $result['room_composition'] = trim($matches[1]);
        } elseif (preg_match('/^Fixed equipment\s*:\s*(.+)/i', $line, $matches)) {
            $equipments = array_map('trim', explode(",", $matches[1])); // Sépare les équipements par virgule
            $result['fixed_equipment'] = $equipments; // Affecte les équipements
        } elseif (preg_match('/^Mobile equipment\s*:\s*(.+)/i', $line, $matches)) {
            $equipments = array_map('trim', explode(",", $matches[1])); // Sépare les équipements mobiles
            $result['mobile_equipment'] = $equipments;
        } elseif (preg_match('/^Number of invitees\s*:\s*(.+)/i', $line, $matches)) {
            $result['number_of_invitees'] = trim($matches[1]);
        } elseif (preg_match('/^Organisation details\s*:\s*(.+)/i', $line, $matches)) {
            // Sépare les détails par <br /> et nettoie
            $details = array_map('trim', explode("<br />", $matches[1])); 
            $result['organisation_details'] = $details;
        } elseif (preg_match('/^Minister\s*\?\s*:\s*(.+)/i', $line, $matches)) {
            $result['minister'] = trim($matches[1]);
        } else {
            // Gestion des détails additionnels qui n'ont pas de préfixe
            // Cela permet de capturer des détails additionnels sans préfixe
            if (!empty(trim($line))) {
                $result['organisation_details'][] = trim($line); // Ajoute toute autre information au tableau organisation_details
            }
        }
    }

    return $result; // Retourne le tableau avec les résultats analysés
}

// Fonction pour nettoyer les clés afin de les rendre valides en tant que balises XML
function cleanKey($key) {
    return preg_replace('/[^a-z0-9_]/', '_', strtolower($key)); // Remplace les caractères non valides par des underscores
}

// Fonction pour échapper les caractères spéciaux dans les valeurs XML
function escapeXML($value) {
    return is_array($value) ? implode(', ', array_map('htmlspecialchars', $value)) : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Fonction pour convertir les événements ICS en XML avec parsing de la description
function convertToXML($events) {
    $xml = new SimpleXMLElement('<events/>'); // Crée un nouvel élément XML racine

    foreach ($events as $event) {
        $eventXML = $xml->addChild('event'); // Ajoute un nouvel événement dans le XML

        foreach ($event as $key => $value) {
            if (strtolower($key) === 'summary') {
                // Supprimer "Booking - " du résumé si présent
                $value = str_replace('Booking - ', '', $value);
                $eventXML->addChild(cleanKey($key), escapeXML($value)); // Ajouter le résumé modifié
            } elseif (strtolower($key) === 'description') {
                $parsedDescription = parseEventDescription($value); // Analyse la description
                $descriptionXML = $eventXML->addChild('description_details'); // Crée un élément pour les détails

                // Ajoutez chaque champ de description
                foreach ($parsedDescription as $descKey => $descValue) {
                    if (is_array($descValue) && !empty($descValue)) { // Si c'est un tableau (comme les équipements)
                        $descFieldXML = $descriptionXML->addChild(cleanKey($descKey)); // Crée un élément pour le champ
                        foreach ($descValue as $descItem) {
                            $descFieldXML->addChild('item', escapeXML($descItem)); // Ajoute chaque item
                        }
                    } else {
                        $descriptionXML->addChild(cleanKey($descKey), escapeXML($descValue)); // Ajoute les valeurs simples
                    }
                }
            } else {
                $eventXML->addChild(cleanKey($key), escapeXML($value)); // Ajoute les autres clés d'événements
            }
        }
    }

    return $xml->asXML(); // Retourne le XML sous forme de chaîne
}

// URL du fichier ICS
$icsUrl = 'https://ipc.diesesoftware.com/sp_ics-AGsHIFI9-CTgBZw==-VG4HYAcy-7,11,8,1,3,4,5,6,10-0.ics'; // Remplacez par l'URL ICS souhaitée

// Récupération du contenu ICS depuis l'URL
$icsContent = file_get_contents($icsUrl);

if ($icsContent !== false) {
    // Parsing du contenu ICS
    $events = parseICS($icsContent);

    // Conversion en XML avec parsing des descriptions
    $xmlContent = convertToXML($events);

    // Affichage du contenu XML
    header('Content-Type: application/xml');
    echo $xmlContent;
} else {
    echo "Erreur: Impossible de récupérer le fichier ICS.";
}
?>
