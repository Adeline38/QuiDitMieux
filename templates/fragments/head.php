<?php // FRAGMENT : head.php

// Rôle : Fournir les métadonnées et la feuille de styles communes aux pages du site.
// Paramètres : 
//      - $titre_page contient le titre préparé par le template de page
// Retour : Les balises nécessaires à la partie head du document HTML
?>

<?php // Les métadonnées définissent l'encodage, l'adaptation aux écrans et le titre propre à la page. ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= echapper_html($titre_page) ?></title>
<?php // Les ressources visuelles communes chargent la police retenue puis la feuille de styles compilée. ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&amp;display=swap" rel="stylesheet">
<link rel="stylesheet" href="public/assets/css/styles.css">
