
# laravel-model-stages

A Laravel package that add some stage scopes on models

## Installation
### With composer

```bash
composer require NastuzziSamy/laravel-model-stages
```

## Docs

This trait add multiple scopes into model class.
They are all usable directly by calling them (withtout the "scope" behind) when querying for items.
It is usefull for model with parent and children relation in itselft

It is also possible to customize this property:
- `parent_id` to define the parental column

## Usage

In your targeted model:
```php
<?php

namespace \App\Models;

use Illuminate\Database\Eloquent\Model;
use NastuzziSamy\Laravel\Traits\HasStages;

class User extends Model {
    use HasStages;

    /* ... */
}
```

In your targeted controller:
```php
<?php

namespace \App\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Models\User;

class UserController extends Controller {
    /* ... */

    public function index($request) {
        if ($request->filled('stage'))
            $users = User::getStage($request->filled('stage'));
        else if ($request->filled('stages'))
            $users = User::getStages( // `stages=x,y` => params: [x, y]
                ...explode(',', $request->filled('stages'))
            );
        else
            $user = User::get();

        return response->json(
            $users
        );
    }

    /* ... */
}
```

Let think we got a company tree with 1 Boss, 2 Supervisors and 4 Employees

### Example 1: request /api/users?stage=0

Output:
```json
[
    {
        "id": 1,
        "name": "Boss",
        "parent_id": null
    },
]
```

### Example 2: request /api/users?stages=0,1

Output:
```json
[
    {
        "id": 1,
        "name": "Boss",
        "parent_id": null
    },
    {
        "id": 2,
        "name": "Supervisor 1",
        "parent_id": 1
    },
    {
        "id": 3,
        "name": "Supervisor 2",
        "parent_id": 1
    },
]
```

### Example 3: request /api/users?stage=2

Output:
```json
[
    {
        "id": 4,
        "name": "Employee 1",
        "parent_id": 2
    },
    {
        "id": 5,
        "name": "Employee 2",
        "parent_id": 2
    },
    {
        "id": 6,
        "name": "Employee 3",
        "parent_id": 3
    },
    {
        "id": 7,
        "name": "Employee 4",
        "parent_id": 3
    },
]
```

## Changelog
### v1.2.0
- Correct wrong variable
- Add posibility to specify a base

### v1.1.0
- Add flat and tree print option for stages

### v1.0.1
- Correct bad stages
