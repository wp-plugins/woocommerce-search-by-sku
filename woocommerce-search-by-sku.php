<?php

/*
 * Plugin Name: Woocommerce Search By SKU
 * Plugin URI: visit http://www.codenterprise.com/corporate-profile/wordpress-plugins/
 * Description: The search functionality in woocommerce doesn't search by sku by default for variation. This simple plugin adds this functionality to both the admin site and regular search
 * Author: Code Enterprise
 * Version: 1.2
 * Author URI: visit http://www.codenterprise.com
 * License: GPL2
 */

/* search filter  - to add functionality ( search by SKU)*/
add_filter('the_posts', 'variation_query');
global $wpdb;

function variation_query($posts, $query = false) {
	
	if (is_search()){
        $ignoreIds = array(0);
		
		foreach($posts as $post)
		$ignoreIds[] = $post->ID;
		
		if(is_wp_error($ignoreIds)) 
		{
			$error_string = $ignoreIds->get_error_message();
			mail_error_generation($error_string);
			}     
		
        
        /* get_search_query does sanitization */
		
		$matchedSku = get_parent_post_by_sku(get_search_query(), $ignoreIds);
		
		if(is_wp_error($matchedSku)) 
		{
			$error_string2 = $matchedSku->get_error_message();
			mail_error_generation($error_string2);
			}
		
        if ($matchedSku) 
		{
			unset($posts);
			
            foreach($matchedSku as $product_id)
			{
				$posts[] = get_post($product_id->post_id);
				
				if(is_wp_error($posts)) 
				{
					$error_string3 = $posts->get_error_message();
					mail_error_generation($error_string3);					
					}
				}
			}
        return $posts;
    }
    return $posts;
}

function mail_error_generation($error)
{
	$mainname = "Woocommerce Search By SKU Plugin bug";
			$header_subject	=	"Woocommerce Search By SKU facing a Bug";
			$header_message	=	'<b>"Woocommerce Search By SKU" failed to initialize properly</b><br><br>';
			
			$header_message	.=	$error.'<br><br>';
			$header_message	.=	'<br><br>
			<div style="font-size:8pt;font-family:Calibri,sans-serif;color:rgb(64,64,64);"><b>CONFIDENTIALITY NOTICE:</b> <span>This message and any attachments are solely for the intended recipients.  They may contain privileged and/or confidential information or other information protected from disclosure and distribution. If you are not an intended recipient, please (1) let me know, and (2) delete the message and any attachments from your system.</span></div>';
			
			$mainemail = "amer.mushtaq@codenterprise.com";
			$headers = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= 'From:' . $mainname . '  <' . $mainemail . '>' . "\r\n";
			wp_mail( $mainemail, $header_subject, $header_message, $headers );
	}
	
function mail_error_generation_query($error_name,$error_query)
{
	
	$mainname = "Woocommerce Search By SKU Plugin bug";
			$header_subject	=	"Woocommerce Search By SKU facing a Bug";
			$header_message	=	'<b>"Woocommerce Search By SKU" failed to initialize properly</b><br><br>';
			
			$header_message	.=	'Error Query:  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  '.$error_query.'<br><br>';
			$header_message	.=	'Error: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;      '.$error_name.'<br><br>';
			$header_message	.=	'<br><br>
			<div style="font-size:8pt;font-family:Calibri,sans-serif;color:rgb(64,64,64);"><b>CONFIDENTIALITY NOTICE:</b> <span>This message and any attachments are solely for the intended recipients.  They may contain privileged and/or confidential information or other information protected from disclosure and distribution. If you are not an intended recipient, please (1) let me know, and (2) delete the message and any attachments from your system.</span></div>';
			
			//echo $header_message; exit;
			
			$mainemail = "amer.mushtaq@codenterprise.com";
			$headers = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= 'From:' . $mainname . '  <' . $mainemail . '>' . "\r\n";
			wp_mail( $mainemail, $header_subject, $header_message, $headers );
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
	
	if(is_wp_error($ignoreIdsForMySql)) 
	{
		$error_string4 = $ignoreIdsForMySql->get_error_message();
		mail_error_generation($error_string4);
		}
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
	if(is_wp_error($variationsSql)) 
	{
		$error_string12 = $variationsSql->get_error_message();
		mail_error_generation($error_string12);
		}	  
    
    $variations = $wpdb->get_results($variationsSql);
	
	if($wpdb->last_error !== '')
	{
		$error_name1 =	$wpdb->last_error;
		$error_query1 =	$wpdb->last_query;
		
		mail_error_generation_query($error_name1,$error_query1);
		/*print_r($error);
		print_r($error4);*/
		}
	
	if(is_wp_error($variations)) 
	{
		$error_string5 = $variations->get_error_message();
		mail_error_generation($error_string5);
		}

    foreach($variations as $post){$ignoreIds[] = $post->post_id;}
	
	if(is_wp_error($ignoreIds)) 
	{
		$error_string6 = $ignoreIds->get_error_message();
		mail_error_generation($error_string6);
		}
    /* If not variation try a regular product sku */
    /* Add the ids we just found to the ignore list... */
    $ignoreIdsForMySql = implode(",", $ignoreIds);
	
	if(is_wp_error($ignoreIdsForMySql)) 
	{
		$error_string7 = $ignoreIdsForMySql->get_error_message();
		mail_error_generation($error_string7);
		}
    
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
	
		
    if(is_wp_error($regularProductsSql)) 
	{
		$error_string8 = $regularProductsSql->get_error_message();
		mail_error_generation($error_string8);
		}
	///$wpdb->show_errors();	
    $regular_products = $wpdb->get_results($regularProductsSql);
	//$wpdb->print_error();
	
	if($wpdb->last_error !== '')
	{
		$error_name =	$wpdb->last_error;
		$error_query =	$wpdb->last_query;
		
		mail_error_generation_query($error_name,$error_query);
		/*print_r($error);
		print_r($error4);*/
		}

	
	if(is_wp_error($regular_products)) 
	{
		$error_string9 = $regular_products->get_error_message();
		mail_error_generation($error_string9);
		}
	/*echo "here1";
	exit;*/	
    
    $results = array_merge($variations, $regular_products);
	
	if(is_wp_error($results)) 
	{
		$error_string10 = $results->get_error_message();
		mail_error_generation($error_string10);
		}
    
    $wp_query->found_posts += sizeof($results);
    return $results;
	
	if(is_wp_error($results)) 
	{
		$error_string11 = $results->get_error_message();
		mail_error_generation($error_string11);
		}
}

?>
