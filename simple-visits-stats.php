<?php
/*
 * Plugin Name: Simple Visits Stats
 * Plugin URI: https://github.com/tu-usuario/simple-visits-stats
 * Description: A simple WordPress plugin to track visits to pages, posts, WooCommerce products, categories, and the shop page, with daily, monthly, and annual statistics.
 * Version: 1.2.0
 * Author: Sant77_ec
 * Author URI: https://github.com/tu-usuario
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Requires PHP: 7.0
 */

// Registrar visitas a páginas, entradas, productos, página de la tienda y categorías de productos
function simple_visit_counter() {
    if (is_admin() || is_user_logged_in() || simple_is_bot()) {
        return;
    }

    $today = date('Y-m-d');
    $month = date('Y-m');
    $year = date('Y');
    $is_product = false;
    $post_id = null;
    $category_id = null;

    if (is_singular()) {
        global $post;
        $post_id = $post->ID;
        $is_product = (function_exists('is_product') && is_product()) || get_post_type($post_id) === 'product';
    } elseif (function_exists('is_shop') && is_shop()) {
        $post_id = wc_get_page_id('shop');
        $is_product = true;
    } elseif (function_exists('is_product_category') && is_product_category()) {
        $category = get_queried_object();
        $category_id = $category->term_id;
        $is_product = true;
    } else {
        return;
    }

    // Incrementar contador total del sitio
    $total_visits = (int) get_option('simple_total_visits', 0);
    update_option('simple_total_visits', $total_visits + 1);

    // Incrementar contador por tipo (sitio o tienda/productos)
    if ($is_product) {
        $total_product_visits = (int) get_option('simple_total_product_visits', 0);
        update_option('simple_total_product_visits', $total_product_visits + 1);
    } else {
        $total_site_visits = (int) get_option('simple_total_site_visits', 0);
        update_option('simple_total_site_visits', $total_site_visits + 1);
    }

    // Incrementar contador específico de la página, producto o tienda
    if ($post_id) {
        $visit_count = (int) get_post_meta($post_id, 'simple_visit_count', true);
        update_post_meta($post_id, 'simple_visit_count', $visit_count + 1);

        // Registrar visita por post por día
        $post_daily_stats = get_post_meta($post_id, 'simple_daily_visits', true);
        if (empty($post_daily_stats)) {
            $post_daily_stats = array();
        }

        if (!isset($post_daily_stats[$today])) {
            $post_daily_stats[$today] = 0;
        }

        $post_daily_stats[$today]++;
        // Limpiar datos diarios según el límite configurado
        $daily_limit = (int) get_option('simple_stats_daily_limit', 30);
        $cutoff_date = date('Y-m-d', strtotime("-$daily_limit days"));
        foreach ($post_daily_stats as $date => $count) {
            if ($date < $cutoff_date) {
                unset($post_daily_stats[$date]);
            }
        }
        update_post_meta($post_id, 'simple_daily_visits', $post_daily_stats);
    }

    // Registrar visita para categoría de producto
    if ($category_id) {
        $category_visit_count = (int) get_term_meta($category_id, 'simple_visit_count', true);
        update_term_meta($category_id, 'simple_visit_count', $category_visit_count + 1);

        // Registrar visita por categoría por día
        $category_daily_stats = get_term_meta($category_id, 'simple_daily_visits', true);
        if (empty($category_daily_stats)) {
            $category_daily_stats = array();
        }

        if (!isset($category_daily_stats[$today])) {
            $category_daily_stats[$today] = 0;
        }

        $category_daily_stats[$today]++;
        // Limpiar datos diarios según el límite configurado
        $daily_limit = (int) get_option('simple_stats_daily_limit', 30);
        $cutoff_date = date('Y-m-d', strtotime("-$daily_limit days"));
        foreach ($category_daily_stats as $date => $count) {
            if ($date < $cutoff_date) {
                unset($category_daily_stats[$date]);
            }
        }
        update_term_meta($category_id, 'simple_daily_visits', $category_daily_stats);
    }

    // Registrar visita diaria
    $daily_stats = get_option('simple_daily_stats', array());
    if (!isset($daily_stats[$today])) {
        $daily_stats[$today] = array('total' => 0, 'site' => 0, 'products' => 0);
    }
    $daily_stats[$today]['total']++;
    if ($is_product) {
        $daily_stats[$today]['products']++;
    } else {
        $daily_stats[$today]['site']++;
    }
    // Limpiar datos diarios según el límite configurado
    $daily_limit = (int) get_option('simple_stats_daily_limit', 30);
    $cutoff_date = date('Y-m-d', strtotime("-$daily_limit days"));
    foreach ($daily_stats as $date => $stats) {
        if ($date < $cutoff_date) {
            unset($daily_stats[$date]);
        }
    }
    update_option('simple_daily_stats', $daily_stats);

    // Registrar visita mensual
    $monthly_stats = get_option('simple_monthly_stats', array());
    if (!isset($monthly_stats[$month])) {
        $monthly_stats[$month] = array('total' => 0, 'site' => 0, 'products' => 0);
    }
    $monthly_stats[$month]['total']++;
    if ($is_product) {
        $monthly_stats[$month]['products']++;
    } else {
        $monthly_stats[$month]['site']++;
    }
    // Limpiar datos mensuales según el límite configurado
    $monthly_limit = (int) get_option('simple_stats_monthly_limit', 12);
    $cutoff_month = date('Y-m', strtotime("-$monthly_limit months"));
    foreach ($monthly_stats as $m => $stats) {
        if ($m < $cutoff_month) {
            unset($monthly_stats[$m]);
        }
    }
    update_option('simple_monthly_stats', $monthly_stats);

    // Registrar visita anual
    $annual_stats = get_option('simple_annual_stats', array());
    if (!isset($annual_stats[$year])) {
        $annual_stats[$year] = array('total' => 0, 'site' => 0, 'products' => 0);
    }
    $annual_stats[$year]['total']++;
    if ($is_product) {
        $annual_stats[$year]['products']++;
    } else {
        $annual_stats[$year]['site']++;
    }
    // Limpiar datos anuales según el límite configurado
    $annual_limit = (int) get_option('simple_stats_annual_limit', 2);
    $cutoff_year = date('Y', strtotime("-$annual_limit years"));
    foreach ($annual_stats as $y => $stats) {
        if ($y < $cutoff_year) {
            unset($annual_stats[$y]);
        }
    }
    update_option('simple_annual_stats', $annual_stats);
}
add_action('wp', 'simple_visit_counter');

