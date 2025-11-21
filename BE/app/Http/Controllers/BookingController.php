<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * 📅 Создать бронирование фотографа
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'photographer_id' => 'required|exists:users,id|different:client_id',
            'start_at' => 'required|date|after_or_equal:now',
            'end_at' => 'required|date|after:start_at',
            'notes' => 'nullable|string|max:2000',
        ]);

        $photographerId = $validated['photographer_id'];
        $clientId = $request->user()->id;

        // Проверка пересечений
        $overlap = Booking::where('photographer_id', $photographerId)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($validated) {
                $q->whereBetween('start_at', [$validated['start_at'], $validated['end_at']])
                  ->orWhereBetween('end_at', [$validated['start_at'], $validated['end_at']])
                  ->orWhere(function ($q2) use ($validated) {
                      $q2->where('start_at', '<=', $validated['start_at'])
                         ->where('end_at', '>=', $validated['end_at']);
                  });
            })
            ->exists();

        if ($overlap) {
            return response()->json(['message' => 'Этот временной интервал уже занят.'], 422);
        }

        $booking = Booking::create([
            'photographer_id' => $photographerId,
            'client_id' => $clientId,
            'start_at' => $validated['start_at'],
            'end_at' => $validated['end_at'],
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Бронь успешно создана',
            'booking' => $booking
        ], 201);
    }

    /**
     * 👀 Получить все бронирования (по текущему пользователю)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Если пользователь — фотограф
        $bookings = Booking::with(['client:id,name,email', 'photographer:id,name,email'])
            ->where(function ($q) use ($user) {
                $q->where('photographer_id', $user->id)
                  ->orWhere('client_id', $user->id);
            })
            ->orderBy('start_at', 'asc')
            ->get();

        return response()->json($bookings);
    }
}
