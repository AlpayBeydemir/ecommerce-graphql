<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use GraphQL\Error\Error;
use Random\RandomException;

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
            $existingPayment = Payment::where('order_id', $order->id)->first();

            if ($existingPayment && $existingPayment->status === 'completed') {
                throw new Error('Payment already processed for this order');
            }

            $payment = $existingPayment ?: new Payment();
            $payment->order_id = $order->id;
            $payment->payment_method = $paymentMethod;
            $payment->amount = $order->total;
            $payment->currency = 'TRY';
            $payment->status = 'pending';

            $gatewayResponse = $this->callFakePaymentGateway($order, $paymentMethod);

            if ($gatewayResponse['success']) {
                $payment->status = 'completed';
                $payment->transaction_id = $gatewayResponse['transaction_id'];
                $payment->paid_at = now();
                $payment->response_data = json_encode($gatewayResponse);

                $order->update(['status' => 'completed']);

            } else {
                $payment->status = 'failed';
                $payment->transaction_id = 'FAILED-' . uniqid();
                $payment->response_data = json_encode($gatewayResponse);

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
     * @throws RandomException
     */
    protected function callFakePaymentGateway(Order $order, string $paymentMethod): array
    {
        $success = random_int(1, 100) <= 90;

        if ($success) {
            return [
                'success' => true,
                'transaction_id' => 'FKG-' . strtoupper(uniqid('', true)),
                'message' => 'Payment processed successfully',
                'gateway' => 'FakeGateway',
                'payment_method' => $paymentMethod,
                'amount' => $order->total,
                'currency' => 'TRY',
                'timestamp' => now()->toIso8601String(),
            ];
        }

        return [
            'success' => false,
            'error_code' => 'INSUFFICIENT_FUNDS',
            'message' => 'Payment declined - Insufficient funds',
            'gateway' => 'FakeGateway',
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
