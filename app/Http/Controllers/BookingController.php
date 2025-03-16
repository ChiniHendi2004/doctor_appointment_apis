<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class BookingController extends Controller
{
    // ✅ Book a Slot
    public function bookSlot(Request $request)
    {
        Log::info('Incoming booking request:', $request->all()); // ✅ Log request payload

        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            $request->validate([
                'doctor_id' => 'required|integer',
                'date' => 'required|date',
                'time_slot' => 'required|string'
            ]);

            $doctorId = $request->doctor_id;
            $date = $request->date;
            $timeSlot = $request->time_slot;

            Log::info("Checking availability for provider: $doctorId, date: $date, time: $timeSlot");

            // Check if slot is available
            $isUnavailable = DB::table('unavailable_slots')
                ->where('doctor_id', $doctorId)
                ->where('date', $date)
                ->where('time_slot', $timeSlot)
                ->exists();

            $isBooked = DB::table('appointments')
                ->where('doctor_id', $doctorId)
                ->where('date', $date)
                ->where('time_slot', $timeSlot)
                ->exists();

            if ($isUnavailable || $isBooked) {
                return response()->json(['status' => false, 'message' => 'Slot is not available'], 400);
            }

            // Book the slot
            DB::table('appointments')->insert([
                'doctor_id' => $doctorId,
                'patient_id' => $user->id,
                'date' => $date,
                'time_slot' => $timeSlot
            ]);

            Log::info("Appointment booked successfully for user {$user->id}");

            return response()->json(['status' => true, 'message' => 'Appointment booked successfully']);
        } catch (\Exception $e) {
            Log::error('Booking error: ' . $e->getMessage()); // ✅ Log error details
            return response()->json(['status' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }



    // ✅ Cancel Appointment
    public function cancelAppointment(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        // Check if the user owns this appointment
        $appointment = DB::table('appointments')
            ->where('id', $request->appointment_id)
            ->where(function ($query) use ($user) {
                $query->where('patient_id', $user->id)
                    ->orWhere('doctor_id', $user->id);
            })
            ->first();

        if (!$appointment) {
            return response()->json(['message' => 'Unauthorized or appointment not found'], 403);
        }

        // Update the status instead of deleting the appointment
        $updated = DB::table('appointments')
            ->where('id', $request->appointment_id)
            ->update(['status' => 'canceled']);

        if ($updated) {
            return response()->json(['status' => true, 'message' => 'Appointment Cancell successfully']);
        } else {
            return response()->json(['message' => 'Failed to cancel appointment. No rows affected.'], 500);
        }
    }
    public function approveAppointment(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        // Check if the user owns this appointment
        $appointment = DB::table('appointments')
            ->where('id', $request->appointment_id)
            ->where(function ($query) use ($user) {
                $query->where('patient_id', $user->id)
                    ->orWhere('doctor_id', $user->id);
            })
            ->first();

        if (!$appointment) {
            return response()->json(['message' => 'Unauthorized or appointment not found'], 403);
        }

        // Update the status instead of deleting the appointment
        $updated = DB::table('appointments')
            ->where('id', $request->appointment_id)
            ->update(['status' => 'pending']);

        if ($updated) {
            return response()->json(['status' => true, 'message' => 'Appointment approved successfully']);
        } else {
            return response()->json(['message' => 'Failed to approve appointment. No rows affected.'], 500);
        }
    }
    public function confirmAppointment(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        // Check if the user owns this appointment
        $appointment = DB::table('appointments')
            ->where('id', $request->appointment_id)
            ->where(function ($query) use ($user) {
                $query->where('patient_id', $user->id)
                    ->orWhere('doctor_id', $user->id);
            })
            ->first();

        if (!$appointment) {
            return response()->json(['message' => 'Unauthorized or appointment not found'], 403);
        }

        // Update the status instead of deleting the appointment
        $updated = DB::table('appointments')
            ->where('id', $request->appointment_id)
            ->update(['status' => 'pending']);

        if ($updated) {
            return response()->json(['message' => 'Appointment Complete successfully']);
        } else {
            return response()->json(['message' => 'Failed to Complete appointment. No rows affected.'], 500);
        }
    }

    // ✅ Get User's Appointments
    public function getbookedbyAppointments()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $appointments = DB::table('appointments')
            ->where('customer_id', $user->id)
            ->where('STATUS', '0')
            ->leftJoin('personal_information', 'personal_information.user_id', '=', 'appointments.provider_id')
            ->select(
                'appointments.id',
                'appointments.provider_id',
                'appointments.customer_id',
                'appointments.date',
                'appointments.time_slot',
                'appointments.STATUS',
                'personal_information.user_id',
                'personal_information.full_name',
                'personal_information.date_of_birth',
                'personal_information.gender',
                'personal_information.email',
                'personal_information.phone_no',
                'personal_information.state',
                'personal_information.district',
                'personal_information.village',
                'personal_information.pincode',
                'personal_information.created_at',
                'personal_information.updated_at',
                'personal_information.role',
                'personal_information.profile_img'
            )
            ->get();

        // Modify profile_img to include full URL
        foreach ($appointments as $item) {
            if (!empty($item->profile_img)) {
                $item->profile_img = asset('storage/' . $item->profile_img);
            } else {
                $item->profile_img = asset('assets/images/dummy-profile.png'); // Default profile image
            }
        }

        return response()->json(['appointments' => $appointments]);
    }

    public function getMyAppointments()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $appointments = DB::table('appointments')
            ->where('doctor_id', $user->id)

            ->leftJoin('patients', 'patients.user_id', '=', 'appointments.patient_id')
            ->select(
                'appointments.id',
                'appointments.doctor_id',
                'appointments.patient_id',
                'appointments.date',
                'appointments.time_slot',
                'appointments.STATUS',
                'patients.user_id',
                'patients.name',
                'patients.age',
                'patients.gender',
                'patients.email',
                'patients.phone_no',
                'patients.created_at',
                'patients.updated_at',
                'patients.profile_img'
            )
            ->get();

        // Modify profile_img to include full URL
        foreach ($appointments as $item) {
            if (!empty($item->profile_img)) {
                $item->profile_img = asset('storage/' . $item->profile_img);
            } else {
                $item->profile_img = asset('assets/images/dummy-profile.png'); // Default profile image
            }
        }

        return response()->json(['appointments' => $appointments]);
    }

    public function getUpcomingAppointments()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $appointments = DB::table('appointments')
            ->where('doctor_id', $user->id)
            ->where('status', 'pending')

            ->leftJoin('patients', 'patients.user_id', '=', 'appointments.patient_id')
            ->select(
                'appointments.id',
                'appointments.doctor_id',
                'appointments.patient_id',
                'appointments.date',
                'appointments.time_slot',
                'appointments.STATUS',
                'patients.user_id',
                'patients.name',
                'patients.age',
                'patients.gender',
                'patients.email',
                'patients.phone_no',
                'patients.created_at',
                'patients.updated_at',
                'patients.profile_img'
            )
            ->get();

        // Modify profile_img to include full URL
        foreach ($appointments as $item) {
            if (!empty($item->profile_img)) {
                $item->profile_img = asset('storage/' . $item->profile_img);
            } else {
                $item->profile_img = asset('assets/images/dummy-profile.png'); // Default profile image
            }
        }

        return response()->json(['appointments' => $appointments]);
    }
    public function getpendingAppointments()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $appointments = DB::table('appointments')
            ->where('doctor_id', $user->id)
            ->where('status', 'approve')

            ->leftJoin('patients', 'patients.user_id', '=', 'appointments.patient_id')
            ->select(
                'appointments.id',
                'appointments.doctor_id',
                'appointments.patient_id',
                'appointments.date',
                'appointments.time_slot',
                'appointments.STATUS',
                'patients.user_id',
                'patients.name',
                'patients.age',
                'patients.gender',
                'patients.email',
                'patients.phone_no',
                'patients.created_at',
                'patients.updated_at',
                'patients.profile_img'
            )
            ->get();

        // Modify profile_img to include full URL
        foreach ($appointments as $item) {
            if (!empty($item->profile_img)) {
                $item->profile_img = asset('storage/' . $item->profile_img);
            } else {
                $item->profile_img = asset('assets/images/dummy-profile.png'); // Default profile image
            }
        }

        return response()->json(['appointments' => $appointments]);
    }
    public function getCompleteAppointments()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $appointments = DB::table('appointments')
            ->where('doctor_id', $user->id)
            ->where('status', 'completed')

            ->leftJoin('patients', 'patients.user_id', '=', 'appointments.patient_id')
            ->select(
                'appointments.id',
                'appointments.doctor_id',
                'appointments.patient_id',
                'appointments.date',
                'appointments.time_slot',
                'appointments.STATUS',
                'patients.user_id',
                'patients.name',
                'patients.age',
                'patients.gender',
                'patients.email',
                'patients.phone_no',
                'patients.created_at',
                'patients.updated_at',
                'patients.profile_img'
            )
            ->get();

        // Modify profile_img to include full URL
        foreach ($appointments as $item) {
            if (!empty($item->profile_img)) {
                $item->profile_img = asset('storage/' . $item->profile_img);
            } else {
                $item->profile_img = asset('assets/images/dummy-profile.png'); // Default profile image
            }
        }

        return response()->json(['appointments' => $appointments]);
    }
    public function getCanceledAppointments()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $appointments = DB::table('appointments')
            ->where('doctor_id', $user->id)
            ->where('status', 'canceled')

            ->leftJoin('patients', 'patients.user_id', '=', 'appointments.patient_id')
            ->select(
                'appointments.id',
                'appointments.doctor_id',
                'appointments.patient_id',
                'appointments.date',
                'appointments.time_slot',
                'appointments.STATUS',
                'patients.user_id',
                'patients.name',
                'patients.age',
                'patients.gender',
                'patients.email',
                'patients.phone_no',
                'patients.created_at',
                'patients.updated_at',
                'patients.profile_img'
            )
            ->get();

        // Modify profile_img to include full URL
        foreach ($appointments as $item) {
            if (!empty($item->profile_img)) {
                $item->profile_img = asset('storage/' . $item->profile_img);
            } else {
                $item->profile_img = asset('assets/images/dummy-profile.png'); // Default profile image
            }
        }

        return response()->json(['appointments' => $appointments]);
    }


    public function getUpcomingPatientAppointments()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $appointments = DB::table('appointments')
            ->where('patient_id', $user->id)
            ->where('status', 'pending')

            ->leftJoin('doctors', 'doctors.user_id', '=', 'appointments.doctor_id')
            ->select(
                'appointments.id',
                'appointments.doctor_id',
                'appointments.patient_id',
                'appointments.date',
                'appointments.time_slot',
                'appointments.STATUS',
                'doctors.user_id',
                'doctors.name',
                'doctors.age',
                'doctors.gender',
                'doctors.email',
                'doctors.phone_no',
                'doctors.created_at',
                'doctors.updated_at',
                'doctors.profile_img'
            )
            ->get();

        // Modify profile_img to include full URL
        foreach ($appointments as $item) {
            if (!empty($item->profile_img)) {
                $item->profile_img = asset('storage/' . $item->profile_img);
            } else {
                $item->profile_img = asset('assets/images/dummy-profile.png'); // Default profile image
            }
        }

        return response()->json(['appointments' => $appointments]);
    }
    public function getCompletePatientAppointments()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $appointments = DB::table('appointments')
            ->where('patient_id', $user->id)
            ->where('status', 'completed')

            ->leftJoin('doctors', 'doctors.user_id', '=', 'appointments.doctor_id')
            ->select(
                'appointments.id',
                'appointments.doctor_id',
                'appointments.patient_id',
                'appointments.date',
                'appointments.time_slot',
                'appointments.STATUS',
                'doctors.user_id',
                'doctors.name',
                'doctors.age',
                'doctors.gender',
                'doctors.email',
                'doctors.phone_no',
                'doctors.created_at',
                'doctors.updated_at',
                'doctors.profile_img'
            )
            ->get();

        // Modify profile_img to include full URL
        foreach ($appointments as $item) {
            if (!empty($item->profile_img)) {
                $item->profile_img = asset('storage/' . $item->profile_img);
            } else {
                $item->profile_img = asset('assets/images/dummy-profile.png'); // Default profile image
            }
        }

        return response()->json(['appointments' => $appointments]);
    }
    public function getCanceledPatientAppointments()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $appointments = DB::table('appointments')
            ->where('patient_id', $user->id)
            ->where('status', 'canceled')

            ->leftJoin('doctors', 'doctors.user_id', '=', 'appointments.doctor_id')
            ->select(
                'appointments.id',
                'appointments.doctor_id',
                'appointments.patient_id',
                'appointments.date',
                'appointments.time_slot',
                'appointments.STATUS',
                'doctors.user_id',
                'doctors.name',
                'doctors.age',
                'doctors.gender',
                'doctors.email',
                'doctors.phone_no',
                'doctors.created_at',
                'doctors.updated_at',
                'doctors.profile_img'
            )
            ->get();

        // Modify profile_img to include full URL
        foreach ($appointments as $item) {
            if (!empty($item->profile_img)) {
                $item->profile_img = asset('storage/' . $item->profile_img);
            } else {
                $item->profile_img = asset('assets/images/dummy-profile.png'); // Default profile image
            }
        }

        return response()->json(['appointments' => $appointments]);
    }



    public function getpatientAppointments()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $appointments = DB::table('appointments')
            ->where('patient_id', $user->id)

            ->leftJoin('doctors', 'doctors.user_id', '=', 'appointments.doctor_id')
            ->select(
                'appointments.id',
                'appointments.doctor_id',
                'appointments.patient_id',
                'appointments.date',
                'appointments.time_slot',
                'appointments.STATUS',
                'doctors.user_id',
                'doctors.name',
                'doctors.age',
                'doctors.gender',
                'doctors.email',
                'doctors.phone_no',
                'doctors.created_at',
                'doctors.updated_at',
                'doctors.profile_img'
            )
            ->get();

        // Modify profile_img to include full URL
        foreach ($appointments as $item) {
            if (!empty($item->profile_img)) {
                $item->profile_img = asset('storage/' . $item->profile_img);
            } else {
                $item->profile_img = asset('assets/images/dummy-profile.png'); // Default profile image
            }
        }

        return response()->json(['appointments' => $appointments]);
    }


    public function getTodayPatientAppointments()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $appointments = DB::table('appointments')
            ->where('patient_id', $user->id)
            ->where('status', 'pending')
            ->whereDate('date', Carbon::today())
            ->leftJoin('doctors', 'doctors.user_id', '=', 'appointments.doctor_id')
            ->select(
                'appointments.id',
                'appointments.doctor_id',
                'appointments.patient_id',
                'appointments.date',
                'appointments.time_slot',
                'appointments.STATUS',
                'doctors.user_id',
                'doctors.name',
                'doctors.age',
                'doctors.gender',
                'doctors.email',
                'doctors.phone_no',
                'doctors.created_at',
                'doctors.updated_at',
                'doctors.specialization',
                'doctors.profile_img'
            )
            ->get();

        if ($appointments->isNotEmpty()) {
            foreach ($appointments as $appointment) {
                if (!empty($appointment->profile_img)) {
                    $appointment->profile_img = asset('storage/' . $appointment->profile_img);
                }
            }
            return response()->json(['status' => true, 'data' => $appointments]);
        } else {
            return response()->json(['status' => false, 'message' => 'No appointments found']);
        }
    }
    public function getTodayDoctorAppointments()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $appointments = DB::table('appointments')
            ->where('doctor_id', $user->id)
            ->where('status', 'pending')
            ->whereDate('date', Carbon::today())
            ->leftJoin('patients', 'patients.user_id', '=', 'appointments.doctor_id')
            ->select(
                'appointments.id',
                'appointments.doctor_id',
                'appointments.patient_id',
                'appointments.date',
                'appointments.time_slot',
                'appointments.STATUS',
                'patients.user_id',
                'patients.name',
                'patients.age',
                'patients.gender',
                'patients.email',
                'patients.phone_no',
                'patients.created_at',
                'patients.updated_at',
                'patients.profile_img'
            )
            ->get();

        if ($appointments->isNotEmpty()) {
            foreach ($appointments as $appointment) {
                if (!empty($appointment->profile_img)) {
                    $appointment->profile_img = asset('storage/' . $appointment->profile_img);
                }
            }
            return response()->json(['status' => true, 'data' => $appointments]);
        } else {
            return response()->json(['status' => false, 'message' => 'No appointments found']);
        }
    }
}
