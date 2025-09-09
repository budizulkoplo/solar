<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Spatie\Image\Image;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
     public function upload(Request $request){
        $fileName='';
        $request->validate(['docfile' => 'required|mimes:jpg,jpeg','path'=>'required']);
        $file=$request->file('docfile');
        if ($file->isValid()) {
            $fileName = md5($request->id). '.' . $file->extension();
            $savePath = storage_path('app/private/img/'.$request->path.'/');
            //$savePath = public_path('storage/uploads/profile/') . $fileName;
            //$savePaththumb = public_path('storage/uploads/profile/thumb/') . $fileName;
            
            // Hapus file lama jika sudah ada
            if (File::exists($savePath . $fileName)) {
                File::delete($savePath . $fileName);
            }

            // if (File::exists($savePaththumb)) {
            //     File::delete($savePaththumb);
            // }

            // Kompres gambar dan batasi ukuran file maksimal 2MB
            $image = Image::load($file->path())
            ->width(2000)
            ->optimize()
            ->save($savePath . $fileName);

            // Image::load($file->path())
            // ->width(100)
            // ->optimize()
            // ->save($savePaththumb);
            
            // Cek ukuran file, jika lebih dari 2MB, kompres ulang
            while (filesize($savePath . $fileName) > 2 * 1024 * 1024) {
                $image->save($savePath . $fileName, 80); // Atur kualitas, turunkan bertahap
            }
            if($image){
                $emp = User::find($request->id);
                $emp->foto = $fileName;
                $emp->save();
            }

            return response()->json($fileName,200);
        }
    }
}
