@extends('voyager::master')

@section('css')
 
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
            text-align: left !important;
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

        <form class="form-inline form-filter no-print" method="GET" action="<?php echo url('/'); ?>/admin/transfer-list">
            <div class="form-group">
                <label for="status">Transfer no:</label>
                <input type="text"   class="form-control" name="transfer_no" id="transfer_no" />



            </div>

            <div class="form-group">
                <label for="status">Branch:</label>
                <select class="form-control" name="branch_id" id="branch_id">

                    <option value="">-Choose-</option>
                    @foreach (\App\Models\Branch::get() as $item)
                        <option value="{{ $item->id }}"> {{ $item->name }} </option>
                    @endforeach
                </select>
            </div>


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

                <div class="panel panel-bordered" style="padding-bottom:5px;">
                    <table class="table table-striped">
                        <thead>
                            <tr>

                                <th># Transfer id</th>
                                <th>Date</th>
                                <th>Created by</th>
                                <th>Branch</th>
                                <th>Actions</th>



                            </tr>
                        </thead>
                        <tbody>

                            @foreach ($data as $item)
                                <tr>

                                    <td>{{ $item['id'] }} </td>
                                    <td>{{ $item['created_at'] }} </td>
                                    <td>{{ $item['created_by'] }} </td>
                                    <td>{{ $item['branch_id'] }} </td>
                                    <td>

                                        <a class="btn btn-sm btn-warning pull-right view"
                                            href="<?php echo url('/'); ?>/admin/transfer-list/<?php echo $item['id']; ?>">
                                            <i class="voyager-eye"></i>
                                            View
                                        </a>
                                    </td>

                                </tr>
                            @endforeach

                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>

@stop

@section('javascript')

    <script src="{{ asset('js/app.js') }}" type="text/js"></script>


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
@stop
