<?php // MODÈLE : _model.php

// Rôle : Fournir les propriétés et les traitements communs aux modèles enfants du projet.
// Paramètres : Les modèles enfants précisent leur table, leurs champs et les données de leurs traitements.
// Retour : La classe fournit des objets persistants et des résultats issus de requêtes préparées.

/*
 * Classe _model : classe générique pour gérer un objet quelconque du MCD 
 * Cette classe contient les outils communs à tous les modèles du projet
 * Un modèle représente une table de la base de données
 * Un objet créé avec ce modèle représente une ligne de cette table
 *
 * Chaque classe enfant doit préciser :
 * - le nom de sa table dans $table
 * - la liste de ses colonnes d'attributs, sauf "id", dans $fields
 */

class _model {

////// INFORMATIONS SUR LE MODÈLE // Attributs (protégés)

    // Description du modèle
    // Nom de la table utilisée. La classe enfant doit remplacer la chaîne vide
    protected $table = ""; // On devra indiquer la table en surchargeant l'attribut table
    // Liste des colonnes de la table, sans la colonne "id"
    protected $fields = []; // ou colonnes d'attributs. On devra indiquer les champs (or id) en surchargeant l'attribut fields
    
////// DONNÉES CHARGÉES DANS L'OBJET

    // Identifiant de la ligne. La valeur 0 signifie que l'objet n'existe pas en BDD
    protected $id = 0;      // initialisation du stockage de l'id
    // Valeurs des autres colonnes. Exemple : ["pseudo" => "Adeline"]
    protected $values = []; // Stockage des valeurs chargées


    public function __construct($id = null) { // CONSTRUCTEUR : méthode qui se déclenche après instanciation - après new
        // Rôle : Créer un objet vide ou charger la ligne correspondant à un identifiant.
        // Paramètres : 
        //      - ce que l'on veut en fonction de ce que l'on veut faire à la constuction
        //      - exemple $id est l'identifiant à charger (facultatif)
        // Retour : Aucun. Un constructeur initialise l'objet courant

        // Si un identifiant est donné, on charge immédiatement la ligne correspondante
        
        if (!is_null($id)) {    // si l'id n'est pas nulle
            $this->load($id);   // charger la ligne de l'id testé 
            // résultat dans le new objet → coquille vide, à enrichir ensuite
        } // Sinon, l'objet reste vide et pourra être rempli plus tard avec set()
    }

    public function is() {
        // Rôle : Indiquer si l'objet représente une ligne existante de la base de données.
        // Paramètres : Aucun 
        // Retour : true si l'objet possède un identifiant, sinon false.

        // Un identifiant vide ou égal à 0 signifie que l'objet n'existe pas en BDD
        return !empty($this->id);
    }

    public function id() {
        // Rôle : Lire l'identifiant de l'objet courant
        //        récupérer la valeur de la clé primaire
        // Paramètres : Aucun
        // Retour : la valeur de la clé, de l'identifiant de l'objet ou zéro lorsque l'objet n'est pas chargé (s'il n'est pas défini)

        
        if (isset($this->id)) return $this->id;
        else return 0;
    }

    // EXÉCUTER ET LIRE DES REQUÊTES SQL - MOULINETTE

    protected function execute($sql, $param = []) {
        // Rôle : Préparer et exécuter une requête SQL avec ses valeurs séparées.
        // Paramètres : $sql contient la requête et $param contient les valeurs des paramètres préparés
        //      $sql : texte de la requête texte SQL (avec des :xxxx)
        //             il peut contenir des repères comme :id.
        //      $param : tableau donnant les valeurs de :xxxx, valeurs à placer dans les repères. Exemple : [":id" => 5].
        // Retour : La requête PDO exécutée (objet donné par $bdd->prepare()) retourne l'objet, ou false si sa préparation ou son exécution échoue

        // Récupération de la variable globale dans laquelle on ouvre la connexion à la BDD
        global $bdd;

        // PDO prépare la requête avant de recevoir les vraies valeurs
        $req = $bdd->prepare($sql);
        // Si la préparation échoue, on arrête la méthode
        if ($req === false) {
            return false;
        }

        // On exécute la requête avec les valeurs contenues dans $param
        if (! $req->execute($param)) {
            return false;
        }

        // On retourne la requête pour pouvoir ensuite lire son résultat
        return $req;
    }

    protected function loadFromTab($tab) {
        // Rôle : Remplir l'objet courant avec les valeurs autorisées d'un tableau associatif.
        //        valoriser chaque nom des attributs (id, nom, ....) à partir des éléments (couples clé/valeur) qui constituent le tableau associatif où les clés correspondent aux noms des attributs de l'objet (généralement les colonnes d'un enregistrement en base de données)
        //        l'identifiant est traité séparément
        // Paramètres : $tab contient les noms des colonnes et leurs valeurs
        //              tableau indexé dont les clés sont des noms d'attributs (des colonnes de la table projet)
        // Exemple : ["id" => 2, "pseudo" => "Adeline"]
        // Retour : true lorsque les données autorisées ont été copiées dans l'objet (chargement est terminé)

        // On parcourt uniquement les champs autorisés dans le modèle
        // Donc pour chacun des attribut décrits dans $this->fields
        foreach($this->fields as $nomAttribut) {
            // Vérifie que l'attribut existe dans le tableau fourni
            // Si le tableau contient ce champ, on copie sa valeur dans l'objet
            if (array_key_exists($nomAttribut, $tab)) {
                // Affecte la valeur du tableau à l'attribut correspondant
                $this->values[$nomAttribut] = $tab[$nomAttribut];
            }
        }
        // Traitement spécifique de l'identifiant de l'objet
        // L'identifiant est rangé séparément des autres champs
        if (array_key_exists("id", $tab)) {
            $this->id = $tab["id"];
        }

        // Indique que le chargement s'est terminé correctement  
        // Toutes les données présentes et autorisées ont été copiées
        return true;
    }

    // OBTENIR DES DONNÉES

    // TABLEAU DE PLUSIEURS LIGNES
    protected function sqlToTab($sql, $param = []) {
        // Rôle : Transformer plusieurs lignes obtenues par une requête SELECT en objets du modèle enfant.
        // Paramètres : $sql contient la requête et $param contient ses valeurs préparées.
        //      $sql : texte SQL de la requête SELECT à exécuter
        //      $param : valeurs à placer dans les repères de la requête
        // Retour : tableau d'objets indexé par leur identifiant, ou un tableau vide en cas d'absence ou d'échec.

        // On exécute la requête SELECT
        $req = $this->execute($sql, $param);

        if ($req === false) {
            return [];
        }

        $lignes = $req->fetchAll(PDO::FETCH_ASSOC);
        // FETCH_ASSOC donne un tableau où le nom de chaque colonne sert de clé
        $resultat = []; // Pour construire le résultat

        // On transforme chaque ligne trouvée en un objet
        foreach($lignes as $ligne) {
            // "new static()" crée un objet de la vraie classe utilisée
            // Par exemple, un appel depuis message crée un objet message
            $objet = new static();
            // On remplit l'objet avec les données de la ligne
            $objet->loadFromTab($ligne);
            // L'identifiant devient la clé de l'objet dans le tableau résultat
            $resultat[$objet->id] = $objet; // on l'ajoute dans le tableau résultat
        }

        return $resultat;
    }

    // TABLEAU DE PLUSIEURS LIGNES DANS UNE AUTRE TABLE
    protected function sqlToTabClasse($sql, $param, $classe) {
        // Rôle : Transformer plusieurs lignes obtenues par une requête en objets d'une classe choisie.
        // Paramètres : $sql contient la requête, $param ses valeurs et $classe le modèle à créer.
        //      $sql : requête SELECT à exécuter
        //      $param : valeurs à placer dans les repères de la requête
        //      $classe : nom de la classe des objets à créer
        // Retour : Un tableau d'objets de la classe demandée, ou un tableau vide en cas d'absence ou d'échec.
        
        $req = $this->execute($sql, $param);

        $resultat = [];

        if ($req === false) {
            return $resultat;
        }

        // fetch() lit une ligne à chaque tour La boucle s'arrête à la dernière ligne
        while ($ligne = $req->fetch(PDO::FETCH_ASSOC)) {

            // On crée un objet avec le nom de classe reçu dans $classe
            $objet = new $classe();
            $objet->loadFromTab($ligne);

            // Les crochets ajoutent l'objet à la fin du tableau
            $resultat[] = $objet;
        }

        return $resultat;
    }

