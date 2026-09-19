<?php

/**
 * French Language File
 *
 * @package Framework
 * @language fr
 */

return [
    // Common
    'welcome' => 'Bienvenue',
    'hello' => 'Bonjour',
    'goodbye' => 'Au revoir',
    'yes' => 'Oui',
    'no' => 'Non',
    'ok' => 'OK',
    'cancel' => 'Annuler',
    'save' => 'Enregistrer',
    'delete' => 'Supprimer',
    'edit' => 'Modifier',
    'create' => 'Créer',
    'update' => 'Mettre à jour',
    'search' => 'Rechercher',
    'filter' => 'Filtrer',
    'close' => 'Fermer',
    'back' => 'Retour',
    'next' => 'Suivant',
    'previous' => 'Précédent',
    'home' => 'Accueil',
    'loading' => 'Chargement...',

    // Authentication
    'auth' => [
        'failed' => 'Ces identifiants ne correspondent pas à nos enregistrements.',
        'throttle' => 'Trop de tentatives de connexion. Veuillez réessayer dans :seconds secondes.',
        'login' => 'Connexion',
        'logout' => 'Déconnexion',
        'register' => "S'inscrire",
        'email' => 'Adresse e-mail',
        'password' => 'Mot de passe',
        'confirm_password' => 'Confirmer le mot de passe',
        'remember_me' => 'Se souvenir de moi',
        'forgot_password' => 'Mot de passe oublié ?',
        'reset_password' => 'Réinitialiser le mot de passe',
        'already_registered' => 'Déjà inscrit ?',
        'not_registered' => 'Pas encore inscrit ?',
    ],

    // Messages
    'messages' => [
        'success' => 'Opération réussie !',
        'error' => "Une erreur s'est produite. Veuillez réessayer.",
        'warning' => 'Avertissement : Veuillez vérifier votre saisie.',
        'info' => 'Information : :message',
        'created' => ':item a été créé avec succès.',
        'updated' => ':item a été mis à jour avec succès.',
        'deleted' => ':item a été supprimé avec succès.',
        'not_found' => ':item introuvable.',
        'unauthorized' => "Vous n'êtes pas autorisé à effectuer cette action.",
        'forbidden' => 'Accès interdit.',
        'validation_failed' => 'La validation a échoué. Veuillez vérifier votre saisie.',
    ],

    // Validation
    'validation' => [
        'required' => 'Le champ :field est obligatoire.',
        'email' => 'Le champ :field doit être une adresse e-mail valide.',
        'min' => 'Le champ :field doit contenir au moins :min caractères.',
        'max' => 'Le champ :field ne peut pas dépasser :max caractères.',
        'numeric' => 'Le champ :field doit être un nombre.',
        'alpha' => 'Le champ :field ne peut contenir que des lettres.',
        'alphanumeric' => 'Le champ :field ne peut contenir que des lettres et des chiffres.',
        'unique' => 'Le champ :field est déjà pris.',
        'match' => 'Le champ :field doit correspondre à :other.',
        'invalid' => 'Le champ :field est invalide.',
    ],

    // Pagination
    'pagination' => [
        'previous' => '&laquo; Précédent',
        'next' => 'Suivant &raquo;',
        'showing' => 'Affichage de :from à :to sur :total résultats',
    ],

    // User
    'user' => [
        'profile' => 'Profil',
        'settings' => 'Paramètres',
        'account' => 'Compte',
        'dashboard' => 'Tableau de bord',
        'name' => 'Nom',
        'email' => 'E-mail',
        'phone' => 'Téléphone',
        'address' => 'Adresse',
        'created_at' => 'Créé le',
        'updated_at' => 'Mis à jour le',
    ],

    // Actions
    'actions' => [
        'view' => 'Voir',
        'edit' => 'Modifier',
        'delete' => 'Supprimer',
        'restore' => 'Restaurer',
        'download' => 'Télécharger',
        'upload' => 'Téléverser',
        'export' => 'Exporter',
        'import' => 'Importer',
        'print' => 'Imprimer',
        'share' => 'Partager',
    ],

    // Status
    'status' => [
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'pending' => 'En attente',
        'approved' => 'Approuvé',
        'rejected' => 'Rejeté',
        'completed' => 'Terminé',
        'cancelled' => 'Annulé',
    ],

    // Time
    'time' => [
        'just_now' => "À l'instant",
        'minutes_ago' => 'Il y a :count minutes',
        'hours_ago' => 'Il y a :count heures',
        'days_ago' => 'Il y a :count jours',
        'weeks_ago' => 'Il y a :count semaines',
        'months_ago' => 'Il y a :count mois',
        'years_ago' => 'Il y a :count ans',
    ],

    // Errors
    'errors' => [
        '404' => 'Page non trouvée',
        '500' => 'Erreur interne du serveur',
        '403' => 'Interdit',
        '401' => 'Non autorisé',
        'general' => "Une erreur s'est produite. Veuillez réessayer plus tard.",
    ],
];
