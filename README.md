plugin-linksuggest
==================

Dokuwiki Plugin

ns:ns1:page1
ns:page
page0

User is on ns:page

Possible links to ns:ns1:page1
a -> [[ns:ns1:page1]] (absolute)  //not supported by  this plugin
b -> [[:ns:ns1:page1]] (explizite absolute)
c  ->  [[ns1:page1]] (relative)
d -> [[.:ns1:page1]] (explizite relative)
e -> [[..:ns:ns1:page1]] (relative, with backlink)

