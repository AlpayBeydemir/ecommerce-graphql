<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use GraphQL\Error\Error;

class PaymentService
{
    /**
     * Process payment for an order using fake payment gateway
     *
     * @param Order $order
     * @param string $paymentMethod
     * @return Payment
     * @throws Error
     */
    public function processPayment(Order $order, string $paymentMethod): Payment
    {
        if ($order->status === 'completed') {
            throw new Error('Order already completed');
        }

        if ($order->status === 'cancelled') {
            throw new Error('Cannot process payment for cancelled order');
        }

        return DB::transaction(function () use ($order, $paymentMethod) {
            // Check if payment already exists
            $existingPayment = Payment::where('order_id', $order->id)->first();

            if ($existingPayment && $existingPayment->status === 'completed') {
                throw new Error('Payment already processed for this order');
            }

            // Create or update payment record
            $payment = $existingPayment ?: new Payment();
            $payment->order_id = $order->id;
            $payment->payment_method = $paymentMethod;
            $payment->amount = $order->total;
            $payment->currency = 'TRY';
            $payment->status = 'pending';

            // Simulate payment gateway call
            $gatewayResponse = $this->callFakePaymentGateway($order, $paymentMethod);

            if ($gatewayResponse['success']) {
                // Payment successful
                $payment->status = 'completed';
                $payment->paid_at = now();
                $payment->response_data = json_encode($gatewayResponse);

                // Update order status
                $order->update(['status' => 'processing']);

                // TODO: Dispatch job to process order fulfillment
                // dispatch(new ProcessOrderFulfillment($order));

            } else {
                // Payment failed
                $payment->status = 'failed';
                $payment->response_data = json_encode($gatewayResponse);

                // Update order status
                $order->update(['status' => 'failed']);
            }

            $payment->save();

            return $payment->load('order');
        });
    }

    /**
     * Simulate fake payment gateway
     * In production, this would call a real payment provider API
     *
     * @param Order $order
     * @param string $paymentMethod
     * @return array
     */
    protected function callFakePaymentGateway(Order $order, string $paymentMethod): array
    {
        // Simulate 90% success rate
        $success = rand(1, 100) <= 90;

        if ($success) {
            return [
                'success' => true,
                'transaction_id' => 'FKG-' . strtoupper(uniqid()),
                'message' => 'Payment processed successfully',
                'gateway' => 'FakeGateway',
                'payment_method' => $paymentMethod,
                'amount' => $order->total,
                'currency' => 'TRY',
                'timestamp' => now()->toIso8601String(),
            ];
        } else {
            return [
                'success' => false,
                'error_code' => 'INSUFFICIENT_FUNDS',
                'message' => 'Payment declined - Insufficient funds',
                'gateway' => 'FakeGateway',
                'timestamp' => now()->toIso8601String(),
            ];
        }
    }

    /**
     * Refund a payment
     *
     * @param Payment $payment
     * @return Payment
     * @throws Error
     */
    public function refundPayment(Payment $payment): Payment
    {
        if ($payment->status !== 'completed') {
            throw new Error('Cannot refund payment that is not completed');
        }

        return DB::transaction(function () use ($payment) {
            // Simulate refund gateway call
            $gatewayResponse = [
                'success' => true,
                'refund_id' => 'RFD-' . strtoupper(uniqid()),
                'message' => 'Refund processed successfully',
                'amount' => $payment->amount,
                'timestamp' => now()->toIso8601String(),
            ];

            $payment->update([
                'status' => 'refunded',
                'response_data' => json_encode(array_merge(
                    json_decode($payment->response_data, true) ?? [],
                    ['refund' => $gatewayResponse]
                )),
            ]);

            return $payment->fresh();
        });
    }
}
