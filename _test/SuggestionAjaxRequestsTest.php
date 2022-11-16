<?php

/**
 * Principle copied from _test/tests/lib/exe/ajax_requests.test.php and /lib/plugins/indexmenu/_test
 *
 * @group ajax
 * @group plugin_linksuggest
 * @group plugins
 */
class SuggestionAjaxRequestsTest extends DokuWikiTest
{
    public function setUp(): void
    {
        $this->pluginsEnabled[] = 'linksuggest';
        parent::setUp(); // this enables the indexmenu plugin

        //needed for 'tsort' to use First headings, sets title during search, otherwise as fallback page name used.
//        global $conf;
//        $conf['useheading'] = 'navigation';


        saveWikiText('wiki:mpage', "======Cc======\nText", 'page with m in ns');
        saveWikiText('wiki:wikisub:midpage', "======Dd======\nText", 'ns with w in ns');
        saveWikiText('mm', "======Ee======\nText", 'page with m in root');

        //ensures title is added to metadata of page
        idx_addPage('mailinglist');
        idx_addPage('wiki:syntax');
        idx_addPage('wiki:dokuwiki');
        idx_addPage('int:editandsavetest');
        idx_addPage('wiki:mpage');
        idx_addPage('wiki:wikisub:midpage');
        idx_addPage('mm');

        // pages on different levels
        saveWikiText('ns1:ms2:apage', "======Bb======\nPage on level 2", 'Created page on level 2');
        saveWikiText('ns1:ms1:apage', "======Ee======\nPage on level 2", 'Created page on level 2');
        saveWikiText('ns1:ms1:lvl3:lvl4:apage', "======Cc======\nPage on levl 4", 'Page on level 4');
        saveWikiText('ns1:ms1:lvl3:lpage2', "======Ccd======\nPage on levl 3", 'Page on level 3');
        saveWikiText('ns1:ms1:lpage', "======Ccc======\nPage on levl 4", 'Page with l in ms1');
        saveWikiText('ns1:ms1:start', "======Aa======\nPage on level 2", 'Startpage on level 2');
        saveWikiText('ns1:ms0:mpage', "======Aa2======\nPage on level 2", 'Created page on level 2');
        saveWikiText('ns1:apage', "======Dd======\nPage on level 1", 'Created page on level 1');
        saveWikiText('ns1:ms1', "======Gg======\nPage on level 1", 'Created page on level 1');

        //ensures title is added to metadata
        idx_addPage('ns1:ms1:apage');
        idx_addPage('ns1:ms1:lvl3:lvl4:apage');
        idx_addPage('ns1:ms1:lvl3:lpage2');
        idx_addPage('ns1:ms1:lpage');
        idx_addPage('ns1:ms1:start');
        idx_addPage('ns1:ms2:apage');
        idx_addPage('ns1:ms0:bpage');
        idx_addPage('ns1:apage');
        idx_addPage('ns1:ms1');
    }

