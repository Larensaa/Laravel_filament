<?php

namespace App\Services;

use App\Models\BookingTransaction;
use App\Models\WorkshopParticipant;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\WorkshopRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class BookingService
{
    protected $workshopRepository;
    protected $bookingRepository;

    public function __construct(WorkshopRepositoryInterface $workshopRepository, BookingRepositoryInterface $bookingRepository)
    {
        $this->workshopRepository = $workshopRepository;
        $this->bookingRepository = $bookingRepository;
    }

    public function storeBooking(array $validatedData)
    {
        $existingData = $this->bookingRepository->getOrderDataFromSession();
        $updatedData = array_merge($existingData, $validatedData);

        $this->bookingRepository->saveToSession($updatedData);

        return $updatedData;
    }

    public function isBookingSessionAvailable()
    {
        return $this->bookingRepository->getOrderDataFromSession() !== null;
    }

    public function getBookingDetails()
    {
        $orderData = $this->bookingRepository->getOrderDataFromSession();

        if (empty($orderData)) {
            return null;
        }

        $workshop = $this->workshopRepository->find($orderData['workshop_id']);
        $quantity = isset($orderData['quantity']) ? $orderData['quantity'] : 1;
        $subTotalAmount = $workshop->price * $quantity;

        $taxRate = 0.11;
        $totalTax = $subTotalAmount * $taxRate;
        $totalAmount = $subTotalAmount + $totalTax;

        $orderData['sub_total_amount'] = $subTotalAmount;
        $orderData['total_tax'] = $totalTax;
        $orderData['total_amount'] = $totalAmount;

        $this->bookingRepository->saveToSession($orderData);
        return compact('orderData', 'workshop');
    }

    public function finalizeBookingAndPayment(array $paymentData)
    {
        $orderData = $this->bookingRepository->getOrderDataFromSession();

        if (!$orderData) {
            throw new Exception('Booking data is missing from session.');
        }

        Log::info('Order Data:', $orderData);

        if (!isset($orderData['total_amount'])) {
            throw new Exception('Total amount is missing from the order data.');
        }

        $proofPath = null;
        if (isset($paymentData['proof'])) {
            $proofPath = $paymentData['proof']->store('proofs', 'public');
        }

        DB::beginTransaction();

        try {
            $bookingTransaction = BookingTransaction::create([
                'name' => $orderData['name'],
                'email' => $orderData['email'],
                'phone' => $orderData['phone'],
                'customer_bank_name' => $paymentData['customer_bank_name'],
                'customer_bank_number' => $paymentData['customer_bank_number'],
                'customer_bank_account' => $paymentData['customer_bank_account'],
                'proof' => $proofPath,
                'quantity' => $orderData['quantity'],
                'total_amount' => $orderData['total_amount'],
                'is_paid' => false,
                'workshop_id' => $orderData['workshop_id'],
                'booking_trx_id' => BookingTransaction::generateUniqueTrxId(),
            ]);

            foreach ($orderData['participants'] as $participant) {
                WorkshopParticipant::create([
                    'name' => $participant['name'],
                    'occupation' => $participant['occupation'],
                    'email' => $participant['email'],
                    'workshop_id' => $bookingTransaction->workshop_id,
                    'booking_transaction_id' => $bookingTransaction->id,
                ]);
            }

            DB::commit();

            $this->bookingRepository->clearSession();

            return $bookingTransaction->id;

        } catch (Exception $e) {
            Log::error('Payment processing failed: ' . $e->getMessage());
            DB::rollback();
            throw $e;
        }
    }

    public function getMyBookingDetails(array $validated)
    {
        return $this->bookingRepository->findByTrxIdAndPhoneNumber($validated['booking_trx_id'], $validated['phone']);
    }
}
