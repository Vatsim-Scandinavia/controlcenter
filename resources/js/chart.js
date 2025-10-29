/**
 * Imports
*/

import Chart from 'chart.js/auto';
import * as helpers from 'chart.js/helpers';
import 'chartjs-adapter-moment';
import autocolors from 'chartjs-plugin-autocolors';


/**
 * Insert global variables
*/

Chart.helpers = helpers;
Chart.register(autocolors);
window.Chart = Chart;
