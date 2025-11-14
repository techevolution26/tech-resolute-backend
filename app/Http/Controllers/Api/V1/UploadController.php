<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Aws\S3\S3Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    // public function presign( Request $r ) {
    //     $r->validate( [
    //         'filename' => 'required|string',
    //         'contentType' => 'required|string'
    //     ] );
    //     $filename = Str::random( 12 ) . '_' . basename( $r->input( 'filename' ) );
    //     $key = 'products/' . $filename;

    //     $s3Client = new S3Client( [
    //         'version' => 'latest',
    //         'region' => config( 'filesystems.disks.s3.region' ),
    //         'credentials' => [
    //             'key' => config( 'filesystems.disks.s3.key' ),
    //             'secret' => config( 'filesystems.disks.s3.secret' ),
    //         ],
    //         'endpoint' => config( 'filesystems.disks.s3.endpoint' ) ?: null,
    //     ] );

    //     $cmd = $s3Client->getCommand( 'PutObject', [
    //         'Bucket' => config( 'filesystems.disks.s3.bucket' ),
    //         'Key' => $key,
    //         'ContentType' => $r->input( 'contentType' ),
    //         'ACL' => 'public-read'
    //     ] );

    //     $request = $s3Client->createPresignedRequest( $cmd, '+15 minutes' );
    //     $presignedUrl = ( string ) $request->getUri();

    //     $publicUrl = rtrim( config( 'filesystems.disks.s3.url' ) ?? ( 'https://' . config( 'filesystems.disks.s3.bucket' ) . '.s3.amazonaws.com' ), '/' ) . '/' . $key;

    //     return response()->json( [ 'url' => $presignedUrl, 'key' => $key, 'publicUrl' => $publicUrl ] );
    // }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,webp,avif|max:5120',
        ]);

        try {
            $file = $request->file('file');

            // sanitize original name and generate a random prefix
            $safeOriginal = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
            $filename = \Illuminate\Support\Str::random(10).'_'.$safeOriginal;

            // store on the "public" disk under uploads/
            $path = $file->storeAs('uploads', $filename, 'public');

            // public path from disk ("/storage/uploads/xxx.jpg")
            $disk = \Illuminate\Support\Facades\Storage::disk('public');
            $publicPath = $disk->url($path);

            // Build an absolute URL in a robust way:
            // 1) Prefer APP_URL (config('app.url')) if explicitly set to a non-localhost host
            // 2) Fallback to the current request host/scheme
            $appUrl = config('app.url');
            $appUrl = is_string($appUrl) && $appUrl !== '' ? rtrim($appUrl, '/') : null;

            // detect "bad" appUrl (localhost, 127.0.0.1) and ignore
            if ($appUrl && preg_match('/^(https?:\/\/)(localhost|127\.0\.0\.1|::1)(:\d+)?$/i', $appUrl)) {
                $appUrl = null;
            }

            if (! $appUrl) {
                // fallback to the incoming request's host and scheme
                $appUrl = rtrim($request->getSchemeAndHttpHost(), '/'); // e.g. https://your-backend-host
            }

            $absoluteUrl = $appUrl.$publicPath;

            return response()->json([
                'key' => $path,
                'publicUrl' => $publicPath, // relative path - useful for frontends
                'url' => $absoluteUrl,      // absolute URL built from APP_URL or request host
            ], 201);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Upload failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json(['message' => 'Upload failed'], 500);
        }
    }
}
