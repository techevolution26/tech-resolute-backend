<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UploadController extends Controller {
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
        'file' => 'required|file|mimes:jpg,jpeg,png,gif,webp,avif|max:5120'
    ]);

    try {
        $file = $request->file('file');

        // sanitize original name and generate a random prefix
        $safeOriginal = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
        $filename = Str::random(10) . '_' . $safeOriginal;

        // store on the "public" disk under uploads/ and ensure visibility
        // storeAs returns the stored path (e.g. "uploads/xxxxx.jpg")
        $path = $file->storeAs('uploads', $filename, 'public');

        // public path from disk (usually "/storage/uploads/xxx")
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        $publicPath = $disk->url($path);

        // absolute URL (uses app.url config)
        $absoluteUrl = rtrim(config('app.url') ?? url('/'), '/') . $publicPath;

        // Optionally: return the storage key/path too
        return response()->json([
            'key' => $path,
            'publicUrl' => $publicPath,
            'url' => $absoluteUrl,
        ], 201);

    } catch (\Throwable $e) {
        Log::error('Upload failed: '.$e->getMessage(), [
            'exception' => $e
        ]);
        return response()->json(['message' => 'Upload failed'], 500);
    }
}

}
