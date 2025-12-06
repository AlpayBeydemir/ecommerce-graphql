<?php

namespace App\GraphQL\Mutations;

use App\Models\Address;
use Illuminate\Support\Facades\Auth;
use GraphQL\Error\Error;

class DeleteAddress
{
    public function __invoke($rootValue, array $args)
    {
        $user = Auth::user();

        if (!$user) {
            throw new Error('Unauthenticated');
        }

        $address = Address::where('id', $args['id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$address) {
            throw new Error('Address not found or you do not have permission');
        }

        $address->delete();

        return [
            'message' => 'Address deleted successfully',
            'success' => true,
        ];
    }
}
