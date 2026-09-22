<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterStudentRequest;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming student registration request.
     */
    public function store(RegisterStudentRequest $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data, $audit): User {
            $profile = new StudentProfile(collect($data)->only([
                'student_no', 'first_name', 'middle_name', 'last_name',
                'course', 'year_level', 'contact_no', 'enrolment_status',
            ])->all());

            $user = new User([
                'name' => $profile->full_name,
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
            $user->role = Role::Student;
            $user->save();

            $user->studentProfile()->save($profile);

            $audit->log($user, 'user.registered', $user);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
