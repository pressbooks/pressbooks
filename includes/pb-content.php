<?php
/**
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Content;

/**
 * Add numbers to headlines in content
 * @param $content
 *
 */

function headline_add_numbers( $content) {
    global $id;
    $post = get_post($id);
    $cn = pb_get_chapter_number($post->post_name);
    if($cn){
        $cna = array();
        $cna[0] = $cn;
        $cna[1] = 0;
        $cna[2] = 0;
        $cna[3] = 0;
        $cna[4] = 0;
        $cna[5] = 0;
        $cna[6] = 0;
        $html = new \DOMDocument();
        $html->loadHTML($content);
        $xpath = new \DOMXpath($html);
        foreach( $xpath->query('/html/body/*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]') as $node ) {
            $nn = $node->nodeName;
            $nn = substr($nn, 1);
            $nn = intval($nn);

            $cna[$nn] ++;
            for($i = $nn+1; $i <7; $i++){
                $cna[$i] = 0;
            }


            /**
             * Filter the headline numbering.
             *
             * <code>
             * function pressbooks_theme_headline_add_numbers( $content, $cna){
             *   return(implode("-",$cna)." --- ".$content);
             * }
             * add_filter('pb_headline_add_numbers', 'pressbooks_theme_headline_add_numbers', 10, 2);
             * </code>
             *
             * @param string $content Content of the current headline.
             * @param array $headlineCount Array with Numbers representing the current headline. 1.2.3 h2 has an array $hc[0]=1, $nc[1]=2, $nc[2]=3
             *
             */
            $v = apply_filters("pb_headline_add_numbers", $node->nodeValue, array_slice($cna, 0, $nn-6));
            if($v == $node->nodeValue){
                $node->nodeValue = implode(".",array_slice($cna, 0, $nn-6))." ".$node->nodeValue;
            }else{
                $node->nodeValue = $v;
            }

        }
        $content = $html->saveHTML();
    }
    return $content;
}
