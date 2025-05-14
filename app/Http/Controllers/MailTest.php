<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MailTest extends Controller
{
    public function store()
    {
        // $order = Order::findOrFail($request->order_id);
        
        $name = "John Doe";

        // Ship the order...
 
        Mail::to("kuhaku.manga.id@gmail.com")->send(new WelcomeMail($name));
 
        return response()->json($name, 200,);
    }
}
