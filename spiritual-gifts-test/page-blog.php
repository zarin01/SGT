<?php
get_header();


//Featured Post Query
$fQeuryArgs = array(
    'post_type' => 'post',
    'category_name' => 'featured',
    'post_per_page' => 1,
    'nopaging' => true

);

$featured_query = new WP_Query($fQeuryArgs);
//

//Trending Posts Query
$tQeuryArgs = array(
    'post_type' => 'post',
    'category_name' => 'trending',
    'post_per_page' => 3,
    'nopaging' => true

);

$trending_query = new WP_Query($tQeuryArgs);
//
//Recent Posts Query
$rQeuryArgs = array(
    'post_type' => 'post',
    'category_name' => 'articles',
    'post_per_page' => 12,
    

);

$recent_query = new WP_Query($rQeuryArgs);
//
?>


<div class="blogArchive">
    <div class="f-wrapper">
        <div class="featuredContainer">
        <?php
            if( $featured_query->have_posts() ) :
                while ( $featured_query->have_posts() ) : $featured_query->the_post();
                ?>
                    <div class="f-text">
                        <h3><a href="<?php echo get_permalink(); ?>"><?php echo get_the_title() ?></a></h3>
                        <?php the_excerpt() ?>
                        <a href="<?php echo get_permalink(); ?>"><p>Read More</p></a>
                    </div>        
                    <div class='f-image'><a href="<?php echo get_permalink(); ?>"><?php the_post_thumbnail(); ?></a></div>
                    
                <?php
                endwhile;    
            endif;    
        ?>
        </div>
    </div>
    <h2>Trending Posts</h2>
    <div class="trendingContainer">
        
    <?php
        if( $trending_query->have_posts() ) :
            while ( $trending_query->have_posts() ) : $trending_query->the_post();
            ?>
                <div class="t-card">
                    <div class='t-image'><a href="<?php echo get_permalink(); ?>"><?php the_post_thumbnail(); ?></a></div>
                    <h3><a href="<?php echo get_permalink(); ?>"><?php echo get_the_title() ?></a></h3>
                </div>        
                
                
            <?php
            endwhile;    
        endif;    
    ?>
    </div>
    <div class="r-wrapper">
    <h2>Recent Posts</h2>
    <div class="recentContainer">

    <?php
        if( $recent_query->have_posts() ) :
            while ( $recent_query->have_posts() ) : $recent_query->the_post();
            ?>
                <div class="r-card">
                    <div class='r-image'><a href="<?php echo get_permalink(); ?>"><?php the_post_thumbnail(); ?></a></div>
                    <h3><a href="<?php echo get_permalink(); ?>"><?php echo get_the_title() ?></a></h3>
                    <?php the_excerpt() ?>
                </div>        
                
                
            <?php
            endwhile;    
        endif;    
    ?>
    </div>
    </div>
    
</div>






<?php
get_footer();
?>