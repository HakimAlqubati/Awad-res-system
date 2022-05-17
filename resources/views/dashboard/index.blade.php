@extends('voyager::master')
@section('page_title', 'Dashboard')

@section('page_header')


    <h1 class="page-title">


        Dashboard


    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')

    <?php
    $dataPoints = $finalDataFirstChart;
    
    $dataPoints2 = $finalDataSecondChart;
    
    $dataPoints3 = $finalDataThirdChart;
    
    ?>
    <div class="page-content read container-fluid">
        <div class="row">
            <div class="col-md-12">

                <div class="panel panel-bordered" style="padding-bottom:5px;">
                    <div id="chartContainer" style="height: 370px; width: 100%;"></div>
                </div> <!-- First chart -->


                <div class="panel panel-bordered" style="padding-bottom:5px;">
                    <div id="chartContainer2" style="height: 370px; width: 100%;"></div>
                </div> <!-- Second chart -->


                <div class="panel panel-bordered" style="padding-bottom:5px;">
                    <div id="chartContainer3" style="height: 370px; width: 100%;"></div>

                </div> <!-- Third chart -->



                <div class="panel panel-bordered">
                    <div class="container">
                        <div class="col-md-12" style="text-align: center;padding: 30px 0px 30px 0px;">





                            {{-- Start total orders --}}
                            <div class="col-md-12"
                                style="padding-top: 32px; color: black; border: 2px solid;
                                                                                                                                                                                                                                                                            padding-right: 0px;
                                                                                                                                                                                                                                                                            padding-left: 0px;
                                                                                                                                                                                                                                                                            border-radius: 45px;">
                                <p style="font-weight: bold"> Total orders </p>
                                <div>
                                    <img width="100px"
                                        src="{{ url('/') }}/fast-delivery.png" />
                                    
                                    <a href={{ url('/') . '/admin/orders' }}>
                                        <p style="font-weight: bold"> {{ $ordersCount }} Order </p>
                                    </a>
                                </div>
                            </div>




                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

@stop

@section('javascript')

    <script src="{{ asset('js/app.js') }}" type="text/js"></script>
    <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>


    <script>
        var deleteFormAction;
        $('.delete').on('click', function(e) {
            var form = $('#delete_form')[0];

            if (!deleteFormAction) {
                // Save form action initial value
                deleteFormAction = form.action;
            }

            form.action = deleteFormAction.match(/\/[0-9]+$/) ?
                deleteFormAction.replace(/([0-9]+$)/, $(this).data('id')) :
                deleteFormAction + '/' + $(this).data('id');

            $('#delete_modal').modal('show');
        });
    </script>


    <script>
        window.onload = function() {

            var chart = new CanvasJS.Chart("chartContainer", {
                animationEnabled: true,
                exportEnabled: true,
                title: {
                    text: "The Most Ten Product Ordered "
                },
                subtitles: [{
                    text: "that are favorite products by our customers"
                }],
                data: [{
                    type: "pie",
                    showInLegend: "true",
                    legendText: "{label}",
                    indexLabelFontSize: 16,
                    indexLabel: "{label} - #percent%",
                    yValueFormatString: "#,##0",
                    dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>
                }]
            });
            chart.render(); // first chart


            var chart2 = new CanvasJS.Chart("chartContainer2", {
                animationEnabled: true,
                theme: "light2", // "light1", "light2", "dark1", "dark2"
                title: {
                    text: "Most Ten Product Ordered in Last Month"
                },
                axisY: {
                    title: "Number of Most Orders"
                },
                data: [{
                    type: "column",
                    dataPoints: <?php echo json_encode($dataPoints2, JSON_NUMERIC_CHECK); ?>
                }]
            });
            chart2.render(); // second chart 




            var chart3 = new CanvasJS.Chart("chartContainer3", {
                animationEnabled: true,
                title: {
                    text: "Branches according to the most ordered"
                },
                axisY: {
                    title: "",
                    includeZero: true,

                },
                data: [{
                    type: "bar",
                    yValueFormatString: "#,##0",
                    indexLabel: "{y}",
                    indexLabelPlacement: "inside",
                    indexLabelFontWeight: "bolder",
                    indexLabelFontColor: "white",
                    dataPoints: <?php echo json_encode($dataPoints3, JSON_NUMERIC_CHECK); ?>
                }]
            });
            chart3.render(); // third chart



        }
    </script>



@stop
