<?php
// URL of the RSS feed
$rssUrl = 'https://rss.nytimes.com/services/xml/rss/nyt/Europe.xml';

function parseNewsXMLFromURL($url) {
    // Vérifiez si l'URL est valide
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new Exception("URL invalide : $url");
    }

    // Récupérer le contenu XML depuis l'URL
    $xmlContent = @file_get_contents($url);

    if ($xmlContent === false) {
        throw new Exception("Impossible de récupérer le fichier XML depuis l'URL : $url");
    }

    // Charger le contenu XML dans un objet SimpleXMLElement
    $xml = @simplexml_load_string($xmlContent);

    if (!$xml) {
        throw new Exception("Le fichier récupéré n'est pas un XML valide : $url");
    }

    $articles = [];
    
    // Parcourir les articles (balises <item>)
    foreach ($xml->channel->item as $item) {
        $article = [
            'title' => (string) $item->title,
            'link' => (string) $item->link,
            'description' => (string) $item->description,
            'creator' => (string) $item->children('dc', true)->creator,
            'pubDate' => (string) $item->pubDate,
            'categories' => [], // Stocke les catégories
            'image' => null, // URL de l'image
            'image_description' => null, // Description de l'image
            'time_elapsed' => null // Temps écoulé depuis la publication
        ];

        // Récupérer les catégories
        foreach ($item->category as $category) {
            $article['categories'][] = (string) $category;
        }

        // Récupérer l'image si elle existe
        $namespaces = $item->getNamespaces(true); // Obtenir les namespaces
        if (isset($namespaces['media'])) {
            $media = $item->children($namespaces['media'])->content;
            if ($media && isset($media->attributes()->url)) {
                $article['image'] = (string) $media->attributes()->url;
            }
        }

         // Récupérer l'image et sa description si elles existent
         $namespaces = $item->getNamespaces(true); // Obtenir les namespaces
         if (isset($namespaces['media'])) {
             $media = $item->children($namespaces['media']);
             if (isset($media->description)) {
                 $article['image_description'] = (string) $media->description;
             }
         }

        $article['time_elapsed'] = getTimeElapsed((string) $item->pubDate);

        $articles[] = $article;
    }

     // Trier les articles par date (les plus récents en premier)
     usort($articles, function ($a, $b) {
        return strtotime($b['pubDate']) - strtotime($a['pubDate']);
    });

    return $articles;
}

function getTimeElapsed($pubDate) {
    $date = strtotime($pubDate); // Convertir la date en timestamp
    $now = time(); // Timestamp actuel
    $diff = $now - $date; // Différence en secondes

    if ($diff < 60) {
        return "Publié il y a moins d'une minute";
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "Publié il y a " . ($minutes == 1 ? "1 minute" : "$minutes minutes");
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "Publié il y a " . ($hours == 1 ? "1 heure" : "$hours heures");
    } else {
        $days = floor($diff / 86400);
        return "Publié il y a " . ($days == 1 ? "1 jour" : "$days jours");
    }
}

// Exemple d'utilisation
try {
     // Remplacez par votre URL
    $articles = parseNewsXMLFromURL($rssUrl);

    $articles = array_slice($articles, 0, 8);

    // Afficher les résultats
    /*foreach ($articles as $article) {
        echo "Titre : " . $article['title'] . PHP_EOL;
        echo "Lien : " . $article['link'] . PHP_EOL;
        echo "Description : " . $article['description'] . PHP_EOL;
        echo "Auteur : " . $article['creator'] . PHP_EOL;
        echo "Date de publication : " . $article['pubDate'] . PHP_EOL;
        echo "Catégories : " . implode(', ', $article['categories']) . PHP_EOL;
        echo "Image : " . $article['image'] . PHP_EOL;
        echo "----------------------------------------" . PHP_EOL;
    }*/

    //var_dump($articles);
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}


    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Latest News - NYT Europe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Verdana, sans-serif;
            background-color: #222;
            color: #fff;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            
        }
    
        .news-item {
            background-color: #333;
            padding: 20px;
            margin-top: 20px;
            border-radius: 5px;
        }
        .news-item h2 {
            font-size: 2em;
            margin-bottom: 10px;
        }
        .news-item p {
            font-size: 1.2em;
        }
        .news-item a {
            color: #1e90ff;
            text-decoration: none;
        }
        .news-item a:hover {
            text-decoration: underline;
        }
        .news-image {
            object-position: center;
            object-fit: contain;
            max-height : 200px;
            backdrop-filter: blur(5px) brightness(50%) grayscale(60%);
            border-radius: inherit;
        }
        .news-image-BG {
              /* Add the blur effect */

           
                /* Full height */
                max-height : 200px;
                height: 100%;

                /* Center and scale the image nicely */
                background-position: center;
                background-repeat: no-repeat;
                background-size: cover;
        }
    </style>
</head>
<body>
<div class="album py-5 w-100">
        <div class="container-fluid">
        <h1 class="text-center mb-2">Latest news <small style="font-size : 0.5em">from NYT</small></h1>
        <div class="last-update  text-center mb-3">Last update : <span id="lastUpdateTime"><?= date("H:i:s"); ?></span></div>
        <div class="row w-100">
        <?php foreach ($articles as $article):?>
            <div class="col-md-3 d-flex align-items-stretch ">
              <div class="card mb-3 box-shadow bg-dark text-white w-100">
                <div class="card-img-top p-0 m-0 border-0 news-image-BG" style="background-image:url('<?=$article['image']?>')">
                <img class="card-img-top img-thumbnail p-0 m-0 border-0 news-image bg-transparent" data-src="<?=$article['image'] . PHP_EOL?>" alt="<?= $article['image_description'] . PHP_EOL ?>" src="<?=$article['image'] . PHP_EOL?>" data-holder-rendered="true" >

                </div>
                
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><?= $article['title'] . PHP_EOL ?></h5>
                    <p class="card-text"><?= $article['description'] . PHP_EOL ?></p>
                    <div class="d-flex  justify-content-between align-items-center mt-auto text-end">
                        <small class=""><?=$article['time_elapsed'] . PHP_EOL?></small>
                    </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
</body>
</html>
