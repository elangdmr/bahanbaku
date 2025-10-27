<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /* =========================================================
     * R&D  (pengganti "Siswa")
     * =======================================================*/
    public function showRND()
    {
        $users = User::where('role', 'R&D')->orderBy('name')->get();
        return view('users_management.show_rnd', compact('users'));
    }

    public function storeRND(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'min:3'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:7'],
        ]);

        User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'role'              => 'R&D',
            'email_verified_at' => now(),
            'password'          => Hash::make($request->password),
        ]);

        return redirect()->route('show-rnd')->with('success', 'Akun R&D berhasil dibuat.');
    }

    public function editRND($id)
    {
        $rnd = User::where('id', $id)->where('role', 'R&D')->firstOrFail();
        return view('users_management.edit_rnd', compact('rnd'));
    }

    public function updateRND(Request $request, $id)
    {
        $request->validate([
            'name'     => ['required', 'min:3'],
            'email'    => ['required', 'email', 'unique:users,email,' . $id],
            'password' => ['nullable', 'min:7'],
        ]);

        $data = [
            'name'  => $request->name,
            'email' => $request->email,
        ];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        User::where('id', $id)->where('role', 'R&D')->update($data);

        return redirect()->route('show-rnd')->with('success', 'Akun R&D diperbarui.');
    }

    /* =========================================================
     * PPIC
     * =======================================================*/
    public function showPPIC()
    {
        $users = User::where('role', 'PPIC')->orderBy('name')->get();
        return view('users_management.show_ppic', compact('users'));
    }

    public function storePPIC(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'min:3'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:7'],
        ]);

        User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'role'              => 'PPIC',
            'email_verified_at' => now(),
            'password'          => Hash::make($request->password),
        ]);

        return redirect()->route('show-ppic')->with('success', 'Akun PPIC berhasil dibuat.');
    }

    public function editPPIC($id)
    {
        $ppic = User::where('id', $id)->where('role', 'PPIC')->firstOrFail();
        return view('users_management.edit_ppic', compact('ppic'));
    }

    public function updatePPIC(Request $request, $id)
    {
        $request->validate([
            'name'     => ['required', 'min:3'],
            'email'    => ['required', 'email', 'unique:users,email,' . $id],
            'password' => ['nullable', 'min:7'],
        ]);

        $data = [
            'name'  => $request->name,
            'email' => $request->email,
        ];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        User::where('id', $id)->where('role', 'PPIC')->update($data);

        return redirect()->route('show-ppic')->with('success', 'Akun PPIC diperbarui.');
    }

    /* =========================================================
     * PURCHASING
     * =======================================================*/
    public function showPurchasing()
    {
        $users = User::where('role', 'Purchasing')->orderBy('name')->get();
        return view('users_management.show_purchasing', compact('users'));
    }

    public function storePurchasing(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'min:3'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:7'],
        ]);

        User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'role'              => 'Purchasing',
            'email_verified_at' => now(),
            'password'          => Hash::make($request->password),
        ]);

        return redirect()->route('show-purchasing')->with('success', 'Akun Purchasing berhasil dibuat.');
    }

    public function editPurchasing($id)
    {
        $purchasing = User::where('id', $id)->where('role', 'Purchasing')->firstOrFail();
        return view('users_management.edit_purchasing', compact('purchasing'));
    }

    public function updatePurchasing(Request $request, $id)
    {
        $request->validate([
            'name'     => ['required', 'min:3'],
            'email'    => ['required', 'email', 'unique:users,email,' . $id],
            'password' => ['nullable', 'min:7'],
        ]);

        $data = [
            'name'  => $request->name,
            'email' => $request->email,
        ];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        User::where('id', $id)->where('role', 'Purchasing')->update($data);

        return redirect()->route('show-purchasing')->with('success', 'Akun Purchasing diperbarui.');
    }

    /* =========================================================
     * COMMON
     * =======================================================*/
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $role = $user->role;
        $user->delete();

        if ($role === 'PPIC') {
            return redirect()->route('show-ppic')->with('success', 'Akun PPIC dihapus.');
        }
        if ($role === 'R&D') {
            return redirect()->route('show-rnd')->with('success', 'Akun R&D dihapus.');
        }
        if ($role === 'Purchasing') {
            return redirect()->route('show-purchasing')->with('success', 'Akun Purchasing dihapus.');
        }
        return redirect()->route('dashboard')->with('success', 'Akun dihapus.');
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/');
    }

    public function profile()
    {
        $user = Auth::user();
        return view('users_management.setting_profile', compact('user'));
    }

    public function updateGeneral(Request $request)
    {
        $request->validate([
            'name'  => ['required', 'min:3'],
            'email' => ['required', 'email', 'unique:users,email,' . Auth::id()],
        ]);

        User::where('id', Auth::id())->update([
            'name'  => $request->name,
            'email' => $request->email,
        ]);

        return redirect()->route('show-profile')->with('success', 'Profil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'new_password'         => ['required', 'min:7'],
            'confirm_new_password' => ['required', 'same:new_password'],
        ]);

        User::where('id', Auth::id())->update([
            'password' => Hash::make($request->new_password),
        ]);

        return redirect()->route('show-profile')->with('success', 'Password diganti.');
    }

    /* =========================================================
     * Alias kompatibilitas lama (opsional)
     * =======================================================*/
    public function showSiswa()                  { return $this->showRND(); }
    public function storeSiswa(Request $r)       { return $this->storeRND($r); }
    public function editSiswa($id)               { return $this->editRND($id); }
    public function updateSiswa(Request $r, $id) { return $this->updateRND($r, $id); }
}
