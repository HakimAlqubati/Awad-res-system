@extends('voyager::master')


@section('page_header')


    <h1 class="page-title">




    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')

    <div class="page-content read container-fluid">
        <div class="row">
            <div class="col-md-12">

                <div class="panel panel-bordered" style="padding-bottom:5px;">
                    <table class="table table-striped">
                        <thead>
                            <tr>

                                <th>Order number</th>
                                <th>Date</th>
                                <th>Created by</th>

                            </tr>
                        </thead>
                        <tbody>

                            @foreach ($orders as $item)
                                <tr>

                                    <td><?php echo $item->id ?> </td>
                                    <td> <?php echo $item->created_at ?> </td>
                                    <td> <?php echo $item->created_by ?> </td>
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
