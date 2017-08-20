# dco_metafields
Helper functions for building complex WordPress meta field interfaces

Here's how it works:
1. Create a new `MetaGroup($name, $post_type);`
1. Create a new `DCoMetaGroup($name, $post_type);`
2. The object array will be made from the format of `$slug_metafields`. So you would create something like `add_filter('$slug_metafields', 'function_name');` And then also add a `function_name( $meta_array) { and add your objects here. }` 
3. Uhhhh THAT'S IT, BOYO!

Here is what a MetaObject looks like being added to an array called $meta_array:

`$meta_array['meta_item_key']    = new MetaObject( 'meta_item_key' , 'type' , 'Title' , 'description', $defaults);`

`$meta_array['meta_item_key']    = new DCoMetaObject( 'meta_item_key' , 'type' , 'Title' , 'description', $defaults);`


So, here is some very example code:

    add_action('init', 'dco_add_metafields_for_custom_post_type', 10);
    function dco_add_metafields_for_custom_post_type(){
       $metagroup = new DCoMetaGroup('Extra Fields', 'Custom Post Type');
    }
    
    apply_filters('extra_fields_metafields', 'dco_define_metafields_for_extra_fields', 10, 1);
    function dco_define_metafields_for_extra_fields( $fields ){
        $fields['meta_item_key'] = new DCoMetaObject( 'meta_item_key' , 'type' , 'Title' , 'description', $defaults);
        return $fields;
    }