    /**
     * DataProvider for the builtin Ajax calls
     *
     * @return array
     */
    public function linksuggestPageCalls()
    {
        return [
            // Call, POST parameters, result function
            [
                'plugin_linksuggest',
                ['id'=>'','ns'=>'','q'=>''],
                'expectedResultAllinRoot'
            ],[
                'plugin_linksuggest',
                ['id'=>'','ns'=>'','q'=>'m'],
                'expectedResultPageinRoot'
            ],[
                'plugin_linksuggest',
                ['id'=>'','ns'=>'','q'=>'w'],
                'expectedResultNsinRoot'
            ], [
                'plugin_linksuggest',
                ['id'=>'wiki:syntax','ns'=>'','q'=>''],
                'expectedResultAllinNs'
            ], [
                'plugin_linksuggest',
                ['id'=>'wiki:syntax','ns'=>'','q'=>':'],
                'expectedResultOnlyrootinNs'
            ],[
                'plugin_linksuggest',
                ['id'=>'wiki:syntax','ns'=>'','q'=>':m'],
                'expectedResultOnlyrootminNs'
            ],[
                'plugin_linksuggest',
                ['id'=>'wiki:syntax','ns'=>'','q'=>'m'],
                'expectedResultPageinNs'
            ],[
                'plugin_linksuggest',
                ['id'=>'wiki:syntax','ns'=>'','q'=>'w'],
                'expectedResultrootNsinNs'
            ],[
                'plugin_linksuggest',
                ['id'=>'ns1:ms1','ns'=>'','q'=>'.m'],
                'expectedResultlocalNsinNs2'
            ],[
                'plugin_linksuggest',
                ['id'=>'ns1:ms1','ns'=>'','q'=>'.:m'],
                'expectedResultlocalNsinNs'
            ],[
                'plugin_linksuggest',
                ['id'=>'ns1:ms1:apage','ns'=>'','q'=>'..m'],
                'expectedResultlocalNsinParentNs2'
            ],[
                'plugin_linksuggest',
                ['id'=>'ns1:ms1:apage','ns'=>'','q'=>'..:m'],
                'expectedResultlocalNsinParentNs'
            ],[//TODO also ~ ?
                'plugin_linksuggest',
                ['id'=>'ns1:ms1','ns'=>'','q'=>'~l'],
                'expectedResultlocalNsinRelativetoPage2' //FIXME nothing found..
            ],[
                'plugin_linksuggest',
                ['id'=>'ns1:ms1','ns'=>'','q'=>'~:l'],
                'expectedResultlocalNsinRelativetoPage' //FIXME nothing found..
            ],[
                'plugin_linksuggest',
                ['id'=>'','ns'=>'','q'=>'ns1:ms1:l'],
                'expectedResultMorelvlsNsinRoot'
            ],[
                'plugin_linksuggest',
                ['id'=>'ns1:ms1','ns'=>'','q'=>'.ms1:l'],
                'expectedResultMorelvlsPageinNs'
            ],[
                'plugin_linksuggest',
                ['id'=>'ns1:ms2:apage','ns'=>'','q'=>'..ms1:l'],
                'expectedResultMorelvlslocalPginParentNs'
            ],[
                'plugin_linksuggest',
                ['id'=>'ns1:ms2:apage','ns'=>'','q'=>'..:ms1:l'],
                'expectedResultMorelvlslocalPginParentNs2'
            ],[
                'plugin_linksuggest',
                ['id'=>'ns1:ms1','ns'=>'','q'=>'~lvl3:l'],
                'expectedResultMorelvlslocalNsRelativetoPage'
            ],[
                'plugin_linksuggest',
                ['id'=>'ns1:ms1','ns'=>'','q'=>'.ms1:lvl3:'],
                'expectedResultMorelvlsinCurrentNsNopage'
            ],[
                'plugin_linksuggest',
                ['id'=>'ns1:ms1:lvl3:lvl4:apage','ns'=>'','q'=>'ns1:ms2:..:ms1:lvl3:'],
                'expectedResultRelativeParentinNsNotStart'
            ]
        ];
    }

    /**
     * @dataProvider linksuggestPageCalls
     * @param string $call
     * @param array $post
     * @param string $expectedResult
     */
    public function testPageSuggestions($call, $post, $expectedResult)
    {
        $request = new TestRequest();
        $response = $request->post(['call' => $call] + $post, '/lib/exe/ajax.php');
//        $this->assertNotEquals("AJAX call '$call' unknown!\n", $response->getContent());

//var_export(json_decode($response->getContent()), true); // print as PHP array

        $actualArray = json_decode($response->getContent(), true);

        $this->assertEquals($this->$expectedResult(), $actualArray, "$expectedResult");
    }



