<?php

namespace App\GraphQL\Mutations;

use App\Models\Address;
use Illuminate\Support\Facades\Auth;
use GraphQL\Error\Error;

class UpdateAddress
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

        // If this is set as default, unset all other default addresses
        if (isset($args['is_default']) && $args['is_default']) {
            Address::where('user_id', $user->id)
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        unset($args['id']);
        $address->update($args);

        return $address->fresh();
    }
}
