<?php

/**
 * Template Name: Search Page Template
 */

get_header(); ?>

<?php

global $post, $wp;

$paged = ( get_query_var( 'paged' ) ) ? absint( get_query_var( 'paged' ) ) : 1;

$args = array(
      'post_type' => 'property',
      'posts_per_page' => 12,
      'post_status' => 'publish',
      'paged' => $paged,
  );

if (isset($_GET['sortby']) && in_array($_GET['sortby'], array('a_price', 'd_price', 'a_date', 'd_date', 'featured', 'most_viewed'))) {
    if ($_GET['sortby'] == 'a_price') {
        $args['orderby'] = 'meta_value_num';
        $args['meta_key'] = ERE_METABOX_PREFIX . 'property_price';
        $args['order'] = 'ASC';
    } else if ($_GET['sortby'] == 'd_price') {
        $args['orderby'] = 'meta_value_num';
        $args['meta_key'] = ERE_METABOX_PREFIX . 'property_price';
        $args['order'] = 'DESC';
    } else if ($_GET['sortby'] == 'featured') {
        $args['orderby'] = array(
            'meta_value_num' => 'DESC',
            'date' => 'DESC',
        );
        $args['meta_key'] = ERE_METABOX_PREFIX . 'property_featured';
    }
    else if ($_GET['sortby'] == 'most_viewed') {
        $args['orderby'] = 'meta_value_num';
        $args['meta_key'] = ERE_METABOX_PREFIX . 'property_views_count';
        $args['order'] = 'DESC';
    }
    else if ($_GET['sortby'] == 'a_date') {
        $args['orderby'] = 'date';
        $args['order'] = 'ASC';
    } else if ($_GET['sortby'] == 'd_date') {
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
    }

} else {

    $featured_toplist = ere_get_option('featured_toplist', 1);

    if($featured_toplist != 0) {
        $args['orderby'] = array(
            'menu_order'=>'ASC',
            'meta_value_num' => 'DESC',
            'date' => 'DESC',
        );
        $args['meta_key'] = ERE_METABOX_PREFIX . 'property_featured';
    }
} 

if( ! empty( $_GET['title'] ) ) {

    $args['s'] = $_GET['title'];
}

$meta_query = array();

$price_array = array(
    1 => array( 'min_price' => 1000000, 'max_price' => 3999999 ),
    2 => array( 'min_price' => 4000000, 'max_price' => 6999999 ),
    3 => array( 'min_price' => 7000000, 'max_price' => 9999999 ),
    4 => array( 'min_price' => 10000000, 'max_price' => 14999999 ),
    5 => array( 'min_price' => 15000000, 'max_price' => 19999999 ),
    6 => array( 'min_price' => 20000000, 'max_price' => 29999999 ),
    7 => array( 'min_price' => 30000000, 'max_price' => 49999999 ),
    8 => array( 'min_price' => 50000000, 'max_price' => 99999999 ),
    9 => array( 'min_price' => 100000000, 'max_price' => 150000000 )
);

if( ! empty( $_GET['price-range'] ) ) {

  $min_price = $price_array[$_GET['price-range']]['min_price'];

  $max_price = $price_array[$_GET['price-range']]['max_price'];

  $meta_query[] = array(
    'key' => ERE_METABOX_PREFIX . 'property_price',
    'value' => array( $min_price, $max_price ),
    'type' => 'numeric',
    'compare' => 'BETWEEN'
  ); 
}

if( ! empty( $_GET['bedrooms'] ) ) {

  $meta_query[] = array(
    'key' => ERE_METABOX_PREFIX . 'property_rooms',
    'value' => $_GET['bedrooms'],
    'type' => 'numeric',
    'compare' => '='
  );
}

if( ! empty( $_GET['bathrooms'] ) ) {

  $meta_query[] = array(
    'key' => ERE_METABOX_PREFIX . 'property_bathrooms',
    'value' => $_GET['bathrooms'],
    'type' => 'numeric',
    'compare' => '='
  );
}

