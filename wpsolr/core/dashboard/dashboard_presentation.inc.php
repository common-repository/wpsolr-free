<?php

use wpsolr\core\classes\utilities\WPSOLR_Escape;

?>

<br>
<h2>You will need just 6 steps to configure your search with wpsolr</h2>

<ol>
    <li>
        Install your open-source search engine:
        <ul>
			<?php foreach (
				[
					'Apache Solr'       => 'https://solr.apache.org/guide/solr/latest/deployment-guide/solr-in-docker.html',
					'Apache Solr Cloud' => 'https://solr.apache.org/guide/solr/latest/deployment-guide/solr-in-docker.html',
					'Elasticsearch'     => 'https://www.elastic.co/guide/en/elasticsearch/reference/current/run-elasticsearch-locally.html',
					'OpenSearch'        => 'https://opensearch.org/docs/latest/install-and-configure/install-opensearch/docker/',
					'Weaviate AI'       => $license_manager->add_campaign_to_url( 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/configure-your-indexes/create-weaviate-index/' ),
				] as $search_engine_name => $search_engine_url
			) { ?>
                <li>
                    <a href="<?php WPSOLR_Escape::echo_esc_url( $search_engine_url ); ?>"
                       target="<?php WPSOLR_Escape::echo_esc_attr( $search_engine_name ); ?>"><?php WPSOLR_Escape::echo_esc_html( $search_engine_name ); ?>
                    </a>
                </li>
			<?php } ?>
        </ul>


    </li>

    <li>
        In tab <a href="?page=solr_settings&tab=solr_indexes">"0. Define your indexes"</a>, select the indexes
        you want to use
    </li>
    <li>
        In tab <a href="?page=solr_settings&tab=solr_plugins">"1. Activate extensions"</a>, activate the
        extensions you need (WPSOLR PRO)
    </li>
    <li>
        In tab <a href="?page=solr_settings&tab=solr_option">"2. Define your search"</a>, select all the
        features you want for your search
    </li>
    <li>
        Finally, in tab <a href="?page=solr_settings&tab=solr_operations">"3. Send you data"</a>, index
        everything you selected in previous tabs
    </li>
    <li>
        Add the WPSOLR widgets to your side bar: WPSOLR facets and WPSOLR sort
    </li>
</ol>

<br>
<h2><a href="https://www.youtube.com/c/GotosolrFrance/videos" target="_video">Visit our Youtube channel</a></h2>