// Función para detectar bots simples
function simple_is_bot() {
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
    $bots = ['googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider', 'yandexbot'];
    foreach ($bots as $bot) {
        if (strpos($user_agent, $bot) !== false) {
            return true;
        }
    }
    return false;
}

// Agregar página de estadísticas al menú admin
function simple_stats_menu() {
    add_menu_page(
        'Estadísticas de Visitas',
        'Estadísticas',
        'manage_options',
        'simple-stats',
        'simple_stats_page',
        'dashicons-chart-bar',
        80
    );
    add_submenu_page(
        'simple-stats',
        'Ajustes de Estadísticas',
        'Ajustes',
        'manage_options',
        'simple-stats-settings',
        'simple_stats_settings_page'
    );
}
add_action('admin_menu', 'simple_stats_menu');

// Página de ajustes para límites
function simple_stats_settings_page() {
    if (isset($_POST['simple_stats_settings_nonce']) && wp_verify_nonce($_POST['simple_stats_settings_nonce'], 'simple_stats_settings')) {
        $daily_limit = isset($_POST['daily_limit']) ? absint($_POST['daily_limit']) : 30;
        $monthly_limit = isset($_POST['monthly_limit']) ? absint($_POST['monthly_limit']) : 12;
        $annual_limit = isset($_POST['annual_limit']) ? absint($_POST['annual_limit']) : 2;

        $daily_limit = max(1, min($daily_limit, 90)); // Límite entre 1 y 90 días
        $monthly_limit = max(1, min($monthly_limit, 36)); // Límite entre 1 y 36 meses
        $annual_limit = max(1, min($annual_limit, 5)); // Límite entre 1 y 5 años

        update_option('simple_stats_daily_limit', $daily_limit);
        update_option('simple_stats_monthly_limit', $monthly_limit);
        update_option('simple_stats_annual_limit', $annual_limit);

        echo '<div class="updated"><p>Ajustes guardados.</p></div>';
    }

    $daily_limit = (int) get_option('simple_stats_daily_limit', 30);
    $monthly_limit = (int) get_option('simple_stats_monthly_limit', 12);
    $annual_limit = (int) get_option('simple_stats_annual_limit', 2);
    ?>
    <div class="wrap">
        <h1>Ajustes de Estadísticas</h1>
        <form method="post" action="">
            <?php wp_nonce_field('simple_stats_settings', 'simple_stats_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="daily_limit">Límite de días</label></th>
                    <td>
                        <input type="number" id="daily_limit" name="daily_limit" value="<?php echo esc_attr($daily_limit); ?>" min="1" max="90">
                        <p class="description">Número de días para almacenar estadísticas diarias (1-90).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="monthly_limit">Límite de meses</label></th>
                    <td>
                        <input type="number" id="monthly_limit" name="monthly_limit" value="<?php echo esc_attr($monthly_limit); ?>" min="1" max="36">
                        <p class="description">Número de meses para almacenar estadísticas mensuales (1-36).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="annual_limit">Límite de años</label></th>
                    <td>
                        <input type="number" id="annual_limit" name="annual_limit" value="<?php echo esc_attr($annual_limit); ?>" min="1" max="5">
                        <p class="description">Número de años para almacenar estadísticas anuales (1-5).</p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" class="button button-primary" value="Guardar cambios">
            </p>
        </form>
    </div>
    <?php
}

// Widget en el escritorio
function simple_stats_dashboard_widget() {
    wp_add_dashboard_widget(
        'simple_stats_dashboard_widget',
        'Resumen de Visitas (Últimos 7 días)',
        'simple_stats_dashboard_widget_display'
    );
}
add_action('wp_dashboard_setup', 'simple_stats_dashboard_widget');

function simple_stats_dashboard_widget_display() {
    $start_date = date('Y-m-d', strtotime('-7 days'));
    $end_date = date('Y-m-d');
    $cache_key = 'simple_stats_dashboard_' . md5($start_date . $end_date);
    $cached_data = get_transient($cache_key);

    if ($cached_data !== false) {
        $total = $cached_data['total'];
        $site = $cached_data['site'];
        $products = $cached_data['products'];
    } else {
        $daily_stats = get_option('simple_daily_stats', array());
        $total = 0;
        $site = 0;
        $products = 0;

        foreach ($daily_stats as $date => $stats) {
            if ($date >= $start_date && $date <= $end_date) {
                $total += $stats['total'];
                $site += $stats['site'];
                $products += $stats['products'];
            }
        }

        set_transient($cache_key, compact('total', 'site', 'products'), HOUR_IN_SECONDS);
    }

    ?>
    <p><strong>Visitas a páginas y entradas:</strong> <?php echo (int) $site; ?></p>
    <p><strong>Visitas a productos, categorías y tienda:</strong> <?php echo (int) $products; ?></p>
    <p><strong>Total de visitas:</strong> <?php echo (int) $total; ?></p>
    <p><a href="<?php echo admin_url('admin.php?page=simple-stats'); ?>" class="button">Ver estadísticas completas</a></p>
    <?php
}

// Renderizar la página de estadísticas con filtros
function simple_stats_page() {
    $today = date('Y-m-d');
    $current_month = date('Y-m');
    $current_year = date('Y');
    $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'daily';
    $start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : $today;
    $end_date = isset($_GET['end_date']) && !empty($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : $today;
    $start_month = isset($_GET['start_month']) && !empty($_GET['start_month']) ? sanitize_text_field($_GET['start_month']) : $current_month;
    $end_month = isset($_GET['end_month']) && !empty($_GET['end_month']) ? sanitize_text_field($_GET['end_month']) : $current_month;
    $page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
    $per_page = 10;

    // Validar fechas
    if ($view === 'daily') {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
            $start_date = $today;
            $end_date = $today;
        }
        if (strtotime($start_date) > strtotime($end_date)) {
            $temp = $start_date;
            $start_date = $end_date;
            $end_date = $temp;
        }
        $daily_limit = (int) get_option('simple_stats_daily_limit', 30);
        $cutoff_date = date('Y-m-d', strtotime("-$daily_limit days"));
        if ($start_date < $cutoff_date) {
            $start_date = $cutoff_date;
        }
    } elseif ($view === 'monthly') {
        if (!preg_match('/^\d{4}-\d{2}$/', $start_month) || !preg_match('/^\d{4}-\d{2}$/', $end_month)) {
            $start_month = $current_month;
            $end_month = $current_month;
        }
        if (strtotime($start_month . '-01') > strtotime($end_month . '-01')) {
            $temp = $start_month;
            $start_month = $end_month;
            $end_month = $temp;
        }
        $monthly_limit = (int) get_option('simple_stats_monthly_limit', 12);
        $cutoff_month = date('Y-m', strtotime("-$monthly_limit months"));
        if ($start_month < $cutoff_month) {
            $start_month = $cutoff_month;
        }
    }

    // Obtener estadísticas desde caché o base de datos
    $cache_key = 'simple_stats_' . md5($view . $start_date . $end_date . $start_month . $end_month . $page);
    $cached_stats = get_transient($cache_key);
    if ($cached_stats !== false) {
        $data = $cached_stats;
    } else {
        $daily_stats = get_option('simple_daily_stats', array());
        $monthly_stats = get_option('simple_monthly_stats', array());
        $annual_stats = get_option('simple_annual_stats', array());

        // Filtrar estadísticas diarias
        $filtered_daily_stats = array();
        $total_daily = array('total' => 0, 'site' => 0, 'products' => 0);
        if ($view === 'daily') {
            foreach ($daily_stats as $date => $stats) {
                if ($date >= $start_date && $date <= $end_date) {
                    $filtered_daily_stats[$date] = $stats;
                    $total_daily['total'] += $stats['total'];
                    $total_daily['site'] += $stats['site'];
                    $total_daily['products'] += $stats['products'];
                }
            }
        }

        // Filtrar estadísticas mensuales
        $filtered_monthly_stats = array();
        $total_monthly = array('total' => 0, 'site' => 0, 'products' => 0);
        if ($view === 'monthly') {
            foreach ($monthly_stats as $month => $stats) {
                if ($month >= $start_month && $month <= $end_month) {
                    $filtered_monthly_stats[$month] = $stats;
                    $total_monthly['total'] += $stats['total'];
                    $total_monthly['site'] += $stats['site'];
                    $total_monthly['products'] += $stats['products'];
                }
            }
        }

        // Obtener estadísticas anuales
        $filtered_annual_stats = $annual_stats;
        $total_annual = array('total' => 0, 'site' => 0, 'products' => 0);
        foreach ($annual_stats as $stats) {
            $total_annual['total'] += $stats['total'];
            $total_annual['site'] += $stats['site'];
            $total_annual['products'] += $stats['products'];
        }

        // Obtener posts y categorías más visitados
        $top_posts = get_top_visited_posts(array('page', 'post'), $start_date, $end_date);
        $top_products = get_top_visited_posts('product', $start_date, $end_date);
        $top_categories = get_top_visited_categories($start_date, $end_date);

        $data = compact(
            'daily_stats', 'monthly_stats', 'annual_stats',
            'filtered_daily_stats', 'total_daily',
            'filtered_monthly_stats', 'total_monthly',
            'filtered_annual_stats', 'total_annual',
            'top_posts', 'top_products', 'top_categories'
        );

        set_transient($cache_key, $data, HOUR_IN_SECONDS);
    }

    extract($data);

    // Paginación para estadísticas diarias
    $paged_daily_stats = array_slice($filtered_daily_stats, ($page - 1) * $per_page, $per_page, true);
    $total_days = count($filtered_daily_stats);
    $max_pages = ceil($total_days / $per_page);

    ?>
    <div class="wrap">
        <h1>Estadísticas de Visitas</h1>

        <form method="get" action="<?php echo admin_url('admin.php'); ?>" id="stats-form">
            <input type="hidden" name="page" value="simple-stats">
            <div style="margin: 20px 0; padding: 10px; background: #fff; border: 1px solid #ccd0d4;">
                <h2>Filtros de Fecha</h2>
                <label for="view">Vista por:</label>
                <select id="view" name="view" onchange="toggleDateFields(this.value)">
                    <option value="daily" <?php echo $view === 'daily' ? 'selected' : ''; ?>>Día</option>
                    <option value="monthly" <?php echo $view === 'monthly' ? 'selected' : ''; ?>>Mes</option>
                    <option value="annual" <?php echo $view === 'annual' ? 'selected' : ''; ?>>Año</option>
                </select>

                <div id="daily-fields" style="display: <?php echo $view === 'daily' ? 'inline' : 'none'; ?>;">
                    <label for="start_date" style="margin-left: 10px;">Fecha de inicio:</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($start_date); ?>" max="<?php echo $today; ?>" min="<?php echo date('Y-m-d', strtotime('-' . (int) get_option('simple_stats_daily_limit', 30) . ' days')); ?>">
                    <label for="end_date" style="margin-left: 10px;">Fecha de fin:</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($end_date); ?>" max="<?php echo $today; ?>">
                </div>

                <div id="monthly-fields" style="display: <?php echo $view === 'monthly' ? 'inline' : 'none'; ?>;">
                    <label for="start_month" style="margin-left: 10px;">Mes de inicio:</label>
                    <input type="month" id="start_month" name="start_month" value="<?php echo esc_attr($start_month); ?>" max="<?php echo $current_month; ?>" min="<?php echo date('Y-m', strtotime('-' . (int) get_option('simple_stats_monthly_limit', 12) . ' months')); ?>">
                    <label for="end_month" style="margin-left: 10px;">Mes de fin:</label>
                    <input type="month" id="end_month" name="end_month" value="<?php echo esc_attr($end_month); ?>" max="<?php echo $current_month; ?>">
                </div>

                <input type="submit" class="button button-primary" value="Aplicar filtros" style="margin-left: 10px;">
                <a href="<?php echo admin_url('admin.php?page=simple-stats'); ?>" class="button">Restablecer filtros</a>
                <input type="submit" name="export_csv" class="button" value="Exportar a CSV" style="margin-left: 10px;">
            </div>
        </form>

        <div id="loading-spinner" style="display: none; text-align: center; margin: 20px 0;">
            <span style="font-size: 16px;">Cargando...</span>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#stats-form').on('submit', function() {
                $('#loading-spinner').show();
            });

            function toggleDateFields(view) {
                $('#daily-fields').css('display', view === 'daily' ? 'inline' : 'none');
                $('#monthly-fields').css('display', view === 'monthly' ? 'inline' : 'none');
            }
        });
        </script>

        <style>
        #loading-spinner {
            font-weight: bold;
        }
        </style>

        <?php
        // Manejar exportación a CSV
        if (isset($_GET['export_csv'])) {
            $filename = 'visits-stats-' . $view . '-' . date('Y-m-d') . '.csv';
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');
            if ($view === 'daily') {
                fputcsv($output, ['Fecha', 'Visitas Totales', 'Páginas y Entradas', 'Productos, Categorías y Tienda']);
                foreach ($filtered_daily_stats as $date => $stats) {
                    fputcsv($output, [$date, $stats['total'], $stats['site'], $stats['products']]);
                }
            } elseif ($view === 'monthly') {
                fputcsv($output, ['Mes', 'Visitas Totales', 'Páginas y Entradas', 'Productos, Categorías y Tienda']);
                foreach ($filtered_monthly_stats as $month => $stats) {
                    fputcsv($output, [date_i18n('F Y', strtotime($month . '-01')), $stats['total'], $stats['site'], $stats['products']]);
                }
            } else {
                fputcsv($output, ['Año', 'Visitas Totales', 'Páginas y Entradas', 'Productos, Categorías y Tienda']);
                foreach ($filtered_annual_stats as $year => $stats) {
                    fputcsv($output, [$year, $stats['total'], $stats['site'], $stats['products']]);
                }
            }
            fclose($output);
            exit;
        }
        ?>

        <?php if ($view === 'daily'): ?>
            <h2>Resumen del período (<?php echo esc_html($start_date); ?> al <?php echo esc_html($end_date); ?>)</h2>
            <p><strong>Visitas a páginas y entradas:</strong> <?php echo (int) $total_daily['site']; ?></p>
            <p><strong>Visitas a productos, categorías y página de tienda:</strong> <?php echo (int) $total_daily['products']; ?></p>
            <p><strong>Total de visitas:</strong> <?php echo (int) $total_daily['total']; ?></p>

            <h2>Gráfico de visitas diarias</h2>
            <div style="background: #fff; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <?php simple_render_visits_chart($filtered_daily_stats, 'daily'); ?>
            </div>

            <h2>Páginas y entradas más visitadas</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Visitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($top_posts)): ?>
                        <?php foreach ($top_posts as $post): ?>
                            <tr>
                                <td><?php echo esc_html($post['title']); ?></td>
                                <td><?php echo (int) $post['visits']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2">No hay datos disponibles.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2>Productos y página de tienda más visitados</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Visitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($top_products)): ?>
                        <?php foreach ($top_products as $product): ?>
                            <tr>
                                <td><?php echo esc_html($product['title']); ?></td>
                                <td><?php echo (int) $product['visits']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2">No hay datos disponibles.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2>Categorías de productos más visitadas</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Nombre de la categoría</th>
                        <th>Visitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($top_categories)): ?>
                        <?php foreach ($top_categories as $category): ?>
                            <tr>
                                <td><?php echo esc_html($category['name']); ?></td>
                                <td><?php echo (int) $category['visits']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2">No hay datos disponibles.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2>Estadísticas diarias</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Visitas totales</th>
                        <th>Visitas a páginas y entradas</th>
                        <th>Visitas a productos, categorías y tienda</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($paged_daily_stats)): ?>
                        <?php foreach ($paged_daily_stats as $date => $stats): ?>
                            <tr>
                                <td><?php echo esc_html($date); ?></td>
                                <td><?php echo (int) $stats['total']; ?></td>
                                <td><?php echo (int) $stats['site']; ?></td>
                                <td><?php echo (int) $stats['products']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4">No hay datos disponibles para el período seleccionado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_days > $per_page): ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        $pagination_args = array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'total' => $max_pages,
                            'current' => $page,
                            'show_all' => false,
                            'end_size' => 1,
                            'mid_size' => 2,
                            'prev_text' => '« Anterior',
                            'next_text' => 'Siguiente »',
                            'type' => 'plain',
                        );
                        echo paginate_links($pagination_args);
                        ?>
                    </div>
                </div>
            <?php endif; ?>

        <?php elseif ($view === 'monthly'): ?>
            <h2>Resumen del período (<?php echo esc_html(date_i18n('F Y', strtotime($start_month . '-01'))); ?> al <?php echo esc_html(date_i18n('F Y', strtotime($end_month . '-01'))); ?>)</h2>
            <p><strong>Visitas a páginas y entradas:</strong> <?php echo (int) $total_monthly['site']; ?></p>
            <p><strong>Visitas a productos, categorías y página de tienda:</strong> <?php echo (int) $total_monthly['products']; ?></p>
            <p><strong>Total de visitas:</strong> <?php echo (int) $total_monthly['total']; ?></p>

            <h2>Estadísticas mensuales</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th>Visitas totales</th>
                        <th>Visitas a páginas y entradas</th>
                        <th>Visitas a productos, categorías y tienda</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($filtered_monthly_stats)): ?>
                        <?php foreach ($filtered_monthly_stats as $month => $stats): ?>
                            <tr>
                                <td><?php echo esc_html(date_i18n('F Y', strtotime($month . '-01'))); ?></td>
                                <td><?php echo (int) $stats['total']; ?></td>
                                <td><?php echo (int) $stats['site']; ?></td>
                                <td><?php echo (int) $stats['products']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4">No hay datos disponibles para el período seleccionado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2>Páginas y entradas más visitadas</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Visitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($top_posts)): ?>
                        <?php foreach ($top_posts as $post): ?>
                            <tr>
                                <td><?php echo esc_html($post['title']); ?></td>
                                <td><?php echo (int) $post['visits']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2">No hay datos disponibles.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2>Productos y página de tienda más visitados</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Visitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($top_products)): ?>
                        <?php foreach ($top_products as $product): ?>
                            <tr>
                                <td><?php echo esc_html($product['title']); ?></td>
                                <td><?php echo (int) $product['visits']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2">No hay datos disponibles.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2>Categorías de productos más visitadas</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Nombre de la categoría</th>
                        <th>Visitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($top_categories)): ?>
                        <?php foreach ($top_categories as $category): ?>
                            <tr>
                                <td><?php echo esc_html($category['name']); ?></td>
                                <td><?php echo (int) $category['visits']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2">No hay datos disponibles.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php else: ?>
            <h2>Resumen anual (<?php echo esc_html($current_year); ?>)</h2>
            <p><strong>Visitas a páginas y entradas:</strong> <?php echo (int) ($annual_stats[$current_year]['site'] ?? 0); ?></p>
            <p><strong>Visitas a productos, categorías y página de tienda:</strong> <?php echo (int) ($annual_stats[$current_year]['products'] ?? 0); ?></p>
            <p><strong>Total de visitas:</strong> <?php echo (int) ($annual_stats[$current_year]['total'] ?? 0); ?></p>

            <h2>Estadísticas anuales</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Año</th>
                        <th>Visitas totales</th>
                        <th>Visitas a páginas y entradas</th>
                        <th>Visitas a productos, categorías y tienda</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($filtered_annual_stats)): ?>
                        <?php foreach ($filtered_annual_stats as $year => $stats): ?>
                            <tr>
                                <td><?php echo esc_html($year); ?></td>
                                <td><?php echo (int) $stats['total']; ?></td>
                                <td><?php echo (int) $stats['site']; ?></td>
                                <td><?php echo (int) $stats['products']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4">No hay datos disponibles para <?php echo esc_html($current_year); ?>.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2>Páginas y entradas más visitadas</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Visitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($top_posts)): ?>
                        <?php foreach ($top_posts as $post): ?>
                            <tr>
                                <td><?php echo esc_html($post['title']); ?></td>
                                <td><?php echo (int) $post['visits']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2">No hay datos disponibles.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2>Productos y página de tienda más visitados</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Visitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($top_products)): ?>
                        <?php foreach ($top_products as $product): ?>
                            <tr>
                                <td><?php echo esc_html($product['title']); ?></td>
                                <td><?php echo (int) $product['visits']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2">No hay datos disponibles.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2>Categorías de productos más visitadas</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Nombre de la categoría</th>
                        <th>Visitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($top_categories)): ?>
                        <?php foreach ($top_categories as $category): ?>
                            <tr>
                                <td><?php echo esc_html($category['name']); ?></td>
                                <td><?php echo (int) $category['visits']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2">No hay datos disponibles.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <p style="margin-top: 20px; text-align: right; font-style: italic;">Implementado por Sant77_ec</p>
    </div>
    <?php
}

