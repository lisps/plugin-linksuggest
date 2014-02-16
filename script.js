/* DOKUWIKI:include_once vendor/jquery.textcomplete.js */

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
                        var res = item.id;
                        if(data.linktype === 'absolute') 
                            res = ':' + res;
                        if(item.type === 'd')
                            res = res + ':';
                        
                        return res;
                    }));
                }
            );
        },
        template:function(value){
            var image = '';
            
            if(value.slice(-1) === ':'){ //namespace
                image = 'ns.png';
            } else { //file
                image = 'fileicons/file.png';
            }
            return '<img src="'+DOKU_BASE+'lib/images/'+image+'"> '+value;
        },
        index: 1,
        replace: function (element) {
            if(element.slice(-1) === ':'){ //namespace
                return '[[' + jQuery.text(element).html();
            } else { //file
                return ['[[' + element + '|',']]'];
            }
            
        },
        cache:true
    });
});