    // TABLEAU D'UNE SEULE LIGNE
    protected function sqlToObject($sql, $param = []) {
        // Rôle : Transformer la première ligne (un seul objet) obtenue par une requête en objet du modèle enfant.
        // Paramètres : $sql contient la requête et $param contient ses valeurs préparées.
        // Retour : Un objet du modèle enfant (le premier objet trouvé), ou null si aucune ligne n'est disponible.

        // On transforme d'abord toutes les lignes trouvées en objets
        $tab = $this->sqlToTab($sql, $param);

        // Un tableau vide signifie que la requête n'a trouvé aucune ligne
        if (empty($tab)) {
            return null;
        }

        // array_shift() retire et retourne le premier objet du tableau
        return array_shift($tab);
    }

    protected function sqlToLigne($sql, $param = []) {
        // Rôle : Lire une seule ligne SQL sans la transformer en objet du modèle.
        // Paramètres : $sql contient la requête et $param contient ses valeurs préparées.
        // Retour : Un tableau associatif représentant la ligne, ou null si elle est absente ou inaccessible.

        $req = $this->execute($sql, $param);

        if ($req === false) {
            return null;
        }

        $ligne = $req->fetch(PDO::FETCH_ASSOC);

        if ($ligne === false) {
            return null;
        }

        return $ligne;
    }


    // GETTERS : RÉCUPÉRER LA VALEUR DES CHAMPS
    // LIRE ET MODIFIER LES CHAMPS DE L'OBJET
    public function get($nomChamp) {
        // Rôle : Obtenir, lire la valeur d'un champ autorisé de l'objet courant.
        // Paramètres : $nomChamp est le nom du champ à lire ($nomChamp contient le nom du champ demandé)
        // Retour : La valeur du champ, ou null si le champ est interdit ou vide.

        // in_array() vérifie que le nom se trouve dans la liste des champs autorisés
        // La valeur du champ est dans $this->values[$nomChamp] si la valeur est chargée
        if (! in_array($nomChamp, $this->fields)) {
            return null;
        }
        
        // On retourne la valeur seulement si elle a déjà été chargée ou définie
        if (isset($this->values[$nomChamp])) {
            return $this->values[$nomChamp];
        } else {
            return null;
        }
    }

    /* Exemple simple 'function estDansTableau()' pour comprendre in_array()
     * Cette fonction ferait presque le même travail à la main
     * Elle n'est pas exécutée, car elle se trouve dans un commentaire
     * 
    function estDansTableau($valeur, $tableau) {
        // Rôle : indiquer si une valeur est dans un tableau (est un élement du tableau)
        // Paramètres :
        //      $valeur : valeur cherchée
        //      $tableau : tableau dasn lequel chercher

        // On regarde chaque case du tableau, pour chque élément du tableau
        foreach($tableau as $cellule) {
          // Si cette case contient la valeur cherchée, on a trouvé
          if ($cellule == $valeur) {
                return true;
            }
        }
        // Le tableau entier a été parcouru sans trouver la valeur
        return false;
    }
    */


    // SETTERS : MODIFIER LES CHAMPS
    public function set($nomChamp, $valeur)  {
        // Rôle : Donner une valeur à un champ autorisé de l'objet courant, sauf à l'identifiant
        // Paramètres : $nomChamp désigne le champ et $valeur contient sa nouvelle valeur
        //      $nomChamp : nom du champ à modifier
        //      $valeur : nouvelle valeur du champ
        // Retour : true si le champ est autorisé, sinon false

        // Seuls les champs déclarés dans $fields peuvent être modifiés
        // On vérifie que le champ que l'on demande existe
        if (! in_array($nomChamp, $this->fields)) {
            // Pas un champ :
            return false;
        }

        // On range la valeur sous le nom du champ, on met la valeur dans $this->values[nomChamp];
        $this->values[$nomChamp] = $valeur;

        return true;
    }


    // SYNCHRONISER L'OBJET AVEC LA BASE DE DONNÉES
    public function load($id) {
        // Rôle : Charger dans l'objet la ligne qui possède l'identifiant demandé.
        // Paramètres : $id est l'identifiant de la ligne à charger ($id contient l'identifiant recherché)
        // Retour : true si la ligne est chargée, false en cas d'erreur ou d'absence

        // listFieldsForSelect() donne les colonnes à récupérer
        // :id est un repère qui recevra ensuite la valeur de $id
        $sql = "SELECT " . $this->listFieldsForSelect() . " FROM `$this->table` WHERE id = :id ";
        $paramValues = [ ":id" => $id ];

        // On prépare et on exécute la requête
        $req = $this->execute($sql, $paramValues);

        // On arrête si la requête n'a pas pu être préparée
        if ($req === false) {
            return false;
        }

        // On récupère les lignes retournées par la requête sous forme de tableaux associatifs
        $lignes = $req->fetchAll(PDO::FETCH_ASSOC);

        // Si on n'a pas de lignes 
        // Un identifiant absent de la table ne donne aucune ligne
        if (empty($lignes)) return false;

        // On a au moins une ligne
        // Un identifiant doit être unique : on charge la première ligne trouvée (on transfère le 1er élément dans les attributs de l'objet courant)
        return $this->loadFromTab($lignes[0]);
    }


