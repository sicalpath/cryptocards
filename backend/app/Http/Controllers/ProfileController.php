<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeEmail;
use App\Models\Card;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['getUserDetail']]);
    }

    /**
     * Gets the info about the current user.
     *
     * @return mixed user
     */
    public function me()
    {
        return response()->build(self::RESPONSE_MESSAGE_SUCCESS, auth()->user());
    }

    public function updateMe()
    {
        $user = auth()->user();
        $data = json_decode(Request::getContent(), true);

        $oldEmail = $user->email;

        foreach ($data as $key => $value) {
            if (in_array($key, [
                User::FIELD_EMAIL,
                User::FIELD_NICKNAME,
            ])) {
                $user->$key = $value;
            }
        }

        //todo: integrity constraint check for email and nickname
        $user->save();

        //check if email changed
        if ($oldEmail != $user->email) {
            if ($oldEmail == null) {
                //setting email for first time
                Mail::to($user)->send(new WelcomeEmail($user));
            //todo: confirmation?
            } elseif ($user->email = '') {
                //bad! setting email to blank
                $user->email = $oldEmail;
            //todo: error message
            } else {
                //normal changing of email
                //todo: confirmation?
            }
        }

        return $this->me();
    }

    /**
     * Gets all the cards for a given user. Includes hidden cards if the authorized user is requesting their own profile.
     *
     * @return mixed cards
     */
    public function getUserDetail($user_id)
    {
        $isRequestingMe = auth()->user() && (auth()->user()->id == $user_id);

        $cards = Card::with('attributes');
        if (!$isRequestingMe) {
            $cards = $cards->where(Card::FIELD_HIDDEN_TOGGLE, false);
        }
        $cards = $cards->where('user_id', $user_id)->get();

        return response()->build(self::RESPONSE_MESSAGE_SUCCESS, ['cards'=> $cards]);
    }
}
