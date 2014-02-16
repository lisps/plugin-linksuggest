/* DOKUWIKI:include_once vendor/jquery.textcomplete.js */
function linksuggest_escape(text){
    return jQuery('<div/>').text(text).html()
}
jQuery(function(){
    jQuery('#wiki__text').textcomplete({
        match: /\[\[([\w:]+)$/,
        search: function (term, callback) {
            jQuery.post(
                DOKU_BASE + 'lib/exe/ajax.php',
                {call:'plugin_linksuggest',
                    q:term,
                    ns:JSINFO['namespace']
                },
                function (data) {
                    console.log(data);
                    data=JSON.parse(data);
                    callback(jQuery.map(data.data,function(item){
                        var id = item.id;
                        if(data.linktype === 'absolute') 
                            id = ':' + id;
                        if(item.type === 'd')
                            id = id + ':';
                        
                        return {id:id,title:item.title};
                    }));
                }
            );
        },
        template:function(value){
            var image = '';
            var title = value.title?' ('+linksuggest_escape(value.title)+')':'';
            value = value.id;
            if(value.slice(-1) === ':'){ //namespace
                image = 'ns.png';
            } else { //file
                image = 'fileicons/file.png';
            }
            return '<img src="'+DOKU_BASE+'lib/images/'+image+'"> '+linksuggest_escape(value) + title;
        },
        index: 1,
        replace: function (element) {
            element = element.id;
            if(element.slice(-1) === ':'){ //namespace
                return '[[' + linksuggest_escape(element);
            } else { //file
                return ['[[' + linksuggest_escape(element) + '|',']]'];
            }
            
        },
        cache:true
    });
});
