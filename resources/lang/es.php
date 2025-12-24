<?php

/**
 * Spanish Language File
 *
 * @package Framework
 * @language es
 */

return [
    // Common
    'welcome' => 'Bienvenido',
    'hello' => 'Hola',
    'goodbye' => 'Adiós',
    'yes' => 'Sí',
    'no' => 'No',
    'ok' => 'OK',
    'cancel' => 'Cancelar',
    'save' => 'Guardar',
    'delete' => 'Eliminar',
    'edit' => 'Editar',
    'create' => 'Crear',
    'update' => 'Actualizar',
    'search' => 'Buscar',
    'filter' => 'Filtrar',
    'close' => 'Cerrar',
    'back' => 'Volver',
    'next' => 'Siguiente',
    'previous' => 'Anterior',
    'home' => 'Inicio',
    'loading' => 'Cargando...',

    // Authentication
    'auth' => [
        'failed' => 'Estas credenciales no coinciden con nuestros registros.',
        'throttle' => 'Demasiados intentos de inicio de sesión. Inténtalo de nuevo en :seconds segundos.',
        'login' => 'Iniciar Sesión',
        'logout' => 'Cerrar Sesión',
        'register' => 'Registrarse',
        'email' => 'Correo Electrónico',
        'password' => 'Contraseña',
        'confirm_password' => 'Confirmar Contraseña',
        'remember_me' => 'Recuérdame',
        'forgot_password' => '¿Olvidaste tu contraseña?',
        'reset_password' => 'Restablecer Contraseña',
        'already_registered' => '¿Ya estás registrado?',
        'not_registered' => '¿Aún no estás registrado?',
    ],

    // Messages
    'messages' => [
        'success' => '¡Operación completada exitosamente!',
        'error' => 'Ocurrió un error. Por favor, inténtalo de nuevo.',
        'warning' => 'Advertencia: Por favor revisa tu entrada.',
        'info' => 'Información: :message',
        'created' => ':item ha sido creado exitosamente.',
        'updated' => ':item ha sido actualizado exitosamente.',
        'deleted' => ':item ha sido eliminado exitosamente.',
        'not_found' => ':item no encontrado.',
        'unauthorized' => 'No estás autorizado para realizar esta acción.',
        'forbidden' => 'Acceso prohibido.',
        'validation_failed' => 'Validación fallida. Por favor verifica tu entrada.',
    ],

    // Validation
    'validation' => [
        'required' => 'El campo :field es obligatorio.',
        'email' => 'El campo :field debe ser una dirección de correo válida.',
        'min' => 'El campo :field debe tener al menos :min caracteres.',
        'max' => 'El campo :field no puede tener más de :max caracteres.',
        'numeric' => 'El campo :field debe ser un número.',
        'alpha' => 'El campo :field solo puede contener letras.',
        'alphanumeric' => 'El campo :field solo puede contener letras y números.',
        'unique' => 'El campo :field ya ha sido tomado.',
        'match' => 'El campo :field debe coincidir con :other.',
        'invalid' => 'El campo :field es inválido.',
    ],

    // Pagination
    'pagination' => [
        'previous' => '&laquo; Anterior',
        'next' => 'Siguiente &raquo;',
        'showing' => 'Mostrando :from a :to de :total resultados',
    ],

    // User
    'user' => [
        'profile' => 'Perfil',
        'settings' => 'Configuración',
        'account' => 'Cuenta',
        'dashboard' => 'Panel de Control',
        'name' => 'Nombre',
        'email' => 'Correo Electrónico',
        'phone' => 'Teléfono',
        'address' => 'Dirección',
        'created_at' => 'Creado En',
        'updated_at' => 'Actualizado En',
    ],

    // Actions
    'actions' => [
        'view' => 'Ver',
        'edit' => 'Editar',
        'delete' => 'Eliminar',
        'restore' => 'Restaurar',
        'download' => 'Descargar',
        'upload' => 'Subir',
        'export' => 'Exportar',
        'import' => 'Importar',
        'print' => 'Imprimir',
        'share' => 'Compartir',
    ],

    // Status
    'status' => [
        'active' => 'Activo',
        'inactive' => 'Inactivo',
        'pending' => 'Pendiente',
        'approved' => 'Aprobado',
        'rejected' => 'Rechazado',
        'completed' => 'Completado',
        'cancelled' => 'Cancelado',
    ],

    // Time
    'time' => [
        'just_now' => 'Justo ahora',
        'minutes_ago' => 'Hace :count minutos',
        'hours_ago' => 'Hace :count horas',
        'days_ago' => 'Hace :count días',
        'weeks_ago' => 'Hace :count semanas',
        'months_ago' => 'Hace :count meses',
        'years_ago' => 'Hace :count años',
    ],

    // Errors
    'errors' => [
        '404' => 'Página No Encontrada',
        '500' => 'Error Interno del Servidor',
        '403' => 'Prohibido',
        '401' => 'No Autorizado',
        'general' => 'Algo salió mal. Por favor, inténtalo más tarde.',
    ],
];