    /**
     * at root level '', query: '', return all at root lvl
     */
    public function expectedResultAllinRoot()
    {
        return [
            'data' => [
                0 => [
                    'id' => 'int',
                    'ns' => false,
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 1
                ],
                1 => [
                    'id' => 'ns1',
                    'ns' => false,
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 1
                ],
                2 => [
                    'id' => 'wiki',
                    'ns' => false,
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 1
                ],
                3 => [
                    'id' => 'mailinglist',
                    'ns' => ':',
                    'type' => 'f',
                    'title' => 'Mailing Lists',
                    'rootns' => 1
                ],
                4 => [
                    'id' => 'mm',
                    'ns' => ':',
                    'type' => 'f',
                    'title' => 'Ee',
                    'rootns' => 1
                ]
            ],
            'link' => ''];
    }
    /**
     * at root level '', query: m, return only start with m (pages) at root lvl
     */
    public function expectedResultPageinRoot()
    {
        return [
            'data' => [
                0 => [
                    'id' => 'mailinglist',
                    'ns' => ':',
                    'type' => 'f',
                    'title' => 'Mailing Lists',
                    'rootns' => 1
                ],
                1 => [
                    'id' => 'mm',
                    'ns' => ':',
                    'type' => 'f',
                    'title' => 'Ee',
                    'rootns' => 1
                ]
            ],
            'link' => ''];
    }
    /**
     * at root level '', query: w, return only start with w (namespace) at root lvl
     */
    public function expectedResultNsinRoot()
    {
        return [
            'data' => [
                0 => [
                    'id' => 'wiki',
                    'ns' => false,
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 1
                ]
            ],
            'link' => ''];
    }

    /**
     * at ns level 'wiki:syntax', query: '', results from two level: ns & root
     */
    public function expectedResultAllinNs()
    {
        return [
            'data' => [
                0 => [
                    'id' => 'dokuwiki',
                    'ns' => false,//relative
                    'type' => 'f',
                    'title' => 'DokuWiki',
                    'rootns' => 0
                ],
                1 => [
                    'id' => 'mpage',
                    'ns' => false,//relative
                    'type' => 'f',
                    'title' => 'Cc',
                    'rootns' => 0
                ],
                2 => [
                    'id' => 'syntax',
                    'ns' => false,//relative
                    'type' => 'f',
                    'title' => 'Formatting Syntax',
                    'rootns' => 0
                ],
                3 => [
                    'id' => 'int',
                    'ns' => false,
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 1
                ],
                4 => [
                    'id' => 'ns1',
                    'ns' => false,
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 1
                ],
                5 => [
                    'id' => 'wiki',
                    'ns' => false,
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 1
                ],
                6 => [
                    'id' => 'mailinglist',
                    'ns' => ':',
                    'type' => 'f',
                    'title' => 'Mailing Lists',
                    'rootns' => 1
                ],
                7 => [
                    'id' => 'mm',
                    'ns' => ':',
                    'type' => 'f',
                    'title' => 'Ee',
                    'rootns' => 1
                ]
            ],
            'link' => ''];
    }
    /**
     * at ns level 'wiki:syntax', query: ':' , results from only root
     */
    public function expectedResultOnlyrootinNs()
    {
        return [
            'data' => [
                0 => [
                    'id' => 'int',
                    'ns' => ':',
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 1
                ],
                1 => [
                    'id' => 'ns1',
                    'ns' => ':',
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 1
                ],
                2 => [
                    'id' => 'wiki',
                    'ns' => ':',
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 1
                ],
                3 => [
                    'id' => 'mailinglist',
                    'ns' => ':',
                    'type' => 'f',
                    'title' => 'Mailing Lists',
                    'rootns' => 1
                ],
                4 => [
                    'id' => 'mm',
                    'ns' => ':',
                    'type' => 'f',
                    'title' => 'Ee',
                    'rootns' => 1
                ]
            ],
            'link' => ''];
    }
    /**
     * at ns level 'wiki:syntax', query: ':m' , results from root which start with m
     */
    public function expectedResultOnlyrootminNs()
    {
        return [
            'data' => [
                0 => [
                    'id' => 'mailinglist',
                    'ns' => ':',
                    'type' => 'f',
                    'title' => 'Mailing Lists',
                    'rootns' => 1
                ],
                1 => [
                    'id' => 'mm',
                    'ns' => ':',
                    'type' => 'f',
                    'title' => 'Ee',
                    'rootns' => 1
                ]
            ],
            'link' => ''];
    }
    /**
     * at ns level 'wiki:syntax', query 'm', results from two level: ns & root starting with m
     */
    public function expectedResultPageinNs()
    {
        return [
            'data' => [
                0 => [
                    'id' => 'mpage',
                    'ns' => false,
                    'type' => 'f',
                    'title' => 'Cc',
                    'rootns' => 0
                ],
                1=> [
                    'id' => 'mailinglist',
                    'ns' => ':',
                    'type' => 'f',
                    'title' => 'Mailing Lists',
                    'rootns' => 1
                ],
                2 => [
                    'id' => 'mm',
                    'ns' => ':',
                    'type' => 'f',
                    'title' => 'Ee',
                    'rootns' => 1
                ]
            ],
            'link' => ''];
    }
    /**
     * at ns level 'wiki:syntax', query 'w', results from only root level (for wikisub, .wikisub should be used)
     */
    public function expectedResultrootNsinNs()
    {
        return [
            'data' => [
                0 => [
                    'id' => 'wiki',
                    'ns' => false,
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 1
                ]
            ],
            'link' => ''];
    }
    /**
     * at ns level 'ns1:ms1', query '.m', results from only ns1: starting with m
     */
    public function expectedResultlocalNsinNs2()
    {
        return $this->expectedResultlocalNsinNs(false);
    }
    /**
     * at ns level 'ns1:ms1', query '.:m', results from only ns1: starting with m
     */
    public function expectedResultlocalNsinNs($withSemicolon = true)
    {
        return [
            'data' => [
                0 => [
                    'id' => 'ms0',
                    'ns' => '.' . ($withSemicolon ? ':' : ''),
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 0
                ],
                1 => [
                    'id' => 'ms1',
                    'ns' => '.' . ($withSemicolon ? ':' : ''),
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 0
                ],
                2 => [
                    'id' => 'ms2',
                    'ns' => '.' . ($withSemicolon ? ':' : ''),
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 0
                ],
                3 => [
                    'id' => 'ms1',
                    'ns' => '.' . ($withSemicolon ? ':' : ''),
                    'type' => 'f',
                    'title' => 'Gg',
                    'rootns' => 0
                ]
            ],
            'link' => ''];
    }
    /**
     * at ns level 'ns1:ms1:apage', query '..m', from only parent ns 'ns1:' starting with m
     */
    public function expectedResultlocalNsinParentNs2()
    {
        return $this->expectedResultlocalNsinParentNs(false);
    }
    /**
     * at ns level 'ns1:ms1:apage', query '..:m', from only parent ns 'ns1:' starting with m
     */
    public function expectedResultlocalNsinParentNs($withSemicolon = true)
    {
        return [
            'data' => [
                0 => [
                    'id' => 'ms0',
                    'ns' => '..' . ($withSemicolon ? ':' : ''),
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 0
                ],
                1 => [
                    'id' => 'ms1',
                    'ns' => '..' . ($withSemicolon ? ':' : ''),
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 0
                ],
                2 => [
                    'id' => 'ms2',
                    'ns' => '..' . ($withSemicolon ? ':' : ''),
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 0
                ],
                3 => [
                    'id' => 'ms1',
                    'ns' => '..' . ($withSemicolon ? ':' : ''),
                    'type' => 'f',
                    'title' => 'Gg',
                    'rootns' => 0
                ]
            ],
            'link' => ''];
    }
    /**
     * from page 'ns1:ms1', query '~l', result from namespace equal to current pageid 'ns1:ms1:' starting with l
     */
    public function expectedResultlocalNsinRelativetoPage2()
    {
        return $this->expectedResultlocalNsinRelativetoPage(false);
    }
    /**
     * from page 'ns1:ms1', query '~:l', result from namespace equal to current pageid 'ns1:ms1:' starting with l
     */
    public function expectedResultlocalNsinRelativetoPage($withSemicolon = true)
    {
        return [
            'data' => [
                0 => [
                    'id' => 'lvl3',
                    'ns' => '~' . ($withSemicolon ? ':' : ''),
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 0
                ],
                1 => [
                    'id' => 'lpage',
                    'ns' => '~' . ($withSemicolon ? ':' : ''),
                    'type' => 'f',
                    'title' => 'Ccc',
                    'rootns' => 0
                ]
            ],
            'link' => ''];
    }
    /**
     * at root level '', query: 'ns1:ms1:l', return specific hit in root
     */
    public function expectedResultMorelvlsNsinRoot()
    {
        return [
            'data' => [
                0 => [
                    'id' => 'lvl3',
                    'ns' => 'ns1:ms1:',
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 0
                ],
                1 => [
                    'id' => 'lpage',
                    'ns' => 'ns1:ms1:',
                    'type' => 'f',
                    'title' => 'Ccc',
                    'rootns' => 0
                ]
            ],
            'link' => ''];
    }
    /**
     * at 'ns1:ms1', query: '.ms1:l' , return specific hit in ns 'ns1:ms1:' starting with l
     */
    public function expectedResultMorelvlsPageinNs()
    {
        return [
            'data' => [
                0 => [
                    'id' => 'lvl3',
                    'ns' => '.ms1:',
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 0
                ],
                1 => [
                    'id' => 'lpage',
                    'ns' => '.ms1:',
                    'type' => 'f',
                    'title' => 'Ccc',
                    'rootns' => 0
                ]
            ],
            'link' => ''];
    }
    /**
     * at 'ns1:ms2:apage', query: '..ms1:l' , return specific hit in 'ns1:ms1:' starting with l
     */
    public function expectedResultMorelvlslocalPginParentNs()
    {
        return [
            'data' => [
                0 => [
                    'id' => 'lvl3',
                    'ns' => '..ms1:',
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 0
                ],
                1 => [
                    'id' => 'lpage',
                    'ns' => '..ms1:',
                    'type' => 'f',
                    'title' => 'Ccc',
                    'rootns' => 0
                ]
            ],
            'link' => ''];
    }
    /**
     * at 'ns1:ms2:apage', query: '..:ms1:l' , return specific hit in 'ns1:ms1:' starting with l
     */
    public function expectedResultMorelvlslocalPginParentNs2()
    {
        return [
            'data' => [
                0 => [
                    'id' => 'lvl3',
                    'ns' => '..:ms1:',
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 0
                ],
                1 => [
                    'id' => 'lpage',
                    'ns' => '..:ms1:',
                    'type' => 'f',
                    'title' => 'Ccc',
                    'rootns' => 0
                ]
            ],
            'link' => ''];
    }
    /**
     * at 'ns1:ms1', query: ~lvl3:l, return specific hit 'ns1:ms1:lvl3:' starting with l
     */
    public function expectedResultMorelvlslocalNsRelativetoPage()
    {
        return [
            'data' => [
                0 => [
                    'id' => 'lvl4',
                    'ns' => '~lvl3:',
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 0
                ],
                1 => [
                    'id' => 'lpage2',
                    'ns' => '~lvl3:',
                    'type' => 'f',
                    'title' => 'Ccd',
                    'rootns' => 0
                ]
            ],
            'link' => ''];
    }
    /**
     * in 'ns1:ms1', query: '.ms1:lvl3:', return all in 'ns1:ms1:lvl3:' namespace
     */
    public function expectedResultMorelvlsinCurrentNsNopage()
    {
        return [
            'data' => [
                0 => [
                    'id' => 'lvl4',
                    'ns' => '.ms1:lvl3:',
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 0
                ],
                1 => [
                    'id' => 'lpage2',
                    'ns' => '.ms1:lvl3:',
                    'type' => 'f',
                    'title' => 'Ccd',
                    'rootns' => 0
                ]
            ],
            'link' => ''];
    }
    /**
     * in 'ns1:ms1:lvl3:lvl4:apage', query: ns1:ms2:..:ms1:lvl3:', return all in 'ns1:ms1:lvl3:' namespace
     */
    public function expectedResultRelativeParentinNsNotStart()
    {
        return [
            'data' => [
                0 => [
                    'id' => 'lvl4',
                    'ns' => 'ns1:ms2:..:ms1:lvl3:',
                    'type' => 'd',
                    'title' => '',
                    'rootns' => 0
                ],
                1 => [
                    'id' => 'lpage2',
                    'ns' => 'ns1:ms2:..:ms1:lvl3:',
                    'type' => 'f',
                    'title' => 'Ccd',
                    'rootns' => 0
                ]
            ],
            'link' => ''];
    }
}
