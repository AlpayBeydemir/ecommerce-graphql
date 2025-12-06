<?php

namespace App\GraphQL\Mutations;

use App\Models\Address;
use Illuminate\Support\Facades\Auth;
use GraphQL\Error\Error;

class CreateAddress
{
    public function __invoke($rootValue, array $args)
    {
        $user = Auth::user();

        if (!$user) {
            throw new Error('Unauthenticated');
        }

        // If this is set as default, unset all other default addresses
        if (isset($args['is_default']) && $args['is_default']) {
            Address::where('user_id', $user->id)
                ->update(['is_default' => false]);
        }

        $address = Address::create(array_merge($args, [
            'user_id' => $user->id,
        ]));

        return $address;
    }
}
