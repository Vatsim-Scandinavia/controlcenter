<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

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
     * @throws AuthorizationException
     */
    public function get(Request $request, File $file)
    {
        $this->authorize('view', $file);

        if (Storage::exists($file->full_path)) {
            return Storage::response($file->full_path, null, ['X-Content-Type-Options' => 'nosniff'], 'inline');
        } else {
            return abort(404);
        }
    }

    /**
     * Store the file
     *
     * @return false|RedirectResponse|string
     *
     * @throws AuthorizationException
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
     * @return RedirectResponse
     *
     * @throws AuthorizationException
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
     * @throws ValidationException
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
        $extension = $file->guessExtension();
        $id = sha1($file->getClientOriginalName() . now()->format('Ymd_His') . rand(1000, 9999));

        if ($filename == null) {
            $filename = now()->format('Ymd_His') . '_' . $id;
        }

        if (! preg_match('/\.([a-zA-Z]*)/', $filename) && $extension != null) {
            // Filename doesn't have anything that resembles and extension
            $filename = $filename . '.' . $extension;
        }

        $path = Storage::putFileAs('public/files/', $file, $filename);
        if ($path === false) {
            throw new RuntimeException('Failed to store the uploaded file.');
        }

        try {
            File::create([
                'id' => $id,
                'name' => $file->getClientOriginalName(),
                'uploaded_by' => Auth::id(),
                'path' => $filename,
            ]);
        } catch (Throwable $exception) {
            try {
                if (! Storage::delete($path)) {
                    report(new RuntimeException('Failed to clean up uploaded file: ' . $path));
                }
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }
            throw $exception;
        }

        return $id;
    }
}
