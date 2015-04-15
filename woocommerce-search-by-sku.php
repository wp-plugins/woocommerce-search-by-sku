<?php

/*
 * Plugin Name: Woocommerce Search By SKU
 * Plugin URI: visit http://www.codenterprise.com/corporate-profile/wordpress-plugins/
 * Description: The search functionality in woocommerce doesn't search by sku by default for variation. This simple plugin adds this functionality to both the admin site and regular search
 * Author: Code Enterprise
 * Version: 1.0
 * Author URI: visit http://www.codenterprise.com
 * License: GPL2
 */

/* search filter  - to add functionality ( search by SKU)*/
add_filter('the_posts', 'variation_query');

function variation_query($posts, $query = false) {
	if (is_search()){
        $ignoreIds = array(0);
        foreach($posts as $post)
		$ignoreIds[] = $post->ID;
        
        /* get_search_query does sanitization */
        $matchedSku = get_parent_post_by_sku(get_search_query(), $ignoreIds);
        if ($matchedSku) {
			unset($posts);
            foreach($matchedSku as $product_id)
			$posts[] = get_post($product_id->post_id);
        }
        return $posts;
    }
    return $posts;
}

function get_parent_post_by_sku($sku, $ignoreIds) {
    //Check for 
    global $wpdb, $wp_query;
    
    /* Should the query do some extra joins for WPML Enabled sites... */
    $wmplEnabled = false;

    if(defined('WPML_TM_VERSION') && defined('WPML_ST_VERSION') && class_exists("woocommerce_wpml")){
        $wmplEnabled = true;
        /* What language should we search for... */
        $languageCode = ICL_LANGUAGE_CODE;
    }
    
    $results = array();
    /* Search for the sku of a variation and return the parent sku */
    $ignoreIdsForMySql = implode(",", $ignoreIds);
    $variationsSql = "
          SELECT p.post_parent as post_id FROM $wpdb->posts as p
          join $wpdb->postmeta pm
          on p.ID = pm.post_id
          and pm.meta_key='_sku'
          and pm.meta_value LIKE '%$sku%'
          join $wpdb->postmeta visibility
          on p.post_parent = visibility.post_id    
          and visibility.meta_key = '_visibility'
          and visibility.meta_value <> 'hidden'
            ";
    
    
    /* IF WPML Plugin is enabled join and get correct language product. */
    if($wmplEnabled){
        $variationsSql .=
        "join ".$wpdb->prefix."icl_translations t on
         t.element_id = p.post_parent
         and t.element_type = 'post_product'
         and t.language_code = '$languageCode'";
         ;
    }
    
    $variationsSql .= "
          where 1
          AND p.post_parent <> 0
          and p.ID not in ($ignoreIdsForMySql)
          and p.post_status = 'publish'
          group by p.post_parent
          ";
    
    $variations = $wpdb->get_results($variationsSql);

    foreach($variations as $post){$ignoreIds[] = $post->post_id;}
    /* If not variation try a regular product sku */
    /* Add the ids we just found to the ignore list... */
    $ignoreIdsForMySql = implode(",", $ignoreIds);
    
    $regularProductsSql = 
        "SELECT p.ID as post_id FROM $wpdb->posts as p
        join $wpdb->postmeta pm
        on p.ID = pm.post_id
        and  pm.meta_key='_sku' 
        AND pm.meta_value LIKE '%$sku%' 
        join $wpdb->postmeta visibility
        on p.ID = visibility.post_id    
        and visibility.meta_key = '_visibility'
        and visibility.meta_value <> 'hidden'";
    /* IF WPML Plugin is enabled join and get correct language product.  */
    if($wmplEnabled){
        $regularProductsSql .= 
        "join ".$wpdb->prefix."icl_translations t on
         t.element_id = p.ID
         and t.element_type = 'post_product'
         and t.language_code = '$languageCode'";
    }
    $regularProductsSql .= 
        "where 1
        and (p.post_parent = 0 or p.post_parent is null)
        and p.ID not in ($ignoreIdsForMySql)
        and p.post_status = 'publish'
        group by p.ID";
    
    $regular_products = $wpdb->get_results($regularProductsSql);
    
    $results = array_merge($variations, $regular_products);
    
    $wp_query->found_posts += sizeof($results);
    return $results;
}

?>