    // CRUD : CRÉER, LIRE, MODIFIER ET SUPPRIMER DES DONNÉES
    public function insert() {
        // Rôle : Insérer en base, ajouter une nouvelle ligne avec les valeurs de l'objet courant (on devra aussi mettre à jour l'id dans l'objet)
        // Paramètres : Aucun paramètre car les valeurs sont déjà enregistrées dans l'objet
        // Retour : true si l'ajout réussit, false si la requête échoue

        // listFieldsForSet() fabrique un texte comme `pseudo` = :pseudo
        $sql = "INSERT INTO `$this->table` SET " . $this->listFieldsForSet();
        $param = [];
        // On prépare la valeur de chaque repère utilisé dans la requête
        foreach($this->fields as $nomChamp) { 
            // Si le champ possède une valeur, on l'ajoute aux paramètres
            if (isset( $this->values[$nomChamp])) $param[":$nomChamp"] = $this->values[$nomChamp];
            // Sans valeur, null représente une valeur absente
            else $param[":$nomChamp"] = null;
        }
        // On prépare et on exécute la requête INSERT
        $req = $this->execute($sql, $param);

        // On arrête si la préparation a échoué
        if ($req === false) {
            return false;
        }

        // PDO donne l'identifiant que la BDD vient de créer
        global $bdd;

        $this->id = $bdd->lastInsertId();

        return true;
    }

    public function update() {
        // Rôle : Enregistrer en base les valeurs actuelles d'un objet existant, mettre à jour la ligne correspondant à l'objet courant dans la BDD 
        // Paramètres : Aucun paramètre car l'identifiant et les valeurs appartiennent à l'objet
        // Retour : true si la modification réussit, sinon false

        // Un objet sans identifiant ne correspond à aucune ligne à modifier.
        if (!$this->is()) {
            return false;
        }

        // Chaque champ doit avoir été chargé avant la modification.
        // array_key_exists() accepte aussi une valeur NULL provenant de la base.
        foreach ($this->fields as $nomChamp) {
            if (!array_key_exists($nomChamp, $this->values)) {
                return false;
            }
        }

        // Seule la ligne qui possède l'identifiant de l'objet sera modifiée
        $sql = "UPDATE `$this->table` SET " . $this->listFieldsForSet() . " WHERE id = :id";
        $param = [ ":id" => $this->id ];
        // On ajoute ensuite la valeur de chaque champ aux paramètres
        foreach($this->fields as $nomChamp) {
            $param[":$nomChamp"] = $this->values[$nomChamp];
        }

        // On prépare et on exécute la requête UPDATE
        $req = $this->execute($sql, $param);

        if ($req === false) {
            return false;
        }

        // La modification a réussi
        return true;
    }

    public function delete() {
        // Rôle : Supprimer de la base la ligne représentée par l'objet courant.
        // Paramètres : Aucun paramètre car l'identifiant appartient à l'objet
        // Retour : true si la suppression réussit, sinon false
        
        // :id recevra l'identifiant de la ligne à supprimer
        $sql = "DELETE FROM `$this->table`  WHERE id = :id";
        $param = [ ":id" => $this->id ];

        // On prépare et on exécute la requête DELETE
        $req = $this->execute($sql, $param);

        if ($req === false) {
            return false;
        } 

        // L'objet ne représente plus une ligne de la BDD : son identifiant redevient 0
        $this->id = 0;

        return true;
    }
    
    // RÉCUPERATION DE LISTE(S)
    public function listAll() {
        // Rôle : Lister toutes les lignes de la table du modèle enfant sous forme d'objets
        // Paramètres : Aucun paramètre n'est nécessaire
        // Retour : Un tableau d'objets du modèle enfant indexé par leur identifiant

        // Sans WHERE, le SELECT demande toutes les lignes de la table
        $sql = "SELECT " . $this->listFieldsForSelect() . " FROM `$this->table`";
        $param = [  ];

        // sqlToTab() exécute la requête et transforme ses lignes en objets
        // on fabrique un tableau d'objets à partir de cette requête
        return $this->sqlToTab($sql, $param);
    }