if( ! empty( $_GET['constructions'] ) ) {

  $meta_query[] = array(
    'key' => 'constructions',
    'value' => $_GET['constructions'],
    'compare' => '='
  );
}

$meta_count = count($meta_query);

if ($meta_count > 0) {
    $args['meta_query'] = array(
        'relation' => 'AND',
        $meta_query
    );
}

$term_query = array();

if ( ! empty( $_GET['type'] ) ) {
    $term_query[] = array(
      'taxonomy' => 'property-type',
      'terms' => $_GET['type'],
      'field' => 'slug',
      'operator' => 'IN'
    );
}

if ( ! empty( $_GET['state'] ) ) {
    $term_query[] = array(
      'taxonomy' => 'property-state',
      'terms' => $_GET['state'],
      'field' => 'slug',
      'operator' => 'IN'
    );
}

if ( ! empty( $_GET['city'] ) ) {
    $term_query[] = array(
      'taxonomy' => 'property-city',
      'terms' => $_GET['city'],
      'field' => 'slug',
      'operator' => 'IN'
    );
}

if ( ! empty( $_GET['status'] ) ) {
    $term_query[] = array(
      'taxonomy' => 'property-status',
      'terms' => $_GET['status'],
      'field' => 'slug',
      'operator' => 'IN'
    );
}

if ( ! empty( $_GET['feature'] ) ) {
    $term_query[] = array(
      'taxonomy' => 'property-feature',
      'terms' => $_GET['feature'],
      'field' => 'slug',
      'compare' => 'IN'
    );
}

$term_count = count($term_query);

if ($term_count > 0) {
    $args['tax_query'] = array(
        'relation' => 'AND',
        $term_query
    );
}

$query = new WP_Query($args);

$total_post = $query->found_posts;

// if property not found then search related // -------------------------------

if( $total_post == 0 ) {

  $related_args = array(
      'post_type' => 'property',
      'posts_per_page' => 12,
      'post_status' => 'publish',
      'orderby' => 'rand',
      'order' => 'DESC',
      'paged' => $paged,
  );

  $related_term_query = array();

  if ( ! empty( $_GET['type'] ) ) {
      $related_term_query[] = array(
        'taxonomy' => 'property-type',
        'terms' => $_GET['type'],
        'field' => 'slug',
        'operator' => 'IN'
      );
  }

  if ( ! empty( $_GET['state'] ) ) {
      $related_term_query[] = array(
        'taxonomy' => 'property-state',
        'terms' => $_GET['state'],
        'field' => 'slug',
        'operator' => 'IN'
      );
  }

  if ( ! empty( $_GET['city'] ) ) {
      $related_term_query[] = array(
        'taxonomy' => 'property-city',
        'terms' => $_GET['city'],
        'field' => 'slug',
        'operator' => 'IN'
      );
  }

  if ( ! empty( $_GET['status'] ) ) {
      $related_term_query[] = array(
        'taxonomy' => 'property-status',
        'terms' => $_GET['status'],
        'field' => 'slug',
        'operator' => 'IN'
      );
  }

  $related_term_count = count($related_term_query);

  if ($related_term_count > 0) {
      $related_args['tax_query'] = array(
          'relation' => 'AND',
          $related_term_query
      );
  }

  $data = new WP_Query($related_args);

} else {

  $data = new WP_Query($args);
}

?>

