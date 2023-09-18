/**
 * Imports
*/

import * as bootstrap from 'bootstrap'
import moment from 'moment';

/**
 * Insert global variables
*/

window.moment = moment;
window.bootstrap = bootstrap;

/**
 * Sidebar logic
*/

window.addEventListener('load', function(event) {
    var sidebar = document.getElementById('sidebar');
    var sidebarButton = document.getElementById('sidebar-button');
    var sidebarCloseButton = document.getElementById('sidebar-button-close');

    sidebarButton.onclick = function(event){
        event.preventDefault();
        sidebar.classList.toggle('sidebar-show');
        document.body.classList.toggle('fixed-body');
    }

    sidebarCloseButton.onclick = function(event){
        event.preventDefault();
        sidebar.classList.toggle('sidebar-show');
        document.body.classList.toggle('fixed-body');
    }
});