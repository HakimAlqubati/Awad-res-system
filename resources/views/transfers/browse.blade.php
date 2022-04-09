@extends('voyager::master')


@section('page_header')


    <h1 class="page-title">




    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')




    <div class="page-content read container-fluid">
        {{-- <form   method="GET">
            From:<input type="date" name="from" value="<?php echo date('Y-m-d'); ?>">
            To:<input type="date" name="to" value="<?php echo date('Y-m-d'); ?>">

            <button type="submit" class="btn btn-success btn-sm">Search</button>
        </form> --}}
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
