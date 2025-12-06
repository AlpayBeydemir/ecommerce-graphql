<?php

namespace App\GraphQL\Mutations;

use Illuminate\Support\Facades\Auth;
use GraphQL\Error\Error;

class UpdateProfile
{
    public function __invoke($rootValue, array $args)
    {
        $user = Auth::user();

        if (!$user) {
            throw new Error('Unauthenticated');
        }

        $updateData = [];

        if (isset($args['name'])) {
            $updateData['name'] = $args['name'];
        }

        if (isset($args['email'])) {
            // Check if email is already taken by another user
            $existingUser = \App\Models\User::where('email', $args['email'])
                ->where('id', '!=', $user->id)
                ->first();

            if ($existingUser) {
                throw new Error('Email already taken');
            }

            $updateData['email'] = $args['email'];
        }

        $user->update($updateData);

        return $user->fresh();
    }
}
