<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class Prescription extends Controller
{
    public function uploadDocument(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

         

            $appointmentId = $request->appointment_id;
            $doctorId = $user->id;
            $patientId = $request->patient_id;
            $documentPath = null;

            // Handle file upload
            if ($request->hasFile('document')) {
                $file = $request->file('document');

                // Generate a unique filename
                $fileName = time() . '.' . $file->getClientOriginalExtension();

                // Store the file in storage/app/public/documents
                $documentPath = $file->storeAs('documents', $fileName, 'public');
            }

            // Insert document record
            DB::table('prescriptions')->insert([
                'appointment_id' => $appointmentId,
                'doctor_id' => $doctorId,
                'patient_id' => $patientId,
                'document' => $documentPath, // Store file path in DB
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info("Document uploaded successfully for patient: $patientId");

            return response()->json([
                'status' => true,
                'message' => 'Document uploaded successfully',
                'file_path' => asset('storage/' . $documentPath) // Return file URL
            ]);
        } catch (\Exception $e) {
            Log::error('Document upload error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function getDocuments(Request $request)
    {
        Log::info('Fetching documents request:', $request->all());

        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }


            // Query builder
            $query = DB::table('prescriptions')
                ->where('patient_id', $user->id)
                ->leftJoin('doctors', 'doctors.user_id', '=', 'prescriptions.doctor_id')
                ->select(
                    'prescriptions.id',
                    'prescriptions.doctor_id',
                    'prescriptions.patient_id',
                    'prescriptions.document',
                    'doctors.user_id',
                    'doctors.name',
                    'doctors.age',
                    'doctors.email',
                    'doctors.phone_no',
                    'doctors.created_at',
                    'doctors.updated_at',
                );


            $documents = $query->get()->map(function ($doc) {
                $doc->document_url = $doc->document ? asset('storage/' . $doc->document) : null;
                return $doc;
            });

            return response()->json(['status' => true, 'document' => $documents]);
        } catch (\Exception $e) {
            Log::error('Error fetching documents: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }
}