<div id="Casas" class="listing-view">
   <h3 class="heading-2-sales">
    <?php if( ! empty( $_GET['type'] ) ): ?>
      <strong>Resultados de búsqueda en:</strong> <?php echo $_GET['type']; ?> <?php echo ! empty($_GET['bedrooms']) ? 'en '.$_GET['bedrooms']. ' Cuartos' : ''; ?> <?php echo ! empty($_GET['bathrooms']) ? 'en '.$_GET['bathrooms']. ' Baños' : ''; ?> <?php echo ! empty($_GET['status']) ? 'en '.$_GET['status'] : ''; ?> 
    <?php endif; ?>
   </h3>
   <div class="search">
      <div class="home-hero-search tabs-search tabs-search-brd">
         <form method="get" action="<?php echo esc_url( home_url('/advanced-search') ); ?>" class="search-form">
         	<div class="sel_box_sec">
           	<div class="sel_col property-type">
              <select class="dropdown-2 w-dropdown type" name="type">
                 <option value="">Tipo De Propiedad</option>
                 <?php $property_type = get_terms('property-type', array('hide_empty' => 0));
                 foreach($property_type as $type) : ?>
                 <option value="<?php echo $type->slug; ?>" <?php echo ( $type->slug == $_GET['type'] ) ? "selected" : ''; ?>><?php echo $type->name; ?></option>
                 <?php endforeach; ?>
              </select>
          	</div>
            <div class="sel_col property-status">
            </div>
          	<div class="sel_col property-price">
              <select class="dropdown-2 w-dropdown price-range" name="price-range">
                 <option value="0">Precio Min - Max</option> 
                 <?php for( $i = 1; $i <= 9; $i++ ) { ?>

                 <option value="<?php echo $i; ?>" <?php echo ( $_GET['price-range'] == $i) ? 'selected': ''; ?>>Precio $<?php echo number_format($price_array[$i]['min_price']); ?> - $<?php echo number_format($price_array[$i]['max_price']); ?></option>

                 <?php } ?>
              </select>
          	</div>
          	<div class="sel_col property-bedrooms">
          	</div>
          	<div class="sel_col property-bathrooms">
          	</div>
          	<div class="sel_col property-feature">
          	</div>
            <div class="sel_col property-constructions">
            </div>
          	<div class="sel_col property-title">
          	</div>
          </div> 
          <div class="l_sel_box_sec">
          <input type="submit" value="Buscar" class="link w-button search-button">
          </div>
         </form>
      </div>
   </div>

   <div class="resultados">
      <div class="resultados-col">
         <div class="text-block-17"><?php echo ! empty( $total_post ) ? $total_post.' Propiedades' : $data->found_posts.' Propiedades relacionadas'; ?></div>
      </div>
      <div class="resultados-col right">
        <div class="archive-property-action-item sort-view-property">
            <div class="sort-property property-filter">
                <span
                    class="property-filter-placeholder"><?php esc_html_e('Ordenar Por', 'essential-real-estate'); ?></span>
                <ul>
                    <li><a data-sortby="default" href="<?php
                        $pot_link_sortby = add_query_arg(array('sortby' => 'default'));
                        echo esc_url($pot_link_sortby) ?>"
                           title="<?php esc_html_e('Orden por defecto', 'essential-real-estate'); ?>"><?php esc_html_e('Orden por defecto', 'essential-real-estate'); ?></a>
                    </li>
                    <li><a data-sortby="featured" href="<?php
                        $pot_link_sortby = add_query_arg(array('sortby' => 'featured'));
                        echo esc_url($pot_link_sortby) ?>"
                           title="<?php esc_html_e('Destacados', 'essential-real-estate'); ?>"><?php esc_html_e('Destacados', 'essential-real-estate'); ?></a>
                    </li>
                    <li><a data-sortby="most_viewed" href="<?php
                        $pot_link_sortby = add_query_arg(array('sortby' => 'most_viewed'));
                        echo esc_url($pot_link_sortby) ?>"
                           title="<?php esc_html_e('Mas Visto', 'essential-real-estate'); ?>"><?php esc_html_e('Mas Visto', 'essential-real-estate'); ?></a>
                    </li>
                    <li><a data-sortby="a_price" href="<?php
                        $pot_link_sortby = add_query_arg(array('sortby' => 'a_price'));
                        echo esc_url($pot_link_sortby) ?>"
                           title="<?php esc_html_e('Precio (Bajo a Alto)', 'essential-real-estate'); ?>"><?php esc_html_e('Precio (Bajo a Alto)', 'essential-real-estate'); ?></a>
                    </li>
                    <li><a data-sortby="d_price" href="<?php
                        $pot_link_sortby = add_query_arg(array('sortby' => 'd_price'));
                        echo esc_url($pot_link_sortby) ?>"
                           title="<?php esc_html_e('Precio (Alto a Bajo)', 'essential-real-estate'); ?>"><?php esc_html_e('Precio (Alto a Bajo)', 'essential-real-estate'); ?></a>
                    </li>
                    <li><a data-sortby="a_date" href="<?php
                        $pot_link_sortby = add_query_arg(array('sortby' => 'a_date'));
                        echo esc_url($pot_link_sortby) ?>"
                           title="<?php esc_html_e('Fecha (Viejo a Nuevo)', 'essential-real-estate'); ?>"><?php esc_html_e('Fecha (Viejo a Nuevo)', 'essential-real-estate'); ?></a>
                    </li>
                    <li><a data-sortby="d_date" href="<?php
                        $pot_link_sortby = add_query_arg(array('sortby' => 'd_date'));
                        echo esc_url($pot_link_sortby) ?>"
                           title="<?php esc_html_e('Fecha (Nuevo a Viejo)', 'essential-real-estate'); ?>"><?php esc_html_e('Fecha (Nuevo a Viejo)', 'essential-real-estate'); ?></a>
                    </li>
                </ul>
            </div>
            <div class="view-as">
                <a href="<?php echo esc_url( home_url('/categoria-mapa/') ).strstr($_SERVER['REQUEST_URI'], '?'); ?>" class="btn-map w-button">Ver Mapa</a>
            </div>
        </div>
      </div>
   </div>
   <div class="home-properties-cards-search search-result-section">

   <?php if ( $data->have_posts() ) : while ($data->have_posts()) : $data->the_post(); 

     $args = array('orderby' => 'name', 'order' => 'ASC', 'fields' => 'all');

     $property_type = wp_get_post_terms( get_the_ID(), 'property-type', $args );
     $property_status = wp_get_post_terms( get_the_ID(), 'property-status', $args );
     $property_label = wp_get_post_terms( get_the_ID(), 'property-label', $args );
     $property_state = wp_get_post_terms( get_the_ID(), 'property-state', $args );
     $property_city = wp_get_post_terms( get_the_ID(), 'property-city', $args );
     $property_address = $property_city[0]->name. ', '. $property_state[0]->name;
     $property_price_unit = get_post_meta( get_the_ID(), ERE_METABOX_PREFIX . 'property_price_unit', true );
     $property_price = get_post_meta( get_the_ID(), ERE_METABOX_PREFIX . 'property_price', true );
     $property_price_short = get_post_meta( get_the_ID(), ERE_METABOX_PREFIX . 'property_price_short', true );
     $property_price_prefix = get_post_meta( get_the_ID(), ERE_METABOX_PREFIX . 'property_price_prefix', true );
     $property_price_postfix = get_post_meta( get_the_ID(), ERE_METABOX_PREFIX . 'property_price_postfix', true );
     $property_bedrooms = get_post_meta( get_the_ID(), ERE_METABOX_PREFIX . 'property_bedrooms', true );
     $property_bathrooms = get_post_meta( get_the_ID(), ERE_METABOX_PREFIX . 'property_bathrooms', true );
     $parking = get_post_meta( get_the_ID(), "parking", true );
     $levels = get_post_meta( get_the_ID(), "levels", true );
     $constructions = get_post_meta( get_the_ID(), "constructions", true );
     $half_bathrooms = get_post_meta( get_the_ID(), "half_bathrooms", true );
   
   ?>
      <div class="search-card">
         <div class="larger">
         <?php if(!empty($property_label)) : ?>
            <div class="exclusive" style="background-color: <?php echo get_term_meta( $property_label[0]->term_id, 'property_label_color', true); ?>;">
               <div><?php echo $property_label[0]->name; ?></div>
            </div>
         <?php endif; ?>

         <?php if ( has_post_thumbnail( $post->ID ) ) : ?>
         <div class="image">
            <?php echo get_the_post_thumbnail( $post->ID, 'full' ); ?>
         </div>
         <?php endif; ?>
         </div>

         <div class="div-block-3">
            <div class="text-block-7"><?php the_title(); ?></div>
            <?php if ( ! empty( $property_address ) ): ?>
               <div class="text-block-8"><?php echo esc_attr($property_address); ?></div>
            <?php endif; ?>
            
            <div class="divider"></div>
            
            <?php if( ! empty( $property_price ) ) : ?>
            <div class="price">
                <?php echo $property_price_prefix.' '.ere_get_format_money($property_price_short, $property_price_unit).' '.$property_price_postfix; ?> 
            </div>
            <?php endif; ?>
            
            <div class="text-block-9">
            <?php if ( ! empty( $property_bedrooms ) ) : ?>
               <span><?php echo $property_bedrooms; ?> Cuartos</span>
            <?php endif; ?>
            <?php if ( ! empty( $property_bathrooms ) ) : ?>
               <span><?php echo $property_bathrooms; ?> Baños</span>
            <?php endif; ?>
            <?php if ( ! empty( $half_bathrooms ) ) : ?>       
               <span><?php echo $half_bathrooms; ?>  Medio baños</span>
            <?php endif; ?>
            <?php if ( ! empty( $parking ) ) : ?>
               <span><?php echo $parking; ?>  Estacionamientos</span>
            <?php endif; ?>
            <?php if ( ! empty( $levels ) ) : ?>
               <span><?php echo $levels; ?> Niveles</span>
            <?php endif; ?>
            <?php if ( ! empty( $constructions ) ) : ?>
               <span><?php echo $constructions; ?> M<sup>2</sup> Contrucción</span>
            <?php endif; ?>
            </div>
            
            <a href="<?php the_permalink(); ?>" class="home-services nm w-button">Ver Propiedad</a>
         </div>
      </div>

   <?php endwhile; wp_reset_postdata(); ?>

      <div class="paging-navigation clearfix">
          <?php
     
            echo paginate_links( array(
                'base'         => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
                'total'        => $data->max_num_pages,
                'current'      => max( 1, get_query_var('paged') ),
                'format'       => '?paged=%#%',
                'show_all'     => false,
                'type'         => 'plain',
                'end_size'     => 0,
                'mid_size'     => 5,
                'prev_next'    => true,
                'prev_text'    => sprintf( '%1$s', __( 'Anterior', 'twentytwelve' ) ),
                'next_text'    => sprintf( '%1$s', __( 'Siguiente', 'twentytwelve' ) ),
                'add_args'     => false,
                'add_fragment' => '',
            ) );

          ?> 
      </div>

      <?php else : ?>
        <p><?php _e( 'Lo sentimos, ninguna propiedad coincide con sus criterios.' ); ?></p>
      <?php endif; ?>
   </div>
</div>
<div id="Newsletter" class="newsletter">
   <div class="w-container">
      <h3 class="heading-2-copy">Suscribete y se un VIP Guía</h3>
      <div>No te pierdas ninguna oportunidad y recibe directo a tu correo electronico exlusivas ofertas, preventas y mas.</div>
      <div class="form-block w-form">
         <?php echo do_shortcode('[contact-form-7 id="6644" title="Suscribete y se un VIP Guía"]'); ?>
      </div>
      <div class="disc">No te preocupes, a nadie legusta el correo basura, Grupo Guía no vende ni comparte tu informacion con nadie!</div>
   </div>
</div>

<script>
$(function() {
  $(".sort-property span.property-filter-placeholder").on("click", function(e) {
    $("span.property-filter-placeholder + ul").addClass("active");
    e.stopPropagation()
  });
  $(document).on("click", function(e) {
    if ($(e.target).is("span.property-filter-placeholder + ul") === false) {
      $("span.property-filter-placeholder + ul").removeClass("active");
    }
  });
});
</script>

<?php get_footer(); ?>