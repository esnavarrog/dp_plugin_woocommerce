<?php
/*
Plugin Name: Despachos Pymes Settings
Description: Configuración del plugin Despachos Pymes para WooCommerce.
Version: 1.0 MEF Tech - Despachos Pymes
*/

// Agregar página de opciones para el token de acceso y la URL de la API
function dp_settings_page() {
    add_options_page(
        'Configuración del Plugin Despachos Pymes para WooCommerce',
        'Despachos Pymes',
        'manage_options',
        'dp-settings',
        'dp_settings_page_content'
    );
}
add_action('admin_menu', 'dp_settings_page');

// Contenido de la página de opciones
function dp_settings_page_content() {
    ?>
    <div class="wrap">
        <h2>Configuración del Plugin Despachos Pymes para WooCommerce</h2>
        <form method="post" action="options.php">
            <?php settings_fields('dp_settings_group'); ?>
            <?php do_settings_sections('dp-settings'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Registrar y agregar campos de configuración
function dp_settings_init() {
    register_setting('dp_settings_group', 'dp_api_token');
    register_setting('dp_settings_group', 'dp_api_url');

    add_settings_section(
        'dp_settings_section',
        'Configuración de dp',
        '',
        'dp-settings'
    );

    add_settings_field(
        'dp_api_url_field',
        'URL de la API',
        'dp_api_url_field_render',
        'dp-settings',
        'dp_settings_section'
    );

    add_settings_field(
        'dp_api_token_field',
        'Token de acceso API',
        'dp_api_token_field_render',
        'dp-settings',
        'dp_settings_section'
    );
}
add_action('admin_init', 'dp_settings_init');

// Renderizar campo de URL de la API
function dp_api_url_field_render() {
    $url = get_option('dp_api_url');
    ?>
    <input type="text" name="dp_api_url" value="<?php echo $url; ?>" />
    <?php
}

// Renderizar campo de token de acceso
function dp_api_token_field_render() {
    $token = get_option('dp_api_token');
    ?>
    <input type="text" name="dp_api_token" value="<?php echo $token; ?>" />
    <?php
}

// Añadir mensaje de conexión establecida
function display_dp_messages() {
    ?>
    <div class="updated">
        <p>¡Conexión establecida correctamente!</p>
    </div>
    <?php
}

// Guardar el token de acceso y la URL de la API de manera permanente
function save_dp_settings() {
    // Verificar si se ha enviado el formulario
    if (isset($_POST['dp_api_token']) && isset($_POST['dp_api_url'])) {
        // Sanitizar y validar los campos antes de guardarlos
        $token = sanitize_text_field($_POST['dp_api_token']);
        $url = esc_url_raw($_POST['dp_api_url']);

        // Guardar los valores en la base de datos
        update_option('dp_api_token', $token);
        update_option('dp_api_url', $url);
    }
}
add_action('admin_init', 'save_dp_settings');

// Mostrar mensajes de conexión establecida o errores
add_action('admin_notices', 'display_dp_messages');
