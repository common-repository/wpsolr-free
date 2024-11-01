=== WPSolr free Search & Related posts - Weaviate AI, Vespa.ai, Elasticsearch, OpenSearch, Solr ===
Contributors: wpsolr
Author: wpsolr
Current Version: 24.0
Author URI: https://www.wpsolr.com/
Tags: search, ai search, elasticsearch, related posts, similar posts
Requires at least: 6.0
Tested up to: 6.6.1
Stable tag: 24.0
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

100% free. World-class Weaviate AI, Vespa AI, Elasticsearch, OpenSearch, Solr.

== Description ==

The ultimate out-of-the-box Wordpress search plugin.

= Simple =
Our 60 seconds wizard will configure 100% of your WordPress search with:
- Ajax search & autocompletion
- Facets (filters)
- Related posts.

= Compatible with world-class self-hosted search engines =
- [WordPress search for Weaviate AI](https://www.wpsolr.com/how-to-easily-configure-weaviate-on-wordpress/?utm_source=wordpress.org&utm_campaign=wpsolr_free)
- [WordPress search for Vespa.ai (soon)](https://www.wpsolr.com/how-to-easily-configure-vespa-ai-search-on-wordpress/?utm_source=wordpress.org&utm_campaign=wpsolr_free)
- [WordPress search for OpenSearch](https://www.wpsolr.com/how-to-easily-configure-opensearch-on-wordpress/?utm_source=wordpress.org&utm_campaign=wpsolr_free)
- [WordPress search for Apache Solr](https://www.wpsolr.com/how-to-easily-configure-solr-on-wordpress/?utm_source=wordpress.org&utm_campaign=wpsolr_free)
- [WordPress search for Elasticsearch](https://www.wpsolr.com/how-to-easily-configure-elasticsearch-on-wordpress/?utm_source=wordpress.org&utm_campaign=wpsolr_free)

= Need even more features? =
Consider our flagship plugin [WPSolr Enterprise](https://www.wpsolr.com/?utm_source=wordpress.org&utm_campaign=wpsolr_free)
- Advanced filters
- WooCommerce
- WPML
- Flatsome
- MyListing
- Jobify
- Listify
- AI search personalization
- AI recommendations
- Paid hosting for search engines like Opensolr, Algolia, Elastic, Google Retail
- And much, much more…

= Download WPSolr Enterprise =
You can [download](https://www.wpsolr.com/download/?utm_source=wordpress.org&utm_campaign=wpsolr_free) and install it on your staging environments. No signup. No registration key. No email asked. Just a download.

== Changelog ==

= 23.9 =
* (new) 60 seconds configuration wizard

= 23.8
* (new) Related posts with Weaviate: retrieve semantically similar posts, with extra filters.
* (new) Related posts with Elasticsearch: retrieve text similar posts (More Like This), with extra filters.
* (new) Related posts with OpenSearch: retrieve text similar posts (More Like This), with extra filters.
* (new) Related posts with Solr: retrieve text similar posts (More Like This), with extra filters.
* (fix) Fix missing $ajax_delay_ms initialization

= 23.7 =
* (new) Add settings to use any jQuery-Autocomplete option with suggestions
* (new) Add post excerpt to boosts
* (new) Index taxonomy’s featured image url for helping catalog discovery in external tools like Algolia

= 23.6 =
* (new) Index featured image url for helping catalog discovery in external tools
* (fix) real-time indexing not working on creation
* (fix) SQL full-text search should not be executed
* (fix) Random sort with Elasticsearch
* (fix) Deprecated parse_str()

= 23.5 =
* (Fix) Solr syntax error with facets containing ” and ”
* (Fix) Facets containing “:” are not selected
* (fix) Facets javascript error in backend search when several views

= 23.4 =
* (deprecation) Deprecated Elasticsearch server 7.x version. Requires Elasticsearch server 8.x version
* (php client) Update Elasticsearch PHP client from version 7. to version 8.
* (new) Weaviate GPT4All vectorizer
* (new) Self-signed node certificate setting for docker OpenSearch SSL
* (new) Self-signed node certificate setting for docker Elasticsearch SSL
* (new) Self-signed node certificate setting for docker Apache Solr SSL
* (new) Self-signed node certificate setting for docker Weaviate SSL
* (new) Button to clone index settings
* (fix) Option to switch Solarium client from http to curl
* (fix) Weaviate slider (numeric and dates),and range, facets
* (fix) Weaviate sort on archive taxonomies

= 23.3 =
* Tested with PHP 8.1 and WordPress 6.2.2
* (new) Rerank Weaviate search results with the <a href="https://weaviate.io/developers/weaviate/modules/retriever-vectorizer-modules/reranker-transformers" rel="noopener" target="_blank">local cross-encoder transformers</a>.
* (Fix) <a href="https://www.wpsolr.com/forums/topic/wonky-results-when-terms-have-same-name-but-belong-to-different-parents">Taxonomy archives with duplicate term names</a>.
* (Fix) Weaviate maximum number of facet items
* (Fix) Weaviate alphabetical sort of facet items

= 23.1 =
* Tested with PHP 8.1 and WordPress 6.2.2
* (new) Set horizontal/vertical orientation on views’ facets. For instance, choose horizontal facets on admin search and vertical on front-end search.
* (fix) Boost categories does not work
* (fix) Wrong archive results with duplicated category names
* (fix) Filters are wrongly showing results with partial matching
* (Fix) Fix some “utf-8-middle-byte” errors with mb_substr()

= 23.0 =
* (fix) Tested with PHP8.1
* (fix) Apply <a href="https://weaviate.io/developers/weaviate/configuration/schema-configuration#property-tokenization">property tokenization</a> to Weaviate indices, to prevent tokenization on facets.
* (fix) <a href="https://www.wpsolr.com/forums/topic/error-in-region-field/">OpenSolr credentials error</a>.
