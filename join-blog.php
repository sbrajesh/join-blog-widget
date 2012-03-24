<?php
/**
 * Plugin Name: Join Blog Widget
 * Version:1.0
 * Plugin URI:http://buddydev.com
 * Author: Brajesh Singh
 * Author URI: http://buddydev.com
 * Description: Allow your users to join a sub blog on a multisite blog network. You can select the roles for the user/message to be shown.
 * License: GPL
 * 
 */
/**
 * Special Note: I created this plugin to be used with My other plugin BuddyPress Multi Network(http://buddydev.com/plugins/buddypress-multi-network/).
 * It will enable sideadmins to show a widget and allow users to join their nework.
 * You can use it for other pourposes as you want.
 */

class BPDevJoinBlogWidget extends WP_Widget{
    
    function __construct($id_base = false, $name=false, $widget_options = array(), $control_options = array()) {
       if(!$name)
           $name=__('Join Blog Widget');
        parent::__construct($id_base, $name, $widget_options, $control_options);
        //I know I am burdening the widget to handle ajax request, May be a bad standart of coding but suits much better in this situation where data varies per widget
        //hopefully, I will put it in helper in next release and use global $wp_registered_widgets
        add_action('wp_ajax_join_blog',array($this,'add_user'));
    }
    
    function widget($args, $instance) {
        
        $user_id=get_current_user_id();
        $blog_id=get_current_blog_id();
        
        //if the user is not logged in or the user is already a member of the blog, do not show this widget
        if(empty($user_id)||  is_user_member_of_blog($user_id,$blog_id))
                return false;
        
        extract($args);
        //display the widget with button to ask joining
         echo $before_widget;
         if(!empty($instance['title']))
             echo $before_title.$instance['title'].$after_title;
             echo "<a data-id='".$this->id."' class='bpdev-join-blog' href='".get_blogaddress_by_id($blog_id)."?action=join-blog&_wpnonce=".wp_create_nonce('join-blog-'.$this->id)."'>".$instance['button_text']."</a>";
         echo $after_widget;
    
    }
    
    function update($new_instance, $old_instance) {
       
        $instance=$old_instance;
        $instance['title']=$new_instance['title'];
        $instance['button_text']=$new_instance['button_text'];
        $instance['message_success']=$new_instance['message_success'];
        $instance['message_error']=$new_instance['message_error'];
        $instance['role']=$new_instance['role'];
        return $instance;
    }
    
    function form($instance) {
        $default=array('title'=>'Join Blog ','role'=>'subscriber','button_text'=>__('Join this Blog'),'message_success'=>__('You have successfully joined this blog'),'message_error'=>__('There was a problem joining this blog. Please try again later'));
        $args=wp_parse_args((array)$instance,$default);
        extract($args);
        ?>
        <p>
            <label for="<?php $this->get_field_id('title') ;?>"><?php _e('Title');?>
                <input type="text" id="<?php $this->get_field_id('title');?>" name="<?php echo $this->get_field_name('title');?>" value="<?php echo $title;?>" />
            </label>
        </p>
        <p>
            <label for="<?php $this->get_field_id('role') ;?>"><?php _e('Role');?>
               <?php $this->print_role_dd($role);?>
            </label>
        </p>
        <p>
            <label for="<?php $this->get_field_id('button_text') ;?>"><?php _e('Join Button Label');?>
                <input type="text" id="<?php $this->get_field_id('button_text');?>" name="<?php echo $this->get_field_name('button_text');?>" value="<?php echo $button_text;?>"/>
            </label>
        </p>
        <p>
            <label for="<?php $this->get_field_id('message_success') ;?>"> <?php _e('Message on Successful Joining');?>
                <textarea  id="<?php $this->get_field_id('message_success');?>" name="<?php echo $this->get_field_name('message_success');?>" ><?php echo $message_success;?></textarea>
            </label>
        </p>
        <p>
            <label for="<?php $this->get_field_id('message_error') ;?>"> <?php _e('Error Message');?>
                <textarea id="<?php $this->get_field_id('message_error');?>" name="<?php echo $this->get_field_name('message_error');?>"><?php echo $message_error;?></textarea>
            </label>
        </p>
        
        
    <?php        }
    
   //ajax work
   function add_user(){
       //nonce check ?
        $user_id=get_current_user_id();
        $blog_id=get_current_blog_id();
        
        $option=get_option($this->option_name);
        $id=$_POST['widget-id'];//get the widget which sent this request
        
        if(!wp_verify_nonce($_POST['_wpnonce'], 'join-blog-'.$id));
         //find the numeric id from this id
        $numeric_id=str_replace($this->id_base."-", '', $id);//remove base id to find the numeric id
        $numeric_id=absint($numeric_id);
        
        $current_widget_option=$option[$numeric_id];//get the options for current widget
        $role=$current_widget_option['role'];
        
    //if the user is not logged in or the user is already a member of the blog, do not show this widget
    if(empty($user_id)||  is_user_member_of_blog($user_id,$blog_id))
            return false;
        
        if(add_user_to_blog($blog_id, $user_id, $role))
            echo $current_widget_option['message_success'];
        else
            echo $current_widget_option['message_error'];
        die();
    
   } 
   //helper
   
   function print_role_dd($selected='subscriber'){
      
       ?>
       <select name="<?php echo $this->get_field_name('role');?>" id="<?php echo $this->get_field_id('role');?>">
            <?php  wp_dropdown_roles($selected); ?>
       </select>
  <?php
  }
  
}

add_action( 'widgets_init', create_function( '', 'register_widget( "BPDevJoinBlogWidget" );' ) );

class BPDevJoinBlogHelper{
    
    private static  $instance;
    
    private function __construct() {
        
        add_action('wp_head',array($this,'ajax_url'));
        add_action('wp_enqueue_scripts',array($this,'load_js'));
    }

 
  public static function get_instance(){
        if(!isset (self::$instance))
                self::$instance=new self();
        
        return self::$instance;
    }
   
    function load_js(){
     
        $plugin_path=plugin_dir_url(__FILE__);
        $plugin_js_path=$plugin_path."_inc/join-blog.js";
        //echo $plugin_js_path;
        wp_enqueue_script('join-blog', $plugin_js_path, array('jquery'),2000);
       // wp_enqueue_script('join-blog');
    }
    //bp creates ajaxurl, no need to define ajaxurl then
    function ajax_url(){
        if(function_exists('bp_is_active'))
            return;
        ?>
        <script type="text/javascript">
            var ajaxurl="<?php echo admin_url('admin-ajax.php');?>";
        </script>
                
        <?php
    }
}
BPDevJoinBlogHelper::get_instance();

?>