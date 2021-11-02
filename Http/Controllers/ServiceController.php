<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Models\Payment;
use App\Models\User;
use App\Models\ServerNode;
use App\Models\ServiceTemplate;
use App\Models\Service;
use App\Models\AuthCode;
use Illuminate\Http\Request;
use App\Models\UserNotification;

use phpseclib\Net\SSH2;

use WebToPay;
use Exception;
use Carbon\Carbon;

class ServiceController extends Controller
{

	public function serviceAction(Request $request) {
		$service = Service::find($request->input("service"));
		if(!$service){
			return json_encode(["status"=>"error", "message" => "Service not found."]);
		}
		if($service->user_id != auth()->user()->id && !auth()->user()->admin){
			return json_encode(["status"=>"error", "message" => "Service not found."]);
		}

		return $service->action($request->input("action"));
	}
}