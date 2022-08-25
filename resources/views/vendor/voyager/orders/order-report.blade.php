@extends('voyager::master')

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href={{ url('/') . '/multiselect/css/style_custom.css' }}>

    <style>
        th {
            background-color: #ededd5 !important;
            font-weight: bold;
        }

        @media print {



            .no-print,
            .no-print * {
                display: none !important;
            }


            .app-container.expanded .side-body {
                margin-right: 0px !important;
            }
        }

        p {
            font-weight: bold;
        }
    </style>

@stop

@section('page_header')


    <h1 class="page-title">




    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')

    <div class="page-content read container-fluid">


        <div style="box-shadow: none;border: 1px solid #38393a;
        border-radius: 20px; "class="row">

            <div
                style="box-shadow: none;text-align: right; padding-top: 20px;padding-right: 30px;"class="col-md-3 col-sm-3 col-xs-3">
                <p>Orders report</p>


            </div>

            <div style="box-shadow: none; text-align: center;"class="col-md-6 col-sm-6 col-xs-6">
                <img style="margin-top: 15px;" width="155px" height="155px"
                    src="http://15.185.62.165/Ressystemv2/public/logo.png" alt="">
            </div>
            <div
                style="box-shadow: none; padding-top: 20px;text-align: left;padding-left: 30px;"class="col-md-3 col-sm-3 col-xs-3">

            </div>
        </div>


        <form class="form-inline form-filter no-print" method="GET" action="<?php echo url('/'); ?>/admin/order-report">


            <div class="form-group">
                <div class="dropdown-container" style="max-height: 250px;">
                    <div class="dropdown-button noselect w-100">
                        <div class="dropdown-label">Branch</div>
                        <div class="dropdown-quantity">(<span class="quantity">0</span>)</div>
                        <i class="fa fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-list" style="display: none;">

                        <input type="search" placeholder="البحث..." class="dropdown-search" />
                        <ul id="ul" style="list-style-type: none; max-height: 200px;"></ul>
                    </div>
                </div>

            </div>


            <div class="form-group">
                <div class="dropdown-container dropdown-container-2" style="max-height: 250px;">
                    <div id="dropdown-button-2" class="dropdown-button dropdown-button-2 noselect w-100">
                        <div class="dropdown-label">Products</div>
                        <div class="dropdown-quantity">(<span class="quantity">0</span>)</div>
                        <i class="fa fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-list-2" style="display: none;">

                        <input type="search" placeholder="البحث..." class="dropdown-search-2" />
                        <ul id="ul" class="ul-2" style="list-style-type: none; max-height: 200px;"></ul>
                    </div>
                </div>

            </div>



            {{-- <div class="form-group">
                <label for="status">Branch:</label>
                <select class="form-control" name="branch_id[]" id="branch_id" multiple>

                    <option value="">-Choose-</option>
                    @foreach ($branches as $item)
                        <option value="{{ $item->id }}"> {{ $item->name }} </option>
                    @endforeach
                </select>
            </div> --}}



            {{-- <div class="form-group">
                <label for="status">Product:</label>
                <select class="form-control" name="product_id[]" id="product_id" multiple>

                    <option value="">-Choose-</option>
                    @foreach ($products as $item)
                        <option value="{{ $item->id }}"> {{ $item->name }} </option>
                    @endforeach
                </select>
            </div> --}}


            {{-- <div class="form-group">
                <label for="status">Unit:</label>
                <select class="form-control" name="unit_id" id="unit_id">

                    <option value="">-Choose-</option>
                    @foreach ($units as $item)
                        <option value="{{ $item->id }}"> {{ $item->name }} </option>
                    @endforeach
                </select>
            </div> --}}

            <div class="form-group">
                <label for="status"> From date:</label>
                <input type="date" name="from_date" type="date" class="form-control">

                <label for="status"> To date:</label>
                <input type="date" name="to_date" type="date" class="form-control">

            </div>

            <input class="form-control btn btn-primary" type="submit" value="Search">
        </form>
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <table class="table table-striped">
                        <thead>
                            <tr>

                                <th>Branch</th>
                                {{-- <th>Order state</th> --}}
                                <th>Product</th>
                                <th>Unit</th>
                                <th>Quantity</th>
                                <th>Unit price</th>
                                <th>Total price</th>
                                {{-- <th>Date </th> --}}

                            </tr>
                        </thead>
                        <tbody>

                            @if (count($data) == 0)
                                <tr>
                                    <td colspan="100%" style="text-align: center">
                                        <h5 style="color: red"> No Data !, Or You Have To Choose a Branch</h5>
                                    </td>
                                </tr>
                            @endif
                            <?php
                            $total_price = 0;
                            ?>
                            @foreach ($data as $item)
                                <tr>
                                    <td><?php echo $item->branch_name; ?> </td>
                                    {{-- <td><?php echo $item->state_name; ?> </td> --}}
                                    <td><?php echo $item->product_name; ?> </td>
                                    <td><?php echo $item->unit_name; ?> </td>
                                    <td><?php echo $item->qty; ?> </td>
                                    <td><?php
                                    if ($item->price > 0) {
                                        echo $item->price / $item->qty;
                                    }
                                    
                                    ?> </td>
                                    <td><?php echo $item->price; ?> </td>
                                    {{-- <td><?php echo $item->created_at; ?> </td> --}}
                                </tr>
                                <?php $total_price += $item->price; ?>
                            @endforeach

                            <td colspan="4" style="text-align: center"> Total price</td>
                            <td> {{ $total_price }} </td>
                            <td></td>
                            <tr>

                            </tr>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>

@stop

@section('javascript')

    <script src="{{ asset('js/app.js') }}" type="text/js"></script>
    <script src={{ url('/') . '/multiselect/js/jquery.min.js' }}></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lodash.js/3.5.0/lodash.min.js"></script>

    <script src={{ url('/') . '/multiselect/js/bootstrap.min.js' }}></script>


    <script>
        var deleteFormAction;

        (function($) {

            $(document).on("click", "#dropdown-button-2", function() {
                    $(this).siblings(".dropdown-list-2").toggle();
                })
                .on("input", ".dropdown-search-2", function() {
                    var target = $(this);
                    var dropdownList = target.closest(".dropdown-list-2");
                    var search = target.val().toLowerCase();

                    if (!search) {
                        dropdownList.find("li").show();
                        return false;
                    }

                    dropdownList.find("li").each(function() {
                        var text = $(this).text().toLowerCase();
                        var match = text.indexOf(search) > -1;
                        $(this).toggle(match);
                    });
                })
                .on("change", '[type="checkbox"]', function() {
                    var container = $(this).closest(".dropdown-container-2");
                    var numChecked = container.find('[type="checkbox"]:checked').length;
                    container.find(".quantity").text(numChecked || "Any");
                });


            var products = <?php
            $products = \App\Models\Product::get();
            foreach ($products as $pro_val) {
                $final_product[] = [
                    'name' => $pro_val->name,
                    'abbreviation' => $pro_val->id,
                    'capName' => $pro_val->name,
                ];
            }
            echo json_encode($final_product);
            ?>



            console.log('ddddddddd', products)


            var stateTemplate2 = _.template(



                // "<li>" +
                // '<label class="checkbox-wrap"><input id="branchid" name="branches[]" value="<%= abbreviation %>" type="checkbox"> <span for="<%= abbreviation %>"><%= name %></span> <span class="checkmark"></span></label>' +
                // "</li>"

                "<li>" +
                '<label class="checkbox-wrap"><input id="productid" name="product_id[]" value="<%= abbreviation %>" type="checkbox"> <span for="<%= abbreviation %>"><%= name %></span> <span class="checkmark"></span></label>' +
                "</li>"
            );

            console.log('hakeeem===> ', products)
            // Populate list with states
            _.each(products, function(s) {
                s.capName = _.startCase(s.name.toLowerCase());
                $(".ul-2").append(stateTemplate2(s));
            });


        })(jQuery);



        (function($) {

            $(document)
                .on("click", ".dropdown-button", function() {
                    $(this).siblings(".dropdown-list").toggle();
                })
                .on("input", ".dropdown-search", function() {
                    var target = $(this);
                    var dropdownList = target.closest(".dropdown-list");
                    var search = target.val().toLowerCase();

                    if (!search) {
                        dropdownList.find("li").show();
                        return false;
                    }

                    dropdownList.find("li").each(function() {
                        var text = $(this).text().toLowerCase();
                        var match = text.indexOf(search) > -1;
                        $(this).toggle(match);
                    });
                })
                .on("change", '[type="checkbox"]', function() {
                    var container = $(this).closest(".dropdown-container");
                    var numChecked = container.find('[type="checkbox"]:checked').length;
                    container.find(".quantity").text(numChecked || "Any");
                });

            // JSON of States for demo purposes






            // -----------------



            var branches = <?php
            $branches = \App\Models\Branch::get();
            foreach ($branches as $user_val) {
                $final[] = [
                    'name' => $user_val->name,
                    'abbreviation' => $user_val->id,
                    'capName' => $user_val->name,
                ];
            }
            echo json_encode($final);
            ?>






            var usStates = [{
                    name: "ALABAMA",
                    abbreviation: "AL"
                },
                {
                    name: "ALASKA",
                    abbreviation: "AK"
                },

            ];

            var stateTemplate = _.template(



                // "<li>" +
                // '<label class="checkbox-wrap"><input id="branchid" name="branches[]" value="<%= abbreviation %>" type="checkbox"> <span for="<%= abbreviation %>"><%= name %></span> <span class="checkmark"></span></label>' +
                // "</li>"

                "<li>" +
                '<label class="checkbox-wrap"><input id="branchid" name="branch_id[]" value="<%= abbreviation %>" type="checkbox"> <span for="<%= abbreviation %>"><%= name %></span> <span class="checkmark"></span></label>' +
                "</li>"
            );

            console.log('hakeeem===> ', branches)
            // Populate list with states
            _.each(branches, function(s) {
                s.capName = _.startCase(s.name.toLowerCase());
                $("#ul").append(stateTemplate(s));
            });


        })(jQuery);
    </script>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="https://cdn.rawgit.com/harvesthq/chosen/gh-pages/chosen.jquery.min.js"></script>
    <link href="https://cdn.rawgit.com/harvesthq/chosen/gh-pages/chosen.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/ui/1.13.0/jquery-ui.js"
        integrity="sha256-xH4q8N0pEzrZMaRmd7gQVcTZiFei+HfRTBPJ1OGXC0k=" crossorigin="anonymous"></script>


@stop
