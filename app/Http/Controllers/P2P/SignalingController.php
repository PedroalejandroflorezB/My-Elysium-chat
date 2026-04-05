<?php

namespace App\Http\Controllers\P2P;

use App\Http\Controllers\Controller;
use App\Events\WebRtcOffer;
use App\Events\WebRtcAnswer;
use App\Events\WebRtcIceCandidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SignalingController extends Controller
{
    /**
     * Enviar oferta SDP (iniciador → receptor)
     */
    public function sendOffer(Request $request)
    {
        $request->validate([
            'targetUserId' => 'required|integer|exists:users,id',
            'offer' => 'required|array',
            'sessionId' => 'required|string',
        ]);

        $user = Auth::user();

        broadcast(new WebRtcOffer(
            $request->targetUserId,
            $request->offer,
            $user->id,
            $request->sessionId
        ));

        return response()->json(['status' => 'offer_sent']);
    }

    /**
     * Enviar respuesta SDP (receptor → iniciador)
     */
    public function sendAnswer(Request $request)
    {
        $request->validate([
            'targetUserId' => 'required|integer|exists:users,id',
            'answer' => 'required|array',
            'sessionId' => 'required|string',
        ]);

        $user = Auth::user();

        broadcast(new WebRtcAnswer(
            $request->targetUserId,
            $request->answer,
            $user->id,
            $request->sessionId
        ));

        return response()->json(['status' => 'answer_sent']);
    }

    /**
     * Enviar candidato ICE (bidireccional)
     */
    public function sendIceCandidate(Request $request)
    {
        $request->validate([
            'targetUserId' => 'required|integer|exists:users,id',
            'candidate' => 'required|array',
            'sessionId' => 'required|string',
        ]);

        $user = Auth::user();

        broadcast(new WebRtcIceCandidate(
            $request->targetUserId,
            $request->candidate,
            $user->id,
            $request->sessionId
        ));

        return response()->json(['status' => 'candidate_sent']);
    }
}