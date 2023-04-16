/* DOKUWIKI:include_once vendor/jquery.textcomplete.min.js */
function linksuggest_escape(text) {
    return jQuery('<div/>').text(text).html();
}

function charAfterCursor() {
    let editor = jQuery('#wiki__text');
    let position = editor.prop('selectionStart');
    return editor.prop('value').substring(position, position+1);
}

jQuery(function () {
    let $editor = jQuery('#wiki__text');

    $editor.textcomplete([
        { //page search
            match:    /\[{2}([\w\-.:~]*)$/,
            search:   function (term, callback) {
                if ($editor.data('linksuggest_off') === 1) {
                    callback([]);
                    return;
                }
                jQuery.post(
                    DOKU_BASE + 'lib/exe/ajax.php',
                    {
                        call: 'plugin_linksuggest',
                        q:    term,
                        ns:   JSINFO['namespace'],
                        id:   JSINFO['id'],
                    },
                    function (data) {
                        data = JSON.parse(data);
                        callback(jQuery.map(data.data, function (item) {
                            let id = item.id;

                            if (item.type === 'd') {
                                id = id + ':';
                            }

                            return {
                                id:     id,
                                ns:     item.ns,
                                title:  item.title,
                                type:   item.type,
                                rootns: item.rootns
                            };
                        }));
                    }
                );
            },
            template: function (item) { //dropdown list
                let image;
                let title = item.title ? ' (' + linksuggest_escape(item.title) + ')' : '';
                let alt = item.type === 'd' ? 'ns' : 'page';
                let value = item.id;

                if (item.rootns) { //page is in root namespace
                    value = ':' + value;
                }
                if (item.type === 'd') { //namespace
                    image = 'ns.png';
                } else { //file
                    image = 'page.png';
                }
                return '<img alt="' + alt + '" src="' + DOKU_BASE + 'lib/images/' + image + '"> ' + linksuggest_escape(value) + title;
            },
            index:    1,
            replace:  function (item) { //returns what will be put to editor
                let id = item.id;
                if (item.ns) { //prefix with already entered ns
                    id = item.ns  + id;
                }
                if (item.type === 'd') { //namespace
                    setTimeout(function () {
                        $editor.trigger('keyup');
                    }, 200);
                    return '[[' + id;
                } else { //file
                    $editor.data('linksuggest_off', 1);

                    setTimeout(function () {
                        $editor.data('linksuggest_off', 0);
                    }, 500);
                    return ['[[' + id, (item.title && (JSINFO["append_header"] === 1) ? '|' + item.title : '') + (charAfterCursor() === ']'? '' : ']]')];
                }

            },
            cache:  false
        }, { //Page Section Search
            match:    /\[\[([\w\-.:~]+#[\w\-.:]*)$/,
            index:    1,
            search:   function (term, callback) {
                if ($editor.data('linksuggest_off') === 1) {
                    callback([]);
                    return;
                }
                jQuery.post(
                    DOKU_BASE + 'lib/exe/ajax.php',
                    {
                        call: 'plugin_linksuggest',
                        q:    term,
                        ns:   JSINFO['namespace'],
                        id:   JSINFO['id'],
                    },
                    function (data) {
                        data = JSON.parse(data);
                        callback(jQuery.map(data.data, function (item) {
                            return {
                                'link': data.link,
                                'toc': item
                            };
                        }));
                    }
                );
            },
            template: function (item) { //dropdown list
                let toc = item.toc;
                let title = toc.title ? ' (' + linksuggest_escape(toc.title) + ')' : '';

                return linksuggest_escape(toc.hid) + title;
            },

            replace: function (item) { //returns what will be put to editor
                let link = item.link;
                let toc = item.toc;

                $editor.data('linksuggest_off', 1);
                setTimeout(function () {
                    $editor.data('linksuggest_off', 0);
                }, 500);

                 let endChar = charAfterCursor();
                 console.log(endChar);
                 if(endChar === '|' || !toc.title) {
                  return '[[' + link + '#' + toc.hid;
                 }else{
                     if(endChar === ']'){
                         return '[[' + link + '#' + toc.hid + '|' + toc.title;
                     }else{
                         return '[[' + link + '#' + toc.hid + '|' + toc.title + ']]';
                     }
                 }
            },
            cache:   false
        }, { //media search
            match:    /\{{2}([\w\-.:~]*)$/,
            search:   function (term, callback) {
                if ($editor.data('linksuggest_off') === 1) {
                    callback([]);
                    return;

                }
                jQuery.post(
                    DOKU_BASE + 'lib/exe/ajax.php',
                    {
                        call: 'plugin_imglinksuggest',
                        q:    term,
                        ns:   JSINFO['namespace'],
                        id:   JSINFO['id'],
                    },
                    function (data) {
                        data = JSON.parse(data);
                        callback(jQuery.map(data.data, function (item) {
                            let id = item.id;

                            if (item.type === 'd') {
                                id = id + ':';
                            }

                            return {
                                id:     id,
                                ns:     item.ns,
                                type:   item.type,
                                rootns: item.rootns
                            };
                        }));
                    }
                );
            },
            template: function (item) { //dropdown list
                let image;
                let value = item.id;
                let alt = item.type === 'd' ? 'ns' : 'media';

                if (item.rootns) { //page is in root namespace
                    value = ':' + value;
                }
                if (item.type === 'd') { //namespace
                    image = 'ns.png';
                } else { //file
                    image = 'media_link_nolnk.png';
                }
                return '<img alt="' + alt + '" src="' + DOKU_BASE + 'lib/images/' + image + '"> ' + linksuggest_escape(value);
            },
            index:    1,
            replace:  function (item) { //returns what will be put to editor
                let id = item.id;
                if (item.ns) { //prefix with already entered ns
                    id = item.ns + id;
                }
                if (item.type === 'd') { //namespace
                    setTimeout(function () {
                        $editor.trigger('keyup');
                    }, 200);
                    return '{{' + id;
                } else { //file
                    $editor.data('linksuggest_off', 1);

                    setTimeout(function () {
                        $editor.data('linksuggest_off', 0);
                    }, 500);
                    return ['{{' + id, '}}'];
                }

            },
            cache:  false
        }],{
        appendTo: 'body',
        maxCount: 50,
        //header:'test',
        //footer: 'schließen'
    });
});
