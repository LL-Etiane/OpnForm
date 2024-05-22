<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Workspace;
use App\Models\User;
use App\Service\WorkspaceHelper;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserInvitationEmail;

class WorkspaceUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function listUsers(Request $request, $workspaceId)
    {
        $workspace = Workspace::findOrFail($workspaceId);
        $this->authorize('view', $workspace);

        return (new WorkspaceHelper($workspace))->getAllUsers();
    }

    public function addUser(Request $request, $workspaceId)
    {
        $workspace = Workspace::findOrFail($workspaceId);
        $this->authorize('workspaceAdmin', $workspace);

        $this->validate($request, [
            'email' => 'required|email',
            'role' => 'required|in:admin,user',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            Mail::to($request->email)->send(new UserInvitationEmail($workspace->name));
            return $this->success([
                'message' => 'Registration invitation email sent to user.'
            ]);
        }

        if ($workspace->users->contains($user->id)) {
            return $this->success([
                'message' => 'User is already in workspace.'
            ]);
        }

        $workspace->users()->sync([
            $user->id => [
                'role' => $request->role,
            ],
        ], false);

        return $this->success([
            'message' => 'User has been successfully added to workspace.'
        ]);
    }

    public function updateUserRole(Request $request, $workspaceId, $userId)
    {
        $workspace = Workspace::findOrFail($workspaceId);
        $user = User::findOrFail($userId);
        $this->authorize('workspaceAdmin', $workspace);

        $this->validate($request, [
            'role' => 'required|in:admin,user',
        ]);

        $workspace->users()->sync([
            $user->id => [
                'role' => $request->role,
            ],
        ], false);

        return $this->success([
            'message' => 'User role changed successfully.'
        ]);
    }

    public function removeUser(Request $request, $workspaceId, $userId)
    {
        $workspace = Workspace::findOrFail($workspaceId);
        $this->authorize('workspaceAdmin', $workspace);

        $workspace->users()->detach($userId);

        return $this->success([
            'message' => 'User removed from workspace successfully.'
        ]);
    }

    public function leaveWorkspace(Request $request, $workspaceId)
    {
        $workspace = Workspace::findOrFail($workspaceId);
        $this->authorize('view', $workspace);

        $workspace->users()->detach($request->user()->id);

        return $this->success([
            'message' => 'You have left the workspace successfully.'
        ]);
    }
}
