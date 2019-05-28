'use strict';

/**
 * Generate an html interval graph from a data array.
 *
 * EcmaScript 6 version using string literals.
 *
 * @param graph
 * @returns {string}
 */
function intvg(graph) {
    let html = "<div class='intvg'>";
    graph.forEach(function (bar, index) {
        if (bar.length === 6) { // Interval
            html += `<div class='bar bar-intv bar${index} ${bar[2]}' `
                + `style='left: ${bar[0]}%; right: ${bar[1]}%' `
                + `data-title='${bar[3]}&nbsp;➔&nbsp;${bar[4]}${bar[5] != null ? '&nbsp;:' + bar[5] : ''}'></div>`;
        }
        if (bar.length === 2) { // Single date
            html += `<div class='bar bar-date bar${index}' `
                + `style='left: ${bar[0]}%;' data-title='${bar[1]}'></div>`;
        }
    });
    return html;
}

/**
 * Generate an html interval graph from a data array.
 *
 * Boring old EcmaScript 5.1 version.
 *
 * @param graph
 * @returns {string}
 */
function intvgES5(graph) {
    var html = "<div class='intvg'>";
    graph.forEach(function (bar, index) {
        if (bar.length === 6) { // Interval
            html += "<div class='bar bar-intv bar" + index
            + "' style='left: " + bar[0] + "%; right: " + bar[1]
            + "%; background-color: " + bar[2] + "' data-title='" + bar[3] + "&nbsp;➔&nbsp;"
            + bar[4] + (bar[5] != null ? '&nbsp;: ' + bar[5] : '') + "'></div>";
        }
        if (bar.length === 2) { // Single date
            html += "<div class='bar bar-date bar${index}' style='left: "
                + bar[0] + "%;' data-title='" + bar[1] + "'></div>";
        }
    });
    return html;
}