/* DOKUWIKI:include_once vendor/jquery.textcomplete.js */
function linksuggest_escape(text){
    return jQuery('<div/>').text(text).html()
}
jQuery(function(){
    jQuery('#wiki__text').textcomplete({
        match: /\[\[([\w\.:]+)$/,
        search: function (term, callback) {
            jQuery.post(
                DOKU_BASE + 'lib/exe/ajax.php',
                {call:'plugin_linksuggest',
                    q:term,
                    ns:JSINFO['namespace'],
                    id:JSINFO['id'],
                },
                function (data) {
                    //console.log(data);
                    data=JSON.parse(data);
                    callback(jQuery.map(data.data,function(item){
                        var id = item.id;
                        
                        if(item.type === 'd')
                            id = id + ':';
                        
                        return {id:id,ns:item.ns,title:item.title,type:item.type};
                    }));
                }
            );
        },
        template:function(item){
            var image = '';
            var title = item.title?' ('+linksuggest_escape(item.title)+')':'';
            value = item.id;
            if(item.type === 'd'){ //namespace
                image = 'ns.png';
            } else { //file
                image = 'page.png';
            }
            return '<img src="'+DOKU_BASE+'lib/images/'+image+'"> '+linksuggest_escape(value) + title;
        },
        index: 1,
        replace: function (item) {
            var id = item.id;
            if(item.ns === ':'){ //absolute link
                id  = item.ns + id;
            } else if (item.ns) { //relative link
                id = item.ns + ':' + id;
            }
            if(item.type === 'd'){ //namespace
                return '[[' + id;
            } else { //file
                return ['[[' + id + '|',(item.title?item.title:'') + ']]'];
            }
            
        },
        cache:true
    });
});
