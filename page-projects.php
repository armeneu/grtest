<?php
/**
 * Template Name: Projects Page
 * Template Post Type: page
 */

get_header(); ?>

<div class="projects-wrap">
  <div class="projects-controls">
    <label for="projects-sort-select" class="screen-reader-text">Сортировка</label>
    <select id="projects-sort-select" class="projects-select" name="sort">
      <option value="default">По умолчанию</option>
      <option value="date_new">По дате (сначала новые)</option>
      <option value="date_old">По дате (сначала старые)</option>
      <option value="cost_asc">Стоимость ↑</option>
      <option value="cost_desc">Стоимость ↓</option>
    </select>
  </div>

  <div id="projects-grid" class="projects-grid">
    <?php
    // Initial query (server side): show first set
    $args = array(
      'post_type' => 'projects',
      'posts_per_page' => 9,
      'post_status' => 'publish',
      'orderby' => 'date',
      'order' => 'DESC',
    );
    $q = new WP_Query( $args );

    if ( $q->have_posts() ):
      while ( $q->have_posts() ): $q->the_post();
        $post_id = get_the_ID();
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
    ?>
  </div>
</div>

<?php get_footer(); ?>
