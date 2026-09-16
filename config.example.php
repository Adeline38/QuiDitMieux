<?php //// CONFIG : config.exemple.php
// Rôle : Montrer les informations attendues dans le fichier local config.php sans fournir de secret.
// Paramètres : Chaque serveur doit compléter une copie locale avec ses propres accès MySQL.
// Retour : Les quatre informations de connexion deviennent disponibles pour init.php.



// Ces valeurs restent volontairement vides pour que ce fichier puisse être conservé dans Git.
$GLOBALS['bdd_host'] = '';
$GLOBALS['bdd_base'] = '';
$GLOBALS['bdd_user'] = '';
$GLOBALS['bdd_pwd'] = '';

// L'absence de balise fermante PHP évite un affichage accidentel en fin de fichier.
