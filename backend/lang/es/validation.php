<?php

return [
    'accepted' => 'El campo :attribute debe ser aceptado.',
    'array' => 'El campo :attribute debe ser un arreglo.',
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'confirmed' => 'La confirmacion de :attribute no coincide.',
    'email' => 'El campo :attribute debe ser un correo electronico valido.',
    'exists' => 'El valor seleccionado para :attribute no es valido.',
    'integer' => 'El campo :attribute debe ser un numero entero.',
    'max' => [
        'string' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'array' => 'El campo :attribute no debe tener mas de :max elementos.',
    ],
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'array' => 'El campo :attribute debe tener al menos :min elementos.',
    ],
    'numeric' => 'El campo :attribute debe ser un numero.',
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'unique' => 'El valor de :attribute ya esta en uso.',

    'attributes' => [
        'email' => 'correo',
        'is_active' => 'estado',
        'must_change_password' => 'cambio de contrasena',
        'name' => 'nombre',
        'password' => 'contrasena',
        'permission_overrides' => 'permisos personalizados',
        'role_ids' => 'roles',
    ],
];
