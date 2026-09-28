<?php

namespace App\Http\Controllers;

use App\Models\DocumentRelease;
use Illuminate\View\View;

class DocumentVerificationController extends Controller
{
    /**
     * Publicly confirm that a released document is genuine, without exposing anything
     * beyond what is needed to verify it.
     */
    public function show(DocumentRelease $documentRelease): View
    {
        return view('document-verification.show', [
            'release' => $documentRelease->load('recordRequest'),
        ]);
    }
}
