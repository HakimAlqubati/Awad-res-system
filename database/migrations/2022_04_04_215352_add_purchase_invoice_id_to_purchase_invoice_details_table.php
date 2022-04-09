<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPurchaseInvoiceIdToPurchaseInvoiceDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_invoice_details', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_invoice_id');
            $table->foreign('purchase_invoice_id')->references('id')->on('purchase_invoices')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_invoice_details', function ($table) {
            $table->dropForeign(['purchase_invoice_id']);
            $table->dropIfExists('purchase_invoice_details');
        });
    }
}
