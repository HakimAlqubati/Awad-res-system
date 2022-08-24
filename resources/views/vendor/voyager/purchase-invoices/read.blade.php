@extends('voyager::master')

@section('page_title', __('voyager::generic.view') . ' ' . $dataType->getTranslatedAttribute('display_name_singular'))

@section('page_header')
    <h1 class="page-title">
        <i class="{{ $dataType->icon }}"></i> {{ __('voyager::generic.viewing') }}
        {{ ucfirst($dataType->getTranslatedAttribute('display_name_singular')) }} &nbsp;



        @can('browse', $dataTypeContent)
            <a href="{{ route('voyager.' . $dataType->slug . '.index') }}" class="btn btn-warning">
                <i class="glyphicon glyphicon-list"></i> <span
                    class="hidden-xs hidden-sm">{{ __('voyager::generic.return_to_list') }}</span>
            </a>
        @endcan
    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')
    <div class="page-content read container-fluid">
        <div class="row">
            <div class="col-md-12">

                <div class="panel panel-bordered" style="padding-bottom:5px;">
                    <div class="col-md-12">
                        <div class="col-md-3">
                            <p>
                                Purchase no: #{{ $purchaseInvoice->invoice_no }}
                            </p>
                        </div>

                        <div class="col-md-3">
                            <p>
                                Date: {{ $purchaseInvoice->date }}
                            </p>
                        </div>

                        <div class="col-md-3">
                            <p>
                                Supplier: {{ \App\Models\User::find($purchaseInvoice->supplier_id)->name }}
                            </p>
                        </div>

                        <div class="col-md-3">
                            <p>
                                Supplier: {{ \App\Models\Stock::find($purchaseInvoice->stock_id)->name }}
                            </p>
                        </div>

                    </div>

                    <div class="col-md-12">
                        <p>

                            Notes: {{ $purchaseInvoice->notes }}
                        </p>
                    </div>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Product id</th>
                                <th scope="col">Product</th>
                                <th scope="col">Unit</th>
                                <th scope="col">Price</th>
                                <th scope="col">Quantity</th>
                                <th scope="col">Total price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $total = 0; @endphp
                            @foreach ($purchaseInvoiceDetails as $item)
                                <tr>
                                    <td scope="row">{{ $loop->iteration }}</td>
                                    <td>{{ $item->product_id }}</td>
                                    <td>{{ \App\Models\Product::find($item->product_id)->name }}</td>
                                    <td>{{  \App\Models\Unit::find($item->unit_id)->name  }}</td>
                                    <td>{{ $item->price }}</td>
                                    <td>{{ $item->qty }}</td>
                                    <td>{{ $item->total_price }}</td>
                                    @php $total += $item->total_price  @endphp
                                </tr>
                            @endforeach

                            <tr>
                                <td colspan="6" style="text-align: center;"> Total</td>
                                <td>{{ $total }}</td>
                            </tr>

                        </tbody>
                    </table>


                </div>
            </div>
        </div>
    </div>

@stop

@section('javascript')
    @if ($isModelTranslatable)
        <script>
            $(document).ready(function() {
                $('.side-body').multilingual();
            });
        </script>
    @endif
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
