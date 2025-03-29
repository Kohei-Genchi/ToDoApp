<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Category;
use Illuminate\View\View;

class SharedTeamMembersController extends Controller
{
    /**
     * Display team members based on shared categories
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Get categories shared with current user
        $sharedWithMe = Auth::user()
            ->sharedCategories()
            ->with("user") // Include the owner of the category
            ->get();

        // Get categories that current user has shared with others
        $mySharedCategories = Category::where("user_id", Auth::id())
            ->with("sharedWith") // Include users with whom the category is shared
            ->whereHas("sharedWith") // Only include categories that are actually shared
            ->get();

        // Get unique team members (both directions of sharing)
        $teamMembers = collect();

        // Add users who shared categories with me
        foreach ($sharedWithMe as $category) {
            if (!$teamMembers->contains("id", $category->user_id)) {
                $teamMembers->push([
                    "id" => $category->user_id,
                    "name" => $category->user->name,
                    "email" => $category->user->email,
                    "relationship" => "shared_with_me",
                    "categories" => [$category->name],
                ]);
            }
        }

        // Add users with whom I've shared categories
        foreach ($mySharedCategories as $category) {
            foreach ($category->sharedWith as $user) {
                $existingMember = $teamMembers->firstWhere("id", $user->id);

                if ($existingMember) {
                    // Add category to existing member if not already present
                    if (
                        !in_array(
                            $category->name,
                            $existingMember["categories"]
                        )
                    ) {
                        $existingMember["categories"][] = $category->name;
                    }
                } else {
                    $teamMembers->push([
                        "id" => $user->id,
                        "name" => $user->name,
                        "email" => $user->email,
                        "relationship" => "shared_by_me",
                        "categories" => [$category->name],
                    ]);
                }
            }
        }

        return view("tasks.team-members", [
            "teamMembers" => $teamMembers,
            "sharedWithMe" => $sharedWithMe,
            "mySharedCategories" => $mySharedCategories,
        ]);
    }
}
