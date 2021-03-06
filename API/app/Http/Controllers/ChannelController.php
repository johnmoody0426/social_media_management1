<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Twitter\Channel;

class ChannelController extends Controller
{

    private $user;
    private $selectedChannel;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = auth()->user();
            $this->selectedChannel = $this->user->selectedChannel();
            return $next($request);
        });
    }

    public function channels()
    {
        $user = $this->user;
        return $user->allFormattedChannels();
    }

    public function select($id)
    {
        $user = $this->user;
        $channel = $user->getChannel($id);

        if ($channel) {
            $channel->select($user);
        }

        return $user->allFormattedChannels();
    }

    public function destroy($id)
    {
        $channel = $this->user->channels()->find($id);

        if ($channel) {
            $channel->delete();
            $channel = $this->user->channels()->first();

            if ($channel) {
                $channel->select();
                $channel->details->select();
            }

            $this->decreaseSubscription();

            return response()->json(["message" => "Channel has been deleted"], 200);
        } else {
            return response()->json(["error" => "You don't have permission to perform this action"], 403);
        }
    }

    public function decreaseSubscription()
    {
        $user = $this->user;
        $users = Channel::where('user_id', $user->id)->count();
        $activeSubscription = $this->user->subscribed('main');
        if ($users == 1) {
            $user->subscription('main')->cancel();
            Channel::where('user_id', $user->id)->update(["paid" => false]);
        }
        try {
            if ($activeSubscription) {
                $user->subscription('main')->updateQuantity($users);
            }

            return response()->json(["success" => true], 200);
        } catch (\Throwable $th) {
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }
}
