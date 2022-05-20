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
    
    $dataPoints4 = $finalDataFordChart;
    
    ?>
    <div class="page-content read container-fluid">
        <div class="row">
            <div class="col-md-12">

                <div class="panel panel-bordered" style="padding-bottom:5px;">
                    <div id="chartContainer" style="height: 370px; width: 100%;"></div>
                </div> <!-- First chart -->


                <div class="panel panel-bordered" style="padding-bottom:5px;">
                    <form 
                    data-action="{{ url('/') . '/admin/dashboard' }} }}"
                    {{-- action="{{ url('/') . '/admin/dashboard' }}" --}}
                    >
                        <select name="month" class="form-control" id="search-month">
                            <option value="May">May</option>
                            <option value="April">April</option>
                            <option value="March">March</option>
                            <option value="February">February</option>
                            <option value="January">January</option>
                        </select>
                        <input type="submit" class="btn btn-primary form-control" value="Search">
                    </form>
                    <div id="chartContainer2" style="height: 370px; width: 100%;"></div>
                </div> <!-- Second chart -->


                <div class="panel panel-bordered" style="padding-bottom:5px;">
                    <div id="chartContainer3" style="height: 370px; width: 100%;"></div>

                </div> <!-- Third chart -->


                <div class="panel panel-bordered" style="padding-bottom:5px;">
                    <div id="chartContainer4" style="height: 370px; width: 100%;"></div>

                </div> <!-- Ford chart -->



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
                                    <img width="100px" src="{{ url('/') }}/fast-delivery.png" />

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
                    text: "Top 10 ordered items"
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
                    text: "Most Ten Product Ordered in " + <?php echo json_encode($month); ?>
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
                    text: "Total number of stock orders"
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



            var chart4 = new CanvasJS.Chart("chartContainer4", {
                animationEnabled: true,
                title: {
                    text: "Stock ordering expenses"
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
                    dataPoints: <?php echo json_encode($dataPoints4, JSON_NUMERIC_CHECK); ?>
                }]
            });
            chart4.render(); // ford chart



        }
    </script>


    <script>
        $(document).ready(function() {

           
            var form = '#search-month';

            $(form).on('submit', function(event) {
                alert('ddd')
                event.preventDefault();

                var url = $(this).attr('data-action');

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: new FormData(this),
                    dataType: 'JSON',
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function(response) {
                        $(form).trigger("reset");
                        alert(response.success)
                    },
                    error: function(response) {}
                });
            });

        });
    </script>


@stop
