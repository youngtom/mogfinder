<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Requests\FeedbackFormRequest;
use Auth;

class ContactController extends Controller
{
	public function __construct() {
		$this->middleware('auth');
	}
	
    public function index() {
	    return view('contact.feedback');
    }
    
    public function store(FeedbackFormRequest $request) {
	    \Mail::send('emails.feedback',
	        array(
	            'userid' => Auth::user()->id,
	            'email' => Auth::user()->email,
	            'referrer' => $request->get('referrer'),
	            'subject' => $request->get('subject'),
	            'usermessage' => $request->get('message')
	        ), function($message)
	    {
	        $message->from('feedback@mogcollector.com');
	        $message->to('tritus@gmail.com', 'Admin')->subject('MogCollector.com Feedback');
	    });
	    
	    return \Redirect::route('feedback')->with('status', 'Thanks for the feedback!');
    }
}
