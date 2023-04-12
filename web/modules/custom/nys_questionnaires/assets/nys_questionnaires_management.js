!function (document, Drupal, $) {
  'use strict';

  Drupal.behaviors.nysQuestionnairesManagement = {
    attach: function () {
      // Initialize all the highchart instances.
      $('.questionnaire-submission-chart').not('.processed').each(function () {
        let $this = $(this);
        $this.highcharts({
          chart: {type: 'pie', width:350, spacingTop: 10, backgroundColor: "rgba(0,0,0,0)"},
          title: {
            text: $this.data('title') + "<br/>(" + $this.data('submissions') + " submissions)",
            style: {"color": "#333333", "fontSize": "14px", "fontWeight": "bold"},
            margin: 1
          },
          credits: {enabled: false},
          tooltip: {enabled: true},
          legend: {enabled: false},
          plotOptions: {pie: {startAngle: -90, endAngle: 90}},
          series: [{
            name: 'Submissions',
            showInLegend: true,
            enableMouseTracking: false,
            innerSize: "40%",
            dataLabels: {
              enabled: true,
              format: "{point.y} {point.name}",
              distance: 15,
              softConnector: false,
              connectorPadding: 2,
              y: -6
            },
            data: [
              {name: 'In District', y: $this.data('in-district')},
              {name: 'Others', y: $this.data('out-district'), color: "#ccccd8"}
            ]
          }]
        });
        $this.addClass('processed');
      });

      $('.nys-senators-management-dashboard-questionnaires')
          .on('click', '.sponsored-questionnaire, .other-questionnaire', function (e) {
            let $this = $(this),
                $parent = $this.closest('.tab-content'),
                $users_div = $parent.children('.questionnaire-user-list').html('<h3>Loading . . .</h3>')
            ;
            $users_div.load(window.location.href + '/' + $this.data('qid'));
          })
          .on('click', '.tab', function (e) {
            let $this = $(this),
                $parent = $this.closest('.nys-senators-management-dashboard-questionnaires'),
                $target = $parent.children('#' + $this.data('target'))
            ;
            $parent.find('.tab.active,.tab-content.active').removeClass('active');
            $this.addClass('active');
            $target.addClass('active');
          });
    }
  }

}(document, Drupal, jQuery);