// Función auxiliar para obtener los posts más visitados
function get_top_visited_posts($post_types, $start_date, $end_date, $limit = 5) {
    if (!is_array($post_types)) {
        $post_types = array($post_types);
    }
    $shop_page_id = function_exists('wc_get_page_id') ? wc_get_page_id('shop') : 0;
    if (in_array('product', $post_types) && $shop_page_id) {
        $post_types[] = 'page';
    }

    $args = [
        'post_type' => $post_types,
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'simple_daily_visits',
                'compare' => 'EXISTS'
            ]
        ]
    ];

    $query = new WP_Query($args);
    $posts_data = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $daily_visits = get_post_meta($post_id, 'simple_daily_visits', true);
            $total_visits = 0;

            if (is_array($daily_visits)) {
                foreach ($daily_visits as $date => $count) {
                    if ($date >= $start_date && $date <= $end_date) {
                        $total_visits += $count;
                    }
                }
            }

            if ($total_visits > 0) {
                $posts_data[] = array(
                    'id' => $post_id,
                    'title' => get_the_title() ?: 'Página de tienda',
                    'visits' => $total_visits
                );
            }
        }
        wp_reset_postdata();
    }

    usort($posts_data, function($a, $b) {
        return $b['visits'] - $a['visits'];
    });

    if (in_array('product', $post_types)) {
        $posts_data = array_filter($posts_data, function($post) use ($shop_page_id) {
            $post_type = get_post_type($post['id']);
            return $post_type === 'product' || $post['id'] == $shop_page_id;
        });
    } else {
        $posts_data = array_filter($posts_data, function($post) use ($shop_page_id) {
            return $post['id'] != $shop_page_id;
        });
    }

    return array_slice(array_values($posts_data), 0, $limit);
}

