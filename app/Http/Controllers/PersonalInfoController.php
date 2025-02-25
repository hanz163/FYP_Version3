<?php
/* Author: Ooi Wei Han */
namespace App\Http\Controllers;

use App\Models\PasswordHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PersonalInfoController extends Controller {

    public function showPersonalInfo() {
        $userID = Auth::id();

        if (!$userID) {
            return redirect()->route('login')->with('error', 'You must be logged in to view this page.');
        }

        $userDetails = DB::table('users')
                ->where('id', $userID)
                ->first(['first_name', 'last_name', 'email']);

        return view('personalInfo', ['user' => $userDetails]);
    }

    public function updatePersonalInfo(Request $request) {
        $userID = Auth::id();

        if (!$userID) {
            return redirect()->route('login')->with('error', 'You must be logged in to update your information.');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
        ]);

        DB::table('users')
                ->where('id', $userID)
                ->update([
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
        ]);

        return redirect()->route('personalInfo')->with('personalInfoStatus', 'Personal information updated successfully.');
    }

    public function updatePassword(Request $request) {
        $request->validate([
            'currentPassword' => 'required|string',
            'newPassword' => [
                'required',
                'string',
                'confirmed',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*?&]/',
            ],
        ]);
        
        $user = Auth::user();
        
        if (!Hash::check($request->currentPassword, $user->password)) {
            return redirect()->route('personalInfo')->with('passwordError', 'Current password is incorrect.');
        }
        
        $previousPasswords = PasswordHistory::where('user_id', $user->id)
                        ->pluck('password')->toArray();
        
        foreach ($previousPasswords as $password) {
            if (Hash::check($request->newPassword, $password)) {
                return redirect()->route('personalInfo')->with('passwordError', 'You cannot use previous passwords.');
            }
        }
        $user->password = Hash::make($request->newPassword);
        $user->save();
        
        PasswordHistory::create([
            'user_id' => $user->id,
            'password' => $user->password,
        ]);

        return redirect()->route('personalInfo')->with('passwordStatus', 'Password updated successfully.');
    }

    public function deleteAccount(Request $request) {
        $userID = Auth::id();

        if (!$userID) {
            return redirect()->route('login')->with('error', 'You must be logged in to delete your account.');
        }

        DB::table('users')->where('id', $userID)->delete();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Your account has been deleted.');
    }
}
