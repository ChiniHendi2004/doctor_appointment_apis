<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Illuminate\Support\Facades\Log;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;


class PersonalInfoController extends Controller
{
    // ✅ Fetch all personal information for the logged-in user
    public function FetchDoctorInfo(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            $data = DB::table('doctors')->where('user_id', $user->id)->get();

            return response()->json(['status' => true, 'data' => $data]);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['status' => false, 'message' => 'Token is invalid or expired'], 401);
        }
    }
    public function FetchDoctorDetails(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            $data = DB::table('doctors')
                ->where('user_id', $request->doctor_id)->get();

            return response()->json(['status' => true, 'data' => $data]);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['status' => false, 'message' => 'Token is invalid or expired'], 401);
        }
    }

    public function FetchPatientInfo(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            $data = DB::table('patients')->where('user_id', $user->id)->get();

            return response()->json(['status' => true, 'data' => $data]);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['status' => false, 'message' => 'Token is invalid or expired'], 401);
        }
    }

    public function FetchDoctorProfile(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            $data = DB::table('doctors')
                ->where('user_id', $user->id)
                ->select('name', 'profile_img')
                ->first();  // Use `first()` if you expect only one record

            if ($data) {
                // Generate full URL for the profile image if it exists
                if ($data->profile_img) {
                    // Use asset() to generate a URL to the public directory
                    $data->profile_img = asset('storage/' . $data->profile_img);
                }

                return response()->json(['status' => true, 'data' => $data]);
            }
            // Check if data is found
            else {
                return response()->json(['status' => false, 'message' => 'No profile found'], 404);
            }
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['status' => false, 'message' => 'Token is invalid or expired'], 401);
        }
    }
    public function FetchPatientProfile(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            $data = DB::table('patients')
                ->where('user_id', $user->id)
                ->select('name', 'profile_img')
                ->first();  // Use `first()` if you expect only one record

            if ($data) {
                // Generate full URL for the profile image if it exists
                if ($data->profile_img) {
                    // Use asset() to generate a URL to the public directory
                    $data->profile_img = asset('storage/' . $data->profile_img);
                }

                return response()->json(['status' => true, 'data' => $data]);
            }
            // Check if data is found
            else {
                return response()->json(['status' => false, 'message' => 'No profile found'], 404);
            }
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['status' => false, 'message' => 'Token is invalid or expired'], 401);
        }
    }


    // ✅ Store personal information (user_id is auto-fetched from JWT)
    public function CreateOrUpdateDoctorInfo(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Check if the personal info already exists
            $existingInfo = DB::table('doctors')->where('user_id', $user->id)->first();

            if ($existingInfo) {
                // ✅ Update existing record
                DB::table('doctors')
                    ->where('user_id', $user->id)
                    ->update([
                        'name' => $request->input('name'),
                        'email' => $request->input('email'),
                        'phone_no' => $request->input('phone'),
                        'specialization' => $request->input('specialization'),
                        'age' => $request->input('age'),
                        'gender' => $request->input('gender'),
                        'work_at' => $request->input('work_at'),
                        'experience' => $request->input('experience'),
                        'address' => $request->input('address'),
                        'updated_at' => now(),
                    ]);

                return response()->json(['status' => true, 'message' => 'Updated successfully']);
            } else {
                // ✅ Insert new record
                $insertId = DB::table('doctors')->insertGetId([
                    'user_id' => $user->id,
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'phone_no' => $request->input('phone'),
                    'specialization' => $request->input('specialization'),
                    'age' => $request->input('age'),
                    'gender' => $request->input('gender'),
                    'work_at' => $request->input('work_at'),
                    'experience' => $request->input('experience'),
                    'address' => $request->input('address'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json(['status' => true, 'message' => 'Inserted successfully', 'id' => $insertId]);
            }
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['status' => false, 'message' => 'Token is invalid or expired'], 401);
        }
    }
    public function CreateOrUpdatePatientInfo(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Check if the personal info already exists
            $existingInfo = DB::table('patients')->where('user_id', $user->id)->first();

            if ($existingInfo) {
                // ✅ Update existing record
                DB::table('patients')
                    ->where('user_id', $user->id)
                    ->update([
                        'name' => $request->input('name'),
                        'email' => $request->input('email'),
                        'phone_no' => $request->input('phone'),
                        'age' => $request->input('age'),
                        'gender' => $request->input('gender'),
                        'updated_at' => now(),
                    ]);

                return response()->json(['status' => true, 'message' => 'Updated successfully']);
            } else {
                // ✅ Insert new record
                $insertId = DB::table('patients')->insertGetId([
                    'user_id' => $user->id,
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'phone_no' => $request->input('phone'),
                    'age' => $request->input('age'),
                    'gender' => $request->input('gender'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json(['status' => true, 'message' => 'Inserted successfully', 'id' => $insertId]);
            }
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['status' => false, 'message' => 'Token is invalid or expired'], 401);
        }
    }


    // public function updateProfileImage(Request $request)
    // {
    //     try {
    //         // Authenticate user from JWT token
    //         $user = JWTAuth::parseToken()->authenticate();

    //         // If user is not authenticated, return unauthorized response
    //         if (!$user) {
    //             return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
    //         }

    //         // Check if the request has a file
    //         if ($request->hasFile('profile_img')) {
    //             $file = $request->file('profile_img');

    //             // Generate a unique filename
    //             $fileName = time() . '.' . $file->getClientOriginalExtension();

    //             // Store the file in public disk (storage/app/public/profile_images)
    //             $filePath = $file->storeAs('profile_images', $fileName, 'public');

    //             // Update the user's profile image path in personal_information table
    //             DB::table('patients')
    //                 ->where('user_id', $user->id)  // Make sure you're matching the user_id
    //                 ->update(['profile_img' => $filePath]);

    //             return response()->json([
    //                 'status' => true,
    //                 'message' => 'Profile image updated successfully',
    //                 'image_url' => asset('storage/' . $filePath) // Return full image URL
    //             ]);
    //         }

    //         return response()->json(['status' => false, 'message' => 'No image uploaded'], 400);
    //     } catch (\Exception $e) {
    //         // Catch any errors
    //         return response()->json(['status' => false, 'message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
    //     }
    // }



    // public function updateProfileImage(Request $request)
    // {
    //     try {
    //         // Authenticate user from JWT token
    //         $user = JWTAuth::parseToken()->authenticate();

    //         if (!$user) {
    //             return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
    //         }

    //         // Check if the request has a file
    //         if ($request->hasFile('profile_img')) {
    //             $file = $request->file('profile_img');

    //             // Upload the file to Cloudinary
    //             $uploadedFileUrl = Cloudinary::upload($file->getRealPath())->getSecurePath();

    //             // Update the user's profile image path in the database
    //             DB::table('patients')
    //                 ->where('user_id', $user->id)
    //                 ->update(['profile_img' => $uploadedFileUrl]);

    //             return response()->json([
    //                 'status' => true,
    //                 'message' => 'Profile image updated successfully',
    //                 'image_url' => $uploadedFileUrl // Cloudinary URL
    //             ]);
    //         }

    //         return response()->json(['status' => false, 'message' => 'No image uploaded'], 400);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Something went wrong',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }


    // public function updateProfileImage(Request $request)
    // {
    //     try {
    //         $user = JWTAuth::parseToken()->authenticate();
    //         if (!$user) {
    //             return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
    //         }

    //         if (!$request->hasFile('profile_img')) {
    //             return response()->json(['status' => false, 'message' => 'No image uploaded'], 400);
    //         }

    //         $file = $request->file('profile_img');
    //         if (!$file) {
    //             return response()->json(['status' => false, 'message' => 'File not received'], 400);
    //         }

    //         // Upload to Cloudinary
    //         $uploadedFileUrl = Cloudinary::upload($file->getRealPath())->getSecurePath();

    //         // Update in database
    //         DB::table('patients')
    //             ->where('user_id', $user->id)
    //             ->update(['profile_img' => $uploadedFileUrl]);

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Profile image updated successfully',
    //             'image_url' => $uploadedFileUrl
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Something went wrong',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }


//     public function updateProfileImage(Request $request)
// {
//     try {
//         // Authenticate user
//         $user = JWTAuth::parseToken()->authenticate();
//         if (!$user) {
//             return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
//         }

//         // Validate that a file is uploaded
//         if (!$request->hasFile('profile_img')) {
//             return response()->json(['status' => false, 'message' => 'No image uploaded'], 400);
//         }

//         $file = $request->file('profile_img');
//         if (!$file) {
//             return response()->json(['status' => false, 'message' => 'File not received'], 400);
//         }

//         // Validate that the uploaded file is an image
//         if (!$file->isValid() || !in_array($file->getClientMimeType(), ['image/jpeg', 'image/png', 'image/gif'])) {
//             return response()->json(['status' => false, 'message' => 'Invalid file type. Only images are allowed.'], 400);
//         }

//         // Upload to Cloudinary
//         $uploadResponse = Cloudinary::upload($file->getRealPath());
//         if (!$uploadResponse) {
//             return response()->json(['status' => false, 'message' => 'Image upload failed.'], 500);
//         }

//         $uploadedFileUrl = $uploadResponse->getSecurePath();

//         // Update database with new image URL
//         DB::table('patients')
//             ->where('user_id', $user->id)
//             ->update(['profile_img' => $uploadedFileUrl]);

//         return response()->json([
//             'status' => true,
//             'message' => 'Profile image updated successfully',
//             'image_url' => $uploadedFileUrl
//         ]);
//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => false,
//             'message' => 'Something went wrong',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }


public function updateProfileImage(Request $request)
{
    try {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!$request->hasFile('profile_img')) {
            return response()->json(['status' => false, 'message' => 'No image uploaded'], 400);
        }

        $file = $request->file('profile_img');
        if (!$file) {
            return response()->json(['status' => false, 'message' => 'File not received'], 400);
        }

        // Validate the file type
        if (!$file->isValid() || !in_array($file->getClientMimeType(), ['image/jpeg', 'image/png', 'image/gif'])) {
            return response()->json(['status' => false, 'message' => 'Invalid file type. Only images are allowed.'], 400);
        }

        // Upload to Cloudinary
        $uploadResponse = Cloudinary::upload($file->getRealPath());

        // 🔍 Log the Cloudinary response
        Log::info('Cloudinary Upload Response:', ['response' => $uploadResponse]);

        // Check if Cloudinary upload was successful
        if (!$uploadResponse || !method_exists($uploadResponse, 'getSecurePath') || is_null($uploadResponse->getSecurePath())) {
            return response()->json(['status' => false, 'message' => 'Image upload failed'], 500);
        }

        // Get uploaded file URL
        $uploadedFileUrl = $uploadResponse->getSecurePath();

        // Update the database with the new image URL
        DB::table('patients')
            ->where('user_id', $user->id)
            ->update(['profile_img' => $uploadedFileUrl]);

        return response()->json([
            'status' => true,
            'message' => 'Profile image updated successfully',
            'image_url' => $uploadedFileUrl
        ]);
    } catch (\Exception $e) {
        Log::error('Profile Image Upload Error:', ['error' => $e->getMessage()]);
        return response()->json([
            'status' => false,
            'message' => 'Something went wrong',
            'error' => $e->getMessage()
        ], 500);
    }
}




    public function getUsersByRole(Request $request)
    {
        try {
            // Authenticate the user using JWT
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Get role from request
            $role = $request->query('role');
            // Fetch users based on role
            $users = DB::table('doctors')
                ->where('specialization', $role)
                ->get();

            return response()->json(['status' => true, 'data' => $users], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Token is invalid or expired'], 401);
        }
    }

    

    public function onlyPatients(Request $request)
    {
        $users = DB::table('users')
            ->leftJoin('patients', 'users.id', 'patients.user_id') // Fixed JOIN condition
            ->where('users.role', 'patient')
            ->select(
                'patients.name',
                'patients.age',
                'patients.gender'
            )
            ->get(); // Added get() to execute the query
    
        return response()->json(['status' => true, 'data' => $users], 200);
    }
    
    public function onlyDoctors(Request $request)
    {
        $users = DB::table('users')
            ->leftJoin('doctors', 'users.id', 'doctors.user_id') // Fixed JOIN condition
            ->where('users.role', 'doctor')
            ->select(
                'doctors.name',
                'doctors.age',
                'doctors.gender'
            )
            ->get(); // Added get() to execute the query
    
        return response()->json(['status' => true, 'data' => $users], 200);
    }
}
