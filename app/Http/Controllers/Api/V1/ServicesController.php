<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\Service;

class ServicesController extends Controller {
    public function index() {
        $services = Service::all();
        return response()->json($services);
    }

    public function show($slug) {
        $service = Service::where('slug',$slug)->firstOrFail();
        return response()->json($service);
    }
}
