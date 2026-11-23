<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| One axis for the order and one for its payment
|--------------------------------------------------------------------------
|
| The order used to carry two lifecycles: the one of "OrderStatus" and the one
| of "FulfillmentStatus". Both named the same physical facts, so they could
| disagree with each other, and the fulfillment one was never written: the
| column stayed in "unfulfilled" for the whole life of the order.
|
| This migration folds the physical half into "OrderStatus" and leaves the
| payment on an axis of its own, which is the only one that can really move at
| a different pace: an order can be delivered and still be unpaid.
|
| The history of the order keeps one row per axis, told apart by its "axis"
| column, so the change of the payment keeps its trace.
|
*/

return new class extends Migration
{
    public function up(): void
    {
        $this->foldFulfillmentIntoTheStatus();

        Schema::table('sivarcart_orders', function (Blueprint $table): void {
            $table->dropColumn('fulfillment_status');
        });

        Schema::table('sivarcart_order_status_histories', function (Blueprint $table): void {
            $table->string('axis', 16)->default('status');
            $table->string('payment_status', 32)->nullable();
            $table->string('previous_payment_status', 32)->nullable();
            $table->string('status', 32)->nullable()->change();
        });

        $this->foldPaidRowsIntoThePaymentAxis();
    }

    /**
     * The "down" of this migration cannot rebuild the data it moved: the
     * fulfillment of an order cannot be told apart from the status of an order
     * once both live in the same column. The orders come back with the
     * fulfillment they had, and the rows of the payment come back as rows of
     * the status.
     */
    public function down(): void
    {
        DB::table('sivarcart_order_status_histories')
            ->whereNull('status')
            ->update(['status' => 'pending']);

        Schema::table('sivarcart_order_status_histories', function (Blueprint $table): void {
            $table->dropColumn(['axis', 'payment_status', 'previous_payment_status']);
            $table->string('status', 32)->nullable(false)->change();
        });

        Schema::table('sivarcart_orders', function (Blueprint $table): void {
            $table->string('fulfillment_status', 32)->default('unfulfilled');
        });
    }

    /**
     * Move the fulfillment of the order into its status.
     *
     * A paid order that was not prepared yet is a "pending" order whose
     * payment is paid, so "paid" becomes "pending". An order that was already
     * shipped, delivered or returned takes the state its fulfillment said,
     * because that is the fact that happened.
     */
    private function foldFulfillmentIntoTheStatus(): void
    {
        DB::table('sivarcart_orders')
            ->where('status', 'paid')
            ->update(['status' => 'pending']);

        DB::table('sivarcart_orders')
            ->whereIn('status', ['pending', 'paid'])
            ->whereIn('fulfillment_status', ['processing', 'shipped', 'delivered', 'returned'])
            ->update(['status' => DB::raw('fulfillment_status')]);
    }

    /**
     * Move the rows of the paid status into the axis of the payment.
     *
     * "paid" is not a status of the order anymore, so a row that says it would
     * break the cast of the enum. The row keeps its trace on the axis that
     * still knows about the payment.
     */
    private function foldPaidRowsIntoThePaymentAxis(): void
    {
        DB::table('sivarcart_order_status_histories')
            ->where('status', 'paid')
            ->update([
                'axis' => 'payment',
                'status' => null,
                'payment_status' => 'paid',
                'previous_payment_status' => DB::raw(
                    "case when previous_status = 'pending' then 'unpaid' else previous_status end",
                ),
                'previous_status' => null,
            ]);

        DB::table('sivarcart_order_status_histories')
            ->where('previous_status', 'paid')
            ->update(['previous_status' => 'pending']);
    }
};