// Función auxiliar para obtener las categorías más visitadas
function get_top_visited_categories($start_date, $end_date, $limit = 5) {
    $args = [
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'meta_query' => [
            [
                'key' => 'simple_daily_visits',
                'compare' => 'EXISTS'
            ]
        ]
    ];

    $categories = get_terms($args);
    $categories_data = array();

    foreach ($categories as $category) {
        $daily_visits = get_term_meta($category->term_id, 'simple_daily_visits', true);
        $total_visits = 0;

        if (is_array($daily_visits)) {
            foreach ($daily_visits as $date => $count) {
                if ($date >= $start_date && $date <= $end_date) {
                    $total_visits += $count;
                }
            }
        }

        if ($total_visits > 0) {
            $categories_data[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'visits' => $total_visits
            );
        }
    }

    usort($categories_data, function($a, $b) {
        return $b['visits'] - $a['visits'];
    });

    return array_slice($categories_data, 0, $limit);
}

// Función para renderizar el gráfico
function simple_render_visits_chart($stats, $view) {
    if (empty($stats)) {
        echo '<p>No hay datos para mostrar en el gráfico.</p>';
        return;
    }

    ksort($stats);
    $labels = array_keys($stats);
    $total_visits = array_column($stats, 'total');
    $site_visits = array_column($stats, 'site');
    $product_visits = array_column($stats, 'products');

    if ($view === 'daily') {
        $labels = array_map(function($date) {
            return date('d M', strtotime($date));
        }, $labels);
    } else {
        $labels = array_map(function($month) {
            return date_i18n('F Y', strtotime($month . '-01'));
        }, $labels);
    }

    $chart_data = array(
        'labels' => $labels,
        'datasets' => array(
            array(
                'label' => 'Visitas Totales',
                'data' => $total_visits,
                'backgroundColor' => 'rgba(52, 152, 219, 0.2)',
                'borderColor' => 'rgba(52, 152, 219, 1)',
                'borderWidth' => 2,
                'fill' => true,
                'tension' => 0.3,
                'pointRadius' => 4,
                'pointBackgroundColor' => 'rgba(52, 152, 219, 1)',
            ),
            array(
                'label' => 'Visitas a Páginas y Entradas',
                'data' => $site_visits,
                'backgroundColor' => 'rgba(46, 204, 113, 0.2)',
                'borderColor' => 'rgba(46, 204, 113, 1)',
                'borderWidth' => 2,
                'fill' => true,
                'tension' => 0.3,
                'pointRadius' => 4,
                'pointBackgroundColor' => 'rgba(46, 204, 113, 1)',
            ),
            array(
                'label' => 'Visitas a Productos, Categorías y Tienda',
                'data' => $product_visits,
                'backgroundColor' => 'rgba(231, 76, 60, 0.2)',
                'borderColor' => 'rgba(231, 76, 60, 1)',
                'borderWidth' => 2,
                'fill' => true,
                'tension' => 0.3,
                'pointRadius' => 4,
                'pointBackgroundColor' => 'rgba(231, 76, 60, 1)',
            )
        )
    );

    ?>
    <canvas id="visitsChart" style="max-height: 400px;"></canvas>
    <script>
    jQuery(document).ready(function($) {
        var ctx = document.getElementById('visitsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: <?php echo json_encode($chart_data); ?>,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 14,
                                family: "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif"
                            },
                            padding: 20,
                            boxWidth: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 14 },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 4
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 12 },
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: { size: 12 }
                        },
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    }
                }
            }
        });
    });
    </script>
    <?php
}

// Cargar Chart.js
function simple_stats_enqueue_scripts($hook) {
    if ($hook != 'toplevel_page_simple-stats') {
        return;
    }
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array('jquery'), null, true);
}
add_action('admin_enqueue_scripts', 'simple_stats_enqueue_scripts');
?>
