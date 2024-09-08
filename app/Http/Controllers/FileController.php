<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * This controls the uploaded file management
 */
class FileController extends Controller
{
    /**
     * Get the file
     *
     * @return mixed
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function get(Request $request, File $file)
    {
        $this->authorize('view', $file);

        if (Storage::exists($file->full_path)) {
            return response(Storage::get($file->full_path), 200)->header('Content-Type', Storage::mimeType($file->full_path))->header('Content-Disposition', 'inline');
        } else {
            return abort(404);
        }
    }

    /**
     * Store the file
     *
     * @return false|\Illuminate\Http\RedirectResponse|string
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request)
    {
        $this->authorize('create', File::class);

        $this->validateRequest();

        $id = $this->saveFile($request->file('file'));

        if ($request->expectsJson()) {
            return json_encode([
                'file_id' => $id,
                'message' => 'File successfully uploaded',
            ]);
        }

        return redirect()->back()->withSuccess('File successfully uploaded');
    }

    /**
     * Delete the provided file
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Request $request, File $file)
    {
        $this->authorize('delete', $file);

        if (Storage::delete($file->full_path) == true) {
            return redirect()->back()->withSuccess('File successfully deleted');
        }

        return redirect()->back()->with('error', 'An error occurred during the deletion of the file');
    }

    /**
     * Validate the request data and filetypes
     *
     * @return mixed
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validateRequest()
    {
        return request()->validate([
            'file' => 'required|file|mimes:pdf,xls,xlsx,doc,docx,txt,png,jpg,jpeg',
        ]);
    }

    /**
     * Save the provided file using the naming scheme.
     *
     * @return string
     */
    public static function saveFile(UploadedFile $file, ?string $filename = null)
    {
        $extension = $file->getClientOriginalExtension();
        $id = sha1($file->getClientOriginalName() . now()->format('Ymd_His') . rand(1000, 9999));

        if ($filename == null) {
            $filename = now()->format('Ymd_His') . '_' . $id;
        }

        if (! preg_match('/\.([a-zA-Z]*)/', $filename) && $extension != null) {
            // Filename doesn't have anything that resembles and extension
            $filename = $filename . '.' . $extension;
        }

        Storage::putFileAs('public/files/', $file, $filename);

        File::create([
            'id' => $id,
            'name' => $file->getClientOriginalName(),
            'uploaded_by' => Auth::id(),
            'path' => $filename,
        ]);

        return $id;
    }
}
