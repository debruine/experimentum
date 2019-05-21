/**
 * Grid theme for Highcharts JS
 * @author Torstein H¿nsi
 */

Highcharts.theme = {
	colors: ['#006699', '#990000', '#CC8800', '#CCCC00', '#008000', '#0000B3', '#660099', '#990066', '#990000', '#994C00', '#CCCC00', '#008000'],
	chart: {
		backgroundColor: 'rgba(255, 255, 255, 0)',
		//borderColor: 'hsla(200, 100%, 30%, 1)',
		borderWidth: 0,
		//plotBackgroundColor: 'rgba(255, 255, 255, 0)',
		shadow: false,
		plotBorderWidth: 0,
		borderRadius: 4,
		style: {
			fontFamily: 'helvetica',
			fontSize: '50%',
		},
	},
	plotOptions: {
        series: {
            marker: {
                enabled: false
            }
        }
    },
	title: {
		style: { 
			color: '#000',
			fontSize: '150%'
		}
	},
	subtitle: {
		style: { 
			color: '#666666',
			fontSize: '125%',
		}
	},
	xAxis: {
		gridLineWidth: 0,
		lineColor: '#000',
		tickColor: '#000',
		labels: {
			style: {
				color: '#000',
				fontSize: '90%'
			}
		},
		title: {
			style: {
				color: '#333',
				fontWeight: 'bold',
				fontSize: '6pt',
			}				
		}
	},
	yAxis: {
		minorTickInterval: 'auto',
		lineColor: '#000',
		lineWidth: 1,
		tickWidth: 1,
		tickColor: '#000',
		startOnTick: true,
		endOnTick: true,
		gridLineWidth: 0,
		minorGridLineWidth: 0,
		labels: {
			style: {
				color: '#000',
				fontSize: '90%',
			}
		},
		title: {
			style: {
				color: '#333',
				fontWeight: 'bold',
				fontSize: '100%',
			}				
		}
	},
	legend: {
		x: 50,
		y: -50,
		borderWidth: 0,
		floating: true,
		verticalAlign: 'top',
		align: 'left',
		layout: 'vertical',
		itemStyle: {			
			fontSize: '100%',
			color: 'black'

		},
		itemHoverStyle: {
			color: '#039'
		},
		itemHiddenStyle: {
			color: 'gray'
		}
	},
	labels: {
		style: {
			color: '#99b'
		}
	},
	credits: {
        enabled: false
    },
};

// Apply the theme
var highchartsOptions = Highcharts.setOptions(Highcharts.theme);
	
