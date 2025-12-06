<?php

namespace App\GraphQL\Mutations;

use App\Models\Address;
use Illuminate\Support\Facades\Auth;
use GraphQL\Error\Error;

class SetDefaultAddress
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

        // Unset all other default addresses
        Address::where('user_id', $user->id)
            ->update(['is_default' => false]);

        // Set this address as default
        $address->update(['is_default' => true]);

        return $address->fresh();
    }
}
