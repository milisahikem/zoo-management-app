<?php

/* =========================
   SESSION
========================= */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   ROLES DISPONIBLES
========================= */
function roles_list() {
    return [
        'soigneur',
        'chef_soigneur',
        'veterinaire',
        'responsable_boutique',
        'employe_boutique',
        'comptable',
        'rh',
        'directeur',
        'technique',
        'entretien'
    ];
}

/* =========================
   LABELS PROPRES
========================= */
function role_label($role) {
    $labels = [
        'soigneur' => 'Soigneur',
        'chef_soigneur' => 'Chef soigneur',
        'veterinaire' => 'Vétérinaire',
        'responsable_boutique' => 'Responsable boutique',
        'employe_boutique' => 'Employé boutique',
        'comptable' => 'Comptable',
        'rh' => 'Ressources humaines',
        'directeur' => 'Directeur',
        'technique' => 'Technicien',
        'entretien' => 'Personnel entretien'
    ];

    return $labels[$role] ?? $role;
}

/* =========================
   UTILISATEUR CONNECTÉ
========================= */
function current_user() {
    if (isset($_SESSION['user'])) {
        return $_SESSION['user'];
    }

    return null;
}

function current_user_id() {
    if (isset($_SESSION['user']['id_personne'])) {
        return $_SESSION['user']['id_personne'];
    }

    if (isset($_SESSION['id_personne'])) {
        return $_SESSION['id_personne'];
    }

    return null;
}

function current_role() {
    if (isset($_SESSION['user']['role'])) {
        return strtolower(trim($_SESSION['user']['role']));
    }

    if (isset($_SESSION['role'])) {
        return strtolower(trim($_SESSION['role']));
    }

    return null;
}

/* =========================
   CONNEXION / DECONNEXION
========================= */
function login_user($user) {
    $_SESSION['user'] = $user;
}

function logout_user() {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

/* =========================
   VERIFICATION LOGIN
========================= */
function require_login() {
    if (!current_user() && !isset($_SESSION['role'])) {
        header("Location: login.php");
        exit();
    }
}

/* =========================
   CHANGEMENT MDP OBLIGATOIRE
========================= */
function must_change_password() {
    if (isset($_SESSION['user']['must_change_password'])) {
        return $_SESSION['user']['must_change_password'] === true;
    }

    if (isset($_SESSION['must_change_password'])) {
        return $_SESSION['must_change_password'] === true;
    }

    return false;
}

/* =========================
   CONTROLE DES DROITS
========================= */
function can_access($action) {
    $role = current_role();

    if (!$role) {
        return false;
    }

    $permissions = [

        /* -------- PROFIL -------- */
        'profil' => [
            'soigneur',
            'chef_soigneur',
            'veterinaire',
            'responsable_boutique',
            'employe_boutique',
            'comptable',
            'rh',
            'directeur',
            'technique',
            'entretien'
        ],

        /* -------- ANIMAUX -------- */
        'animaux' => [
            'soigneur', 'chef_soigneur', 'veterinaire', 'directeur'
        ],

        'modifier_animal' => [
            'chef_soigneur', 'directeur'
        ],

        'ajouter_animal' => [
            'chef_soigneur', 'directeur'
        ],

        /* -------- SOINS -------- */
        'soins' => [
            'soigneur', 'chef_soigneur', 'veterinaire', 'directeur'
        ],

        'ajouter_soin' => [
            'soigneur', 'chef_soigneur', 'veterinaire'
        ],

        /* -------- NOURRISSAGE -------- */
        'nourrir_animaux' => [
            'soigneur', 'chef_soigneur'
        ],

        /* -------- REPARATIONS -------- */
        'travaux' => [
            'technique', 'directeur'
        ],

        'ajouter_reparation' => [
            'technique', 'directeur'
        ],
        'travaux' => [
    'technique', 'directeur'
],

'ajouter_reparation' => [
    'technique', 'directeur'
],

        /* -------- BOUTIQUES -------- */
        'boutiques' => [
            'responsable_boutique', 'employe_boutique', 'directeur', 'comptable'
        ],

        'ajouter_ca' => [
            'responsable_boutique'
        ],

        'calculer_ca' => [
            'comptable', 'directeur'
        ],

        /* -------- PERSONNEL -------- */
        'personnel' => [
            'rh', 'directeur'
        ],

        'ajouter_employe' => [
            'rh', 'directeur'
        ],

        /* -------- RAPPORTS -------- */
        

        /* -------- LOGIQUE AVANCÉE -------- */
        'gestion_affectations' => [
            'chef_soigneur', 'directeur'
        ],

        'gestion_remplacements' => [
            'chef_soigneur', 'directeur'
        ],

        'gestion_parents' => [
            'chef_soigneur', 'veterinaire', 'directeur'
        ],

        'gestion_cohabitation' => [
            'chef_soigneur', 'veterinaire', 'directeur'
        ],

        /* -------- PARRAINAGES -------- */
        'parrainages' => [
            'comptable', 'directeur'
        ],

        'ajouter_parrainage' => [
            'comptable', 'directeur'
        ]
    ];

    return in_array($role, $permissions[$action] ?? [], true);
}

/* =========================
   BLOQUER ACCES PAGE
========================= */
function require_permission($action) {
    require_login();

    if (!can_access($action)) {
        die("<div class='container'><div class='erreur'>Accès refusé.</div></div>");
    }
}

/* =========================
   COMPATIBILITE ANCIEN NOM
========================= */
function require_section($action) {
    require_permission($action);
}

/* =========================
   FILTRES SQL PAR ROLE
   (si tu veux les utiliser plus tard)
========================= */
function sql_filter_animaux($alias = 'a') {
    $role = current_role();
    $id = current_user_id();

    if (!$id) {
        return "1=0";
    }

    if ($role === 'soigneur') {
        return "$alias.id_soigneur_attitre = " . (int)$id;
    }

    return "1=1";
}

function sql_filter_boutiques($alias = 'b') {
    $role = current_role();
    $id = current_user_id();

    if (!$id) {
        return "1=0";
    }

    if ($role === 'responsable_boutique') {
        return "$alias.id_responsable = " . (int)$id;
    }

    if ($role === 'employe_boutique') {
        return "$alias.id_boutique IN (
            SELECT t.id_boutique
            FROM TRAVAILLER t
            WHERE t.id_personne = " . (int)$id . "
        )";
    }

    return "1=1";
}
?>