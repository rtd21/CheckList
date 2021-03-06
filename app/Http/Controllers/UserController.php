<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUser;
use App\Models\CheckList;
use App\Models\Permission;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpParser\Node\Expr\BinaryOp\Equal;

class UserController extends Controller
{

    public function index()
    {
        $this->authorize('look', User::class);
        $users = User::where('id', '!=', Auth::id())->get();
        return view('users/users', [
            'users' => $users
        ]);
    }

    public function edit($id)
    {
        $this->authorize('edit', User::class);
        return view('users/edit', [
            'user' => User::find($id)
        ]);
    }

    public function update(UpdateUser $request, $id)
    {
        $user = User::find($id);
        $validated = $request->validated();
        $count = $validated['count'];
        $user->count = $count;
        $user->save();
        $oldCount = CheckList::where('user_id', $user->id)->count();
        CheckList::where('user_id', $user->id)->orderBy('id', 'desc')->limit($oldCount - $count)->delete();
        return redirect()->route('users.index');
    }

    public function block(Request $request, $id)
    {
        $user = User::find($id);
        $this->authorize('block', User::class);
        $user->status = 'blocked';
        $user->save();
        return redirect()->route('users.index');
    }

    public function unblock(Request $request, $id)
    {
        $user = User::find($id);
        $this->authorize('block', User::class);
        $user->status = 'active';
        $user->save();
        return redirect()->route('users.index');
    }

    public function addPermission(Request $request, $id)
    {
        $user = User::with('permissions')->find($id);
        return view('users.permit', [
            'user' => $user,
            'permissions' => Permission::all()
        ]);
    }

    public function permit(Request $request, $id)
    {
        $user = User::with('permissions')->find($id);
        $this->authorize('permit', User::class);
        $permission = Permission::find($request->permission);
        foreach ($user->permissions as $perm) {
            if (Permission::equals($perm, $permission)) {
                return back();
            }
        }
        $user->permissions()->save($permission);
        return back();
    }

    public function forbid(Request $request, $user_id, $perm_id)
    {
        $user = User::with('permissions')->find($user_id);
        $this->authorize('permit', User::class);
        $user->permissions()->detach($perm_id);
        return back();
    }
}
