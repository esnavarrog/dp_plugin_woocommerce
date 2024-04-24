<?php
/*
Plugin Name: Despachos Pymes Order
Description: Envía la información de la orden a la API de Despachos Pymes cuando una orden se completa en WooCommerce.
Version: 1.0 MEF Tech - Despachos Pymes
*/

// Enviar información de la orden a la API externa cuando se completa
function send_order_info_to_api($order_id) {
  $order = wc_get_order($order_id);
  $token = get_option('dp_api_token');
  $url = 'https://despachospymes.cl/api/v1/deliveries';

  // Verificar si la orden está completada y se tienen el token y la URL de la API
  if ($order->get_status() === 'completed' && !empty($token) && !empty($url)) {
        // Obtener datos relevantes de la orden
        $order_data = array(
          'order_id' => $order_id,
          'customer_id' => $order->get_customer_id(),
          'total' => $order->get_total(),
            // Agrega más datos relevantes aquí según tus necesidades
        );

        // Obtener los elementos de línea de la orden
        foreach ($order->get_items() as $item_id => $item_data) {
          $product = $item_data->get_product();

          $order_data['line_items'][] = array(
            'item_id' => $item_id,
            'product_id' => $item_data->get_product_id(),
            'name' => $item_data->get_name(),
            'quantity' => $item_data->get_quantity(),
            'subtotal' => $order->get_line_subtotal($item_data, false, false),
            'total' => $order->get_line_total($item_data, false, false),
            // Otros datos relevantes del elemento de línea según sea necesario
          );
        }

        $order_data['shipping'] = array(
          'order_id' => $order_id,
          'customer_id' => $order->get_customer_id(),
          'order_key' => $order->get_order_key(),
          'email' => $order->get_billing_email(),
          'status' => $order->get_status(),
          'first_name' => $order->get_shipping_first_name(),
          'last_name' => $order->get_shipping_last_name(),
          'company' => $order->get_shipping_company(),
          'address_1' => $order->get_shipping_address_1(),
          'address_2' => $order->get_shipping_address_2(),
          'city' => $order->get_shipping_city(),
          'state' => $order->get_shipping_state(),
          'postcode' => $order->get_shipping_postcode(),
          'country' => $order->get_shipping_country(),
          'date_created' => $order->get_date_created(),
          'date_modified' => $order->get_date_modified(),
          'shipping_total' => $order->get_shipping_total(),
          'phone' => $order->get_shipping_phone() ?? $order->get_billing_phone(),
          // Otros datos de envío según sea necesario
        );

        // Configurar la solicitud a la API
        $args = array(
          'body'        => json_encode($order_data),
          'headers'     => array(
              'Content-Type' => 'application/json',
              'Authorization' => 'Bearer ' . $token,
              'token' => $token,
              'origin' => 'woocommerce',
          ),
          'timeout'     => 15,
          'redirection' => 5,
          'blocking'    => true,
          'httpversion' => '1.0',
          'sslverify'   => false,
          'data_format' => 'body',
      );

        // Enviar la solicitud a la API
        $response = wp_remote_post($url, $args);

        $plugin_dir = plugin_dir_path(__FILE__);
        $log_file = $plugin_dir . 'logs.txt';

        function add_to_logs($message) {
          global $log_file;
          $current_time = date('Y-m-d H:i:s');
          $log_message = "[$current_time] $message\n";
          file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
        }

        // Verificar si la solicitud fue exitosa
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
          add_to_logs('Orden con ID ' . $order_id . ' enviada a Despachos Pymes para su procesamiento de envío.');
        } else {
          $error_message = wp_remote_retrieve_response_message($response);
          $error_code = wp_remote_retrieve_response_code($response);
          $error_body = wp_remote_retrieve_body($response);

            // Registrar el error en el archivo de registro de errores del servidor
            add_to_logs('Error al enviar la orden a la API externa. Código de error: ' . $error_code . '. Mensaje de error: ' . $error_message . '. Detalles adicionales: ' . $error_body);
        }
    }
}
  add_action('woocommerce_order_status_completed', 'send_order_info_to_api');


add_action('admin_menu', 'dp_order_logs_page');

function dp_order_logs_page() {
    add_menu_page(
        'Logs de dp Order', // Título de la página
        'Logs de dp Order', // Título del menú
        'manage_options', // Capacidad requerida para ver esta página
        'dp-order-logs', // Slug de la página
        'dp_order_logs_callback' // Función para mostrar la página
    );
}

// Función para mostrar la página de logs
function dp_order_logs_callback() {
    $plugin_dir = plugin_dir_path(__FILE__);
    // Recupera los logs de alguna manera (por ejemplo, desde un archivo)
    $log_file = $plugin_dir . 'logs.txt';

    $logs = file_get_contents($log_file);

    // Muestra los logs en una página
    echo '<div class="wrap">';
    echo '<h1>Logs de Despachos Pymes Order</h1>';
    echo '<pre>' . esc_html($logs) . '</pre>'; // Muestra los logs como texto sin formato
    echo '</div>';
}

// Función de activación del plugin
function my_plugin_activation() {
    // Ruta al directorio del plugin
    $plugin_dir = plugin_dir_path(__FILE__);

    // Ruta al archivo de registro
    $log_file = $plugin_dir . 'logs.txt';

    // Mensaje inicial para el archivo de registro
    $message = "Inicio del registro:\n";

    // Escribir el mensaje inicial en el archivo de registro
    file_put_contents($log_file, $message);
}

// Registrar la función de activación del plugin
register_activation_hook(__FILE__, 'my_plugin_activation');
