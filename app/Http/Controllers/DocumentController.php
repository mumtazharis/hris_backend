<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class DocumentController extends Controller
{
    public function getEmployeeDocument(string $employee_id){
        $hrUser = Auth::user();
         if (!$hrUser || !$hrUser->company_id) {
            return response()->json(['message' => 'HR user not authenticated or company_id not found.'], 403);
        }
        
        $employee = Employee::where('employee_id', $employee_id)
                    ->where('company_id', $hrUser->company_id)
                    ->first();
        if (!$employee) {
            return response()->json(['message' => 'Employee not found or.'], 404);
        }

        $documents = $employee->document()->latest()->select('id','employee_id','document_name','document_type','issue_date','expiry_date')->get();
        return response()->json([
        'employee_id' => $employee->employee_id,
        'documents' => $documents,
        ]);
    }

    public function store(Request $request){
         // 1. Ambil informasi user HR yang sedang login
        $hrUser = Auth::user();

        // Validasi apakah user HR ada dan punya company_id
        if (!$hrUser || !$hrUser->company_id) {
            return response()->json(['message' => 'HR user not authenticated or company_id not found.'], 403);
        }

        $validatedData = $request->validate([
            'employee_id' => 'required',
            'document_name' => 'required|string|max:255',
            'document_type' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'expiry_date' => 'required|date',
            'document' => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120'

        ]);

        DB::beginTransaction();
        try {

            $path = $request->file('document')->store('employee_document', 's3');
                // $fileName = basename($path);
            $validatedData['document'] = $path;

            $document = Document::create($validatedData);
            DB::commit();

             return response()->json([
                'document' => $document,
            ], 201);


        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create employee and user.', 'error' => $e->getMessage()], 500);
        }
    }

    public function download(string $document_id){
        $hrUser = Auth::user();
         if (!$hrUser || !$hrUser->company_id) {
            return response()->json(['message' => 'HR user not authenticated or company_id not found.'], 403);
        }
        
        $document = Document::where('id', $document_id)
                    ->whereHas('employee', function ($query) use ($hrUser) {
                        $query->where('company_id', $hrUser->company_id);
                    })
                    ->first();

        if (!$document) {
            return response()->json(['message' => 'Employee not found or.'], 404);
        }

        $employeeDocument = null;
        if (!empty($document->document)) {
            $employeeDocument = Storage::disk('s3')->temporaryUrl(
                $document->document,
                Carbon::now()->addMinutes(10) // berlaku 10 menit
            );
        }
        return response()->json(['document_url' => $employeeDocument]);
    }
}
