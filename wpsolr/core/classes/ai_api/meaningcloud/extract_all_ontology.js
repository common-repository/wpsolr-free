// Script to extract all ontologies from js console on https://www.meaningcloud.com/developer/documentation/ontology
var ontologies = [];
jQuery('.table.table-bordered.table-condensed th:contains(Path)').each(function () {
    var is_table_cntains_th_with_children = (jQuery(this).closest('.table').find('th:contains(Children)').length > 0);
    if (!is_table_cntains_th_with_children) {
        ontologies.push("'" + jQuery(this).next('td').html().replace(/&gt;/g, '>') + "'");
    }
});
console.log(ontologies.join(','));
