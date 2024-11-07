<?php 

namespace App\Repositories;

use App\Models\BookingTransaction;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Support\Facades\Session;

class BookingRepository implements BookingRepositoryInterface
{
    public function createBooking(array $data)
    {
        return BookingTransaction::create($data);
    }

    public function findByTrxIdAndPhoneNumber($bookingTrxId, $phoneNumber)
    {
        return BookingTransaction::where('booking_trx_id', $bookingTrxId)
                                ->where('phone_number', $phoneNumber)
                                ->first();
    }

    public function saveToSession(array $data)
    {
        Session::put('orderData', $data);
    }

    public function getOrderDataFromSession()
    {
        return Session::get('orderData', []);
    }

    public function updateSessionData(array $data)
    {
        $orderData = Session::get('orderData', []);
        $orderData = array_merge($orderData, $data);
        Session:(['orderData', $orderData]); // Perbaikan di sini
    }

    public function clearSession()
    {
        Session::forget('orderData');
    }
}
