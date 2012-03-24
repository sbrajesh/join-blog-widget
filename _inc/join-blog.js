(function($){
    
   $(document).ready(function(){
      
      $('.bpdev-join-blog').live('click',function(){
         var $link=$(this);
         var $url=$link.attr('href');
         var $nonce=get_var_in_url($url);
         var $id=$(this).attr('data-id');
         
         $.post(ajaxurl,{action:'join_blog',
                         _wpnonce:$nonce,
                         'widget-id':$id,
                     'cookie':encodeURIComponent(document.cookie)
                },function(resp){
            $link.replaceWith(resp);
         });
         return false;//no action for link clicking
      });
      
      
     function get_var_in_url(url,name){
    
        var urla=url.split("?");
        var qvars=urla[1].split("&");//so we hav an arry of name=val,name=val
        for(var i=0;i<qvars.length;i++){
            var qv=qvars[i].split("=");
            if(qv[0]==name)
                return qv[1];
          }
          return '';
        }
   });//end of dom ready
    
})(jQuery);