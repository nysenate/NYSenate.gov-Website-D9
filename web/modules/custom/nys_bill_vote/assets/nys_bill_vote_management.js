!function (document, Drupal, $) {
    'use strict';

    Drupal.behaviors.nysBillVoteManagement = {
        attach: function () {
            // Initialize all the highchart instances.
            $('.sponsored-bill-chart').not('.processed').each(
                function () {
                    let $this = $(this);
                    $this.highcharts(
                        {
                            chart: {
                                type: 'pie',
                                width: '400',
                                height: '300'
                            },
                            title: {
                                text: "User Voting Detail",
                                style: {"color": "#333", "fontSize": "14px", "fontWeight": "bold"}
                            },
                            credits: {enabled: false},
                            tooltip: {enabled: true},
                            legend: {enabled: true},
                            series: [{
                                name: 'Votes',
                                showInLegend: true,
                                enableMouseTracking: false,
                                innerSize: "30%",
                                slicedOffset: 5,
                                dataLabels: {
                                    enabled: true,
                                    format: "{point.y}",
                                    distance: 15,
                                    softConnector: false,
                                    connectorPadding: 2,
                                    y: -6
                                },
                                data: [
                                {name: 'In District Aye', y: $(this).data('in-district-aye'), color: "#319631", borderColor:'#333333'},
                                {name: 'Outside Dist Aye', y: $(this).data('out-district-aye'), color: "#b8ffb8",  borderColor:'#333333'},
                                {name: 'In District Nay', y: $(this).data('in-district-nay'), color: "#d63131", borderColor:'#333333'},
                                {name: 'Outside Dist Nay', y: $(this).data('out-district-nay'), color: "#ffb8b8", borderColor:'#333333'}
                                  ]
                            }]
                        }
                    );
                    $this.addClass('processed');
                }
            );

            $('.nys-senators-management-dashboard-bills')
            .on(
                'click', '.tab', function (e) {
                    let $this = $(this),
                    $parent = $this.closest('.nys-senators-management-dashboard-bills'),
                    $target = $parent.children('#' + $this.data('target'));
                    $parent.find('.tab.active,.tab-content.active').removeClass('active');
                    $this.addClass('active');
                    $target.addClass('active');
                }
            );
        }
    }

}(document, Drupal, jQuery);
