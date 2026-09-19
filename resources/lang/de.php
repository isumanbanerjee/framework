<?php

/**
 * German Language File
 *
 * @package Framework
 * @language de
 */

return [
    // Common
    'welcome' => 'Willkommen',
    'hello' => 'Hallo',
    'goodbye' => 'Auf Wiedersehen',
    'yes' => 'Ja',
    'no' => 'Nein',
    'ok' => 'OK',
    'cancel' => 'Abbrechen',
    'save' => 'Speichern',
    'delete' => 'Löschen',
    'edit' => 'Bearbeiten',
    'create' => 'Erstellen',
    'update' => 'Aktualisieren',
    'search' => 'Suchen',
    'filter' => 'Filtern',
    'close' => 'Schließen',
    'back' => 'Zurück',
    'next' => 'Weiter',
    'previous' => 'Vorherige',
    'home' => 'Startseite',
    'loading' => 'Wird geladen...',

    // Authentication
    'auth' => [
        'failed' => 'Diese Anmeldedaten stimmen nicht mit unseren Aufzeichnungen überein.',
        'throttle' => 'Zu viele Anmeldeversuche. Bitte versuchen Sie es in :seconds Sekunden erneut.',
        'login' => 'Anmelden',
        'logout' => 'Abmelden',
        'register' => 'Registrieren',
        'email' => 'E-Mail-Adresse',
        'password' => 'Passwort',
        'confirm_password' => 'Passwort bestätigen',
        'remember_me' => 'Angemeldet bleiben',
        'forgot_password' => 'Passwort vergessen?',
        'reset_password' => 'Passwort zurücksetzen',
        'already_registered' => 'Bereits registriert?',
        'not_registered' => 'Noch nicht registriert?',
    ],

    // Messages
    'messages' => [
        'success' => 'Vorgang erfolgreich abgeschlossen!',
        'error' => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.',
        'warning' => 'Warnung: Bitte überprüfen Sie Ihre Eingabe.',
        'info' => 'Information: :message',
        'created' => ':item wurde erfolgreich erstellt.',
        'updated' => ':item wurde erfolgreich aktualisiert.',
        'deleted' => ':item wurde erfolgreich gelöscht.',
        'not_found' => ':item nicht gefunden.',
        'unauthorized' => 'Sie sind nicht berechtigt, diese Aktion auszuführen.',
        'forbidden' => 'Zugriff verboten.',
        'validation_failed' => 'Validierung fehlgeschlagen. Bitte überprüfen Sie Ihre Eingabe.',
    ],

    // Validation
    'validation' => [
        'required' => 'Das Feld :field ist erforderlich.',
        'email' => 'Das Feld :field muss eine gültige E-Mail-Adresse sein.',
        'min' => 'Das Feld :field muss mindestens :min Zeichen lang sein.',
        'max' => 'Das Feld :field darf nicht länger als :max Zeichen sein.',
        'numeric' => 'Das Feld :field muss eine Zahl sein.',
        'alpha' => 'Das Feld :field darf nur Buchstaben enthalten.',
        'alphanumeric' => 'Das Feld :field darf nur Buchstaben und Zahlen enthalten.',
        'unique' => 'Das Feld :field wurde bereits vergeben.',
        'match' => 'Das Feld :field muss mit :other übereinstimmen.',
        'invalid' => 'Das Feld :field ist ungültig.',
    ],

    // Pagination
    'pagination' => [
        'previous' => '&laquo; Vorherige',
        'next' => 'Weiter &raquo;',
        'showing' => 'Zeige :from bis :to von :total Ergebnissen',
    ],

    // User
    'user' => [
        'profile' => 'Profil',
        'settings' => 'Einstellungen',
        'account' => 'Konto',
        'dashboard' => 'Dashboard',
        'name' => 'Name',
        'email' => 'E-Mail',
        'phone' => 'Telefon',
        'address' => 'Adresse',
        'created_at' => 'Erstellt am',
        'updated_at' => 'Aktualisiert am',
    ],

    // Actions
    'actions' => [
        'view' => 'Ansehen',
        'edit' => 'Bearbeiten',
        'delete' => 'Löschen',
        'restore' => 'Wiederherstellen',
        'download' => 'Herunterladen',
        'upload' => 'Hochladen',
        'export' => 'Exportieren',
        'import' => 'Importieren',
        'print' => 'Drucken',
        'share' => 'Teilen',
    ],

    // Status
    'status' => [
        'active' => 'Aktiv',
        'inactive' => 'Inaktiv',
        'pending' => 'Ausstehend',
        'approved' => 'Genehmigt',
        'rejected' => 'Abgelehnt',
        'completed' => 'Abgeschlossen',
        'cancelled' => 'Storniert',
    ],

    // Time
    'time' => [
        'just_now' => 'Gerade eben',
        'minutes_ago' => 'Vor :count Minuten',
        'hours_ago' => 'Vor :count Stunden',
        'days_ago' => 'Vor :count Tagen',
        'weeks_ago' => 'Vor :count Wochen',
        'months_ago' => 'Vor :count Monaten',
        'years_ago' => 'Vor :count Jahren',
    ],

    // Errors
    'errors' => [
        '404' => 'Seite nicht gefunden',
        '500' => 'Interner Serverfehler',
        '403' => 'Verboten',
        '401' => 'Nicht autorisiert',
        'general' => 'Etwas ist schiefgelaufen. Bitte versuchen Sie es später erneut.',
    ],
];
