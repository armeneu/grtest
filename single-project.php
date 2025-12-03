<?php
// single-project.php
get_header();

if ( have_posts() ) : while ( have_posts() ) : the_post();
  $post_id = get_the_ID();
  $cost = function_exists('get_field') ? get_field('cost', $post_id) : get_post_meta($post_id,'cost',true);
  $time = function_exists('get_field') ? get_field('time_to_dev', $post_id) : get_post_meta($post_id,'time_to_dev',true);
  $extra = function_exists('get_field') ? get_field('extra_description', $post_id) : '';
  $gallery = function_exists('get_field') ? get_field('gallery', $post_id) : false;
  $terms = get_the_terms( $post_id, 'project_category' );
  $cats = $terms && !is_wp_error($terms) ? wp_list_pluck($terms, 'name') : array();
  ?>
  <main class="single-project" style="max-width:1000px;margin:40px auto;padding:0 16px;">
    <article class="project-article">
      <h1><?php the_title(); ?></h1>
      <?php if ( !empty($cats) ): ?>
        <div class="project-meta" style="margin-bottom:12px;">
          <?php echo implode(', ', array_map('esc_html',$cats)); ?>
        </div>
      <?php endif; ?>

      <div class="project-main">
        <div class="project-content">
          <?php the_content(); ?>
        </div>

        <?php if ( $extra ): ?>
          <section class="project-extra">
            <h3>Дополнительное описание</h3>
            <div><?php echo wp_kses_post( $extra ); ?></div>
          </section>
        <?php endif; ?>

        <div class="project-info" style="margin-top:16px;">
          <?php if ( $cost !== '' ): ?><div><strong>Стоимость:</strong> <?php echo esc_html($cost); ?> ₽</div><?php endif; ?>
          <?php if ( $time ): ?><div><strong>Время разработки:</strong> <?php echo esc_html($time); ?></div><?php endif; ?>
        </div>

        <?php if ( $gallery && is_array($gallery) ): ?>
          <div class="project-gallery">
            <?php foreach( $gallery as $img ): 
              $url = esc_url( $img['url'] );
              $alt = esc_attr( $img['alt'] ?? get_the_title() );
              ?>
              <img src="<?php echo $url; ?>" alt="<?php echo $alt; ?>">
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      </div>
    </article>
  </main>

  <?php
endwhile; endif;

get_footer();