    // UTILES POUR SELECT

    // CONSTRUIRE DES MORCEAUX DE REQUÊTES SQL
    protected function listFieldsForSelect() {
        // Rôle : Construire la liste des colonnes autorisées pour une requête SELECT (à placer après SELECT)
        // Paramètres : Aucun paramètre car les champs sont définis par le modèle enfant
        // Retour : Une chaîne SQL contenant l'identifiant et les champs autorisés (texte comme `id`,`pseudo`,`email`)

        // Toutes les tables possèdent d'abord une colonne id
        $sql = "`id`";
        // On ajoute ensuite tous les champs déclarés dans $fields
        foreach ($this->fields as $nomChamp) {
            // Ajouter au text $sql ', `nomChamp`'
            $sql .= ",`$nomChamp`";
        }
        return $sql;
    }

    protected function listFieldsForSet() {  
        // Rôle : Construire la liste des colonnes et paramètres utilisés par INSERT ou UPDATE (associer chaque colonne à son repère SQL)
        // Paramètres : Aucun paramètre car les champs sont définis par le modèle enfant
        // Retour : Une chaîne associant chaque colonne à son paramètre préparé, texte à mettre dans la requête comme `pseudo` = :pseudo,`email` = :email
        
        // Chaque case du tableau contient une association colonne-repère, on va faire un tableau des éléments `champs1` = :champ1
        $tab = [];
        foreach($this->fields as $nomChamp) {
            $tab[] = "`$nomChamp` = :$nomChamp";
        }
        // implode() rassemble les cases avec une virgule entre chacune
        return implode(",", $tab);
    }

    public function findBy($champ, $valeur) {
        // Rôle :  Trouver un objet (une ligne) grâce à la valeur de l'un de ses champs
        // Paramètres : $champ contient le nom de la colonne et $valeur contient la valeur recherchée
        //      $champ : nom de la colonne dans laquelle chercher
        //      $valeur : valeur recherchée dans cette colonne
        // Retour : Un objet rempli si une ligne est trouvée, sinon false

        // Un nom de colonne ne peut pas être protégé par un paramètre PDO.
        // Il doit donc appartenir à la liste des colonnes autorisées du modèle.
        $champs_autorises = $this->fields;
        $champs_autorises[] = 'id';

        if (!in_array($champ, $champs_autorises, true)) {
            return false;
        }

        // Construction de la requête
        // On demande les lignes dont le champ contient la valeur recherchée
        $sql = "SELECT ".$this->listFieldsForSelect()."
            FROM `$this->table`
            WHERE `$champ` = :valeur
        ";

        // :valeur sera remplacé par $valeur pendant l'exécution
        $param = [":valeur" => $valeur];

        // On prépare et on exécute la requête
        $req = $this->execute($sql, $param);

        // On arrête si la requête n'a pas pu être préparée
        if ($req === false) return false;

        // On lit uniquement la première ligne trouvée
        $ligne = $req->fetch(PDO::FETCH_ASSOC);

        // fetch() retourne false lorsqu'il n'y a aucune ligne
        if ($ligne === false) return false;

        // On crée un objet de la classe qui a appelé cette méthode, création d'un objet de la classe courante
        $objet = new static();

        // On remplit l'objet avec les données trouvées, chargement des données
        $objet->loadFromTab($ligne);

        // On retourne l'objet maintenant prêt à être utilisé
        return $objet;
    }
  
    public function modif($tab) {
        // Rôle : Modifier plusieurs champs autorisés de l'objet courant, puis enregistrer les modifications de l'objet dans la BDD
        // Paramètres : $tab contient les noms des champs et leurs nouvelles valeurs ($tab associe chaque nom de champ à sa nouvelle valeur)
        // Exemple : ["pseudo" => "Lina", "email" => "l@exemplefr"]
        // Retour : true si la modification est enregistrée, sinon false.

        // Chaque clé est un nom de champ et chaque élément est sa nouvelle valeur
        // Parcourir le tableau des nouvelles valeurs
        foreach ($tab as $champ => $valeur) {
            // Affecter la nouvelle valeur à l'objet
            $this->set($champ, $valeur);
        }

        // update() enregistre toutes les valeurs actuelles de l'objet
        // Enregistrerles modifications
        return $this->update();
    }
}
