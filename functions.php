<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/* --- Enqueue parent and child styles + scripts --- */
add_action( 'wp_enqueue_scripts', 'tt4_child_enqueue_assets' );
function tt4_child_enqueue_assets(){
    // Parent style
    wp_enqueue_style( 'tt4-parent-style', get_template_directory_uri() . '/style.css', array(), wp_get_theme( get_template() )->get('Version') );

    // Child base styles
    wp_enqueue_style( 'tt4-child-style', get_stylesheet_directory_uri() . '/style.css', array('tt4-parent-style'), filemtime( get_stylesheet_directory() . '/style.css' ) );

    // Responsive
    wp_enqueue_style( 'tt4-child-responsive', get_stylesheet_directory_uri() . '/responsive.css', array('tt4-child-style'), filemtime( get_stylesheet_directory() . '/responsive.css' ) );

    // AJAX script
    wp_enqueue_script( 'tt4-projects-ajax', get_stylesheet_directory_uri() . '/js/projects-ajax.js', array('jquery'), filemtime( get_stylesheet_directory() . '/js/projects-ajax.js' ), true );

    wp_localize_script( 'tt4-projects-ajax', 'tt4_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'tt4_projects_nonce' )
    ) );
}

/* --- Register Custom Post Type: Projects --- */
add_action( 'init', 'tt4_register_projects_cpt' );
function tt4_register_projects_cpt(){
    $labels = array(
        'name' => 'Проекты',
        'singular_name' => 'Проект',
        'menu_name' => 'Проекты',
        'name_admin_bar' => 'Проект',
        'add_new' => 'Добавить проект',
        'add_new_item' => 'Добавить новый проект',
        'edit_item' => 'Редактировать проект',
        'new_item' => 'Новый проект',
        'view_item' => 'Просмотреть проект',
        'search_items' => 'Поиск проектов',
        'not_found' => 'Проекты не найдены',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'show_in_rest' => true,
        'supports' => array('title','editor','thumbnail','excerpt','custom-fields'),
        'rewrite' => array('slug' => 'projects'),
        'menu_position' => 5,
        'menu_icon' => 'dashicons-portfolio',
    );

    register_post_type( 'projects', $args );
}

/* --- Register Taxonomy: project_category (Категории проектов) --- */
add_action( 'init', 'tt4_register_project_categories' );
function tt4_register_project_categories(){
    $labels = array(
        'name' => 'Категории проектов',
        'singular_name' => 'Категория проекта',
        'search_items' => 'Искать категории',
        'all_items' => 'Все категории',
        'edit_item' => 'Редактировать категорию',
        'add_new_item' => 'Добавить категорию',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'hierarchical' => true,
        'rewrite' => array('slug' => 'project-category'),
        'show_in_rest' => true,
    );

    register_taxonomy( 'project_category', array('projects'), $args );
}

/* --- Create default terms on theme activation --- */
add_action( 'after_switch_theme', 'tt4_create_default_terms' );
function tt4_create_default_terms(){
    $terms = array( 'разработка', 'дизайн', 'верстка', 'маркетинг' );
    foreach( $terms as $t ){
        if ( ! term_exists( $t, 'project_category' ) ){
            wp_insert_term( $t, 'project_category' );
        }
    }
    // Flush rewrite rules so CPT slugs work immediately
    flush_rewrite_rules();
}

/* --- ACF: register fields programmatically if ACF is active (optional) --- */
add_action( 'acf/init', 'tt4_register_acf_fields' );
function tt4_register_acf_fields(){
    if( function_exists('acf_add_local_field_group') ){
        acf_add_local_field_group(array(
            'key' => 'group_projects_meta',
            'title' => 'Проекты — поля',
            'fields' => array(
                array(
                    'key' => 'field_cost',
                    'label' => 'Стоимость',
                    'name' => 'cost',
                    'type' => 'number',
                    'instructions' => 'Стоимость, числовое значение',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_gallery',
                    'label' => 'Галерея проекта',
                    'name' => 'gallery',
                    'type' => 'gallery',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_extra_desc',
                    'label' => 'Дополнительное описание',
                    'name' => 'extra_description',
                    'type' => 'wysiwyg',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_time',
                    'label' => 'Время разработки',
                    'name' => 'time_to_dev',
                    'type' => 'text',
                    'required' => 0,
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'projects',
                    ),
                ),
            ),
        ));
    }
}

/* --- AJAX handler: filter_projects --- */
add_action( 'wp_ajax_filter_projects', 'tt4_ajax_filter_projects' );
add_action( 'wp_ajax_nopriv_filter_projects', 'tt4_ajax_filter_projects' );

function tt4_ajax_filter_projects(){
    // Check nonce
    if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'tt4_projects_nonce' ) ) {
        wp_send_json_error( array('message'=>'Nonce fail') );
        wp_die();
    }

    $sort = isset($_POST['sort']) ? sanitize_text_field( $_POST['sort'] ) : 'default';
    $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;

    $args = array(
        'post_type' => 'projects',
        'post_status' => 'publish',
        'posts_per_page' => 9,
        'paged' => $paged,
    );

    // Sorting logic
    switch( $sort ){
        case 'date_new':
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
        case 'date_old':
            $args['orderby'] = 'date';
            $args['order'] = 'ASC';
            break;
        case 'cost_asc':
            $args['meta_key'] = 'cost';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'ASC';
            break;
        case 'cost_desc':
            $args['meta_key'] = 'cost';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
            break;
        default:
            // default order by date desc
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
    }

    $q = new WP_Query( $args );

    ob_start();

    if ( $q->have_posts() ):
        while( $q->have_posts() ): $q->the_post();
            // Card markup (same as in template)
            $post_id = get_the_ID();
            // Thumbnail or first gallery image
            $thumb = '';
            if ( has_post_thumbnail( $post_id ) ){
                $thumb = get_the_post_thumbnail_url( $post_id, 'large' );
            } else {
                $gallery = function_exists('get_field') ? get_field('gallery', $post_id) : false;
                if ( $gallery && is_array($gallery) && !empty($gallery) ){
                    $thumb = esc_url( $gallery[0]['sizes']['large'] ?? $gallery[0]['url'] );
                }
            }
            $terms = get_the_terms( $post_id, 'project_category' );
            $term_name = $terms && !is_wp_error($terms) ? esc_html( $terms[0]->name ) : '';
            $cost = function_exists('get_field') ? get_field('cost', $post_id) : get_post_meta($post_id, 'cost', true);

            ?>
            <article class="project-card">
                <?php if ( $thumb ): ?>
                    <img src="<?php echo esc_url($thumb); ?>" class="project-thumb" alt="<?php the_title_attribute(); ?>">
                <?php else: ?>
                    <div style="height:160px;background:#eee;display:flex;align-items:center;justify-content:center;color:#999;">
                        Нет изображения
                    </div>
                <?php endif; ?>
                <div class="project-body">
                    <h3 class="project-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                    <div class="project-meta">
                        <?php if ( $term_name ): ?><span class="project-cat"><?php echo esc_html($term_name); ?></span><?php endif; ?>
                        <span class="project-price"><?php echo $cost !== '' ? esc_html( $cost ) . ' ₽' : ''; ?></span>
                    </div>
                </div>
            </article>
            <?php
        endwhile;
        wp_reset_postdata();
    else:
        echo '<p class="center">Проекты не найдены.</p>';
    endif;

    $html = ob_get_clean();

    wp_send_json_success( array( 'html' => $html ) );
    wp_die();
}
