@extends('voyager::master')

@section('page_title', __('voyager::generic.view') . ' ' . $dataType->getTranslatedAttribute('display_name_singular'))

@section('page_header')


    <h1 class="page-title">
        <i class="{{ $dataType->icon }}"></i> {{ __('voyager::generic.viewing') }}
        {{ ucfirst($dataType->getTranslatedAttribute('display_name_singular')) }} &nbsp;

        @can('edit', $dataTypeContent)
            <a href="{{ route('voyager.' . $dataType->slug . '.edit', $dataTypeContent->getKey()) }}" class="btn btn-info">
                <i class="glyphicon glyphicon-pencil"></i> <span
                    class="hidden-xs hidden-sm">{{ __('voyager::generic.edit') }}</span>
            </a>
        @endcan
        @can('delete', $dataTypeContent)
            @if ($isSoftDeleted)
                <a href="{{ route('voyager.' . $dataType->slug . '.restore', $dataTypeContent->getKey()) }}"
                    title="{{ __('voyager::generic.restore') }}" class="btn btn-default restore"
                    data-id="{{ $dataTypeContent->getKey() }}" id="restore-{{ $dataTypeContent->getKey() }}">
                    <i class="voyager-trash"></i> <span class="hidden-xs hidden-sm">{{ __('voyager::generic.restore') }}</span>
                </a>
            @else
                <a href="javascript:;" title="{{ __('voyager::generic.delete') }}" class="btn btn-danger delete"
                    data-id="{{ $dataTypeContent->getKey() }}" id="delete-{{ $dataTypeContent->getKey() }}">
                    <i class="voyager-trash"></i> <span
                        class="hidden-xs hidden-sm">{{ __('voyager::generic.delete') }}</span>
                </a>
            @endif
        @endcan
        @can('browse', $dataTypeContent)
            <a href="{{ route('voyager.' . $dataType->slug . '.index') }}" class="btn btn-warning">
                <i class="glyphicon glyphicon-list"></i> <span
                    class="hidden-xs hidden-sm">{{ __('voyager::generic.return_to_list') }}</span>
            </a>
        @endcan

        <a href="{{ url('/get-pdf/' . $dataTypeContent->getKey() . '') }}" class="btn btn-success">
            <i class="glyphicon glyphicon-file"></i> <span class="export-to-pdf">Export to pdf</span>
        </a>


        <a href="{{ url('/orders/export/' . $dataTypeContent->getKey() . '') }}" class="btn btn-success">
            <i class="glyphicon glyphicon-file"></i> <span class="export-to-pdf">Export to excel</span>
        </a>
    

    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')

    <div class="page-content read container-fluid">
        <div class="row">
            <div class="col-md-12">

                <div class="panel panel-bordered" id="headerTable" style="padding-bottom:5px;">



                    <div class="container">
                        <div class="row">

                            <div class="col-md-3">
                                <h4> Order ID : #{{ $finalResultOrder[0]->id }} </h4>
                            </div>


                            <div class="col-md-3">
                                <h4> Date : {{ $finalResultOrder[0]->created_at }} </h4>
                            </div>

                            <div class="col-md-3">
                                <h4> Order state : {{ $finalResultOrder[0]->request_state_name }} </h4>
                            </div>
                            <div class="col-md-3">
                                <h4> ({{ $finalResultOrder[0]->restricted_state_name }}) </h4>
                            </div>


                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <h4> Created by : {{ $finalResultOrder[0]->user_name }} </h4>
                            </div>

                            <div class="col-md-4">
                                <h4> Manager of branch : {{ $finalResultOrder[0]->branch_name }} </h4>
                            </div>
                        </div>


                        @if ($finalResultOrder[0]->request_state_id == 5 && !is_null($finalResultOrder[0]->notes))
                            <h4> Missing quantities: </h4>
                            <div class="row">
                                <div class="col-md-12">
                                    <p> {{ $finalResultOrder[0]->notes }} </p>
                                </div>
                            </div>
                        @endif

                    </div>


                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product id</th>
                                <th>Product name</th>
                                <th>Unit</th>
                                <th>Quantity</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $total_price = 0; ?>
                            @foreach ($finalResult as $value)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $value->product_id ? $value->product_id : '' }}</td>
                                    <td>{{ $value->product_name }}</td>
                                    <td>{{ $value->unit_name ? $value->unit_name : '' }}</td>
                                    <td>{{ $value->qty }}</td>
                                    <td>{{ $value->price ? $value->price : '' }}</td>
                                    <?php $value->price ? ($total_price += $value->price) : ''; ?>
                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="5" style="text-align: center">Total price</td>
                                <td><?php echo $total_price; ?></td>
                            </tr>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>

    {{-- Single delete modal --}}
    <div class="modal modal-danger fade" tabindex="-1" id="delete_modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"
                        aria-label="{{ __('voyager::generic.close') }}"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="voyager-trash"></i>
                        {{ __('voyager::generic.delete_question') }}
                        {{ strtolower($dataType->getTranslatedAttribute('display_name_singular')) }}?</h4>
                </div>
                <div class="modal-footer">
                    <form action="{{ route('voyager.' . $dataType->slug . '.index') }}" id="delete_form" method="POST">
                        {{ method_field('DELETE') }}
                        {{ csrf_field() }}
                        <input type="submit" class="btn btn-danger pull-right delete-confirm"
                            value="{{ __('voyager::generic.delete_confirm') }} {{ strtolower($dataType->getTranslatedAttribute('display_name_singular')) }}">
                    </form>
                    <button type="button" class="btn btn-default pull-right"
                        data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
@stop

@section('javascript')
    @if ($isModelTranslatable)
        <script>
            function fnExcelReport() {
                var tab_text = "<table border='2px'><tr bgcolor='#87AFC6'>";
                var textRange;
                var j = 0;
                tab = document.getElementById('headerTable'); // id of table

                for (j = 0; j < tab.rows.length; j++) {
                    tab_text = tab_text + tab.rows[j].innerHTML + "</tr>";
                    //tab_text=tab_text+"</tr>";
                }

                tab_text = tab_text + "</table>";
                tab_text = tab_text.replace(/<A[^>]*>|<\/A>/g, ""); //remove if u want links in your table
                tab_text = tab_text.replace(/<img[^>]*>/gi, ""); // remove if u want images in your table
                tab_text = tab_text.replace(/<input[^>]*>|<\/input>/gi, ""); // reomves input params

                var ua = window.navigator.userAgent;
                var msie = ua.indexOf("MSIE ");

                if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)) // If Internet Explorer
                {
                    txtArea1.document.open("txt/html", "replace");
                    txtArea1.document.write(tab_text);
                    txtArea1.document.close();
                    txtArea1.focus();
                    sa = txtArea1.document.execCommand("SaveAs", true, "Say Thanks to Sumit.xls");
                } else //other browser not tested on IE 11
                    sa = window.open('data:application/vnd.ms-excel,' + encodeURIComponent(tab_text));

                return (sa);
            }

            $(document).ready(function() {
                $('.side-body').multilingual();
            });
        </script>
    @endif
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
