<?php

declare(strict_types=1);

/*
| The language of the store is "es_SV", but the language of an application is
| whatever "config('app.locale')" says, and the translator looks for a folder
| with that exact name. Without this file an application with the locale
| "es_SV" would fall back to the English texts.
*/

return [

    'title' => 'Administración',

    'dashboard' => [
        'title' => 'Panel',
        'description' => 'Lo que está pasando en la tienda ahora mismo.',
        'orders_today' => 'Pedidos de hoy',
        'orders_awaiting' => 'Pedidos por preparar',
        'orders_unpaid' => 'Pedidos sin pagar',
        'products' => 'Productos',
        'low_stock' => 'Pocas existencias',
    ],

    'orders' => [
        'title' => 'Pedidos',
        'search' => 'Busca por número, nombre o correo',
        'all_statuses' => 'Todos los estados',
        'empty' => 'No hay pedidos que respondan a la búsqueda.',
        'not_found' => 'El pedido no existe.',
        'back' => 'Volver a los pedidos',
        'number' => 'Número',
        'customer' => 'Cliente',
        'date' => 'Fecha',
        'total' => 'Total',
        'status' => 'Estado',
        'payment' => 'Pago',
        'items' => 'Ítems',
        'item' => 'Ítem',
        'quantity' => 'Cantidad',
        'price' => 'Precio',
        'line_total' => 'Subtotal',
        'subtotal' => 'Subtotal',
        'discount' => 'Descuento',
        'tax' => 'Impuesto',
        'shipping' => 'Envío',
        'buyer' => 'Comprador',
        'email' => 'Correo',
        'phone' => 'Teléfono',
        'actions' => 'Acciones',
        'timeline' => 'Historial',
        'moved' => 'El pedido ahora está :status.',
    ],

    'products' => [
        'title' => 'Productos',
        'search' => 'Busca por nombre',
        'empty' => 'No hay productos que respondan a la búsqueda.',
        'name' => 'Nombre',
        'sku' => 'SKU',
        'price' => 'Precio',
        'stock' => 'Existencias',
        'status' => 'Estado',
    ],

    'customers' => [
        'title' => 'Clientes',
        'search' => 'Busca por nombre, correo o teléfono',
        'empty' => 'No hay clientes que respondan a la búsqueda.',
        'name' => 'Nombre',
        'email' => 'Correo',
        'phone' => 'Teléfono',
        'orders' => 'Pedidos',
    ],

    'settings' => [
        'title' => 'Ajustes de la tienda',
        'description' => 'Los datos que la tienda le muestra a sus compradores.',
        'name' => 'Nombre',
        'legal_name' => 'Razón social',
        'tax_id' => 'NIT',
        'email' => 'Correo',
        'phone' => 'Teléfono',
        'save' => 'Guardar los ajustes',
        'saved' => 'Se guardaron los ajustes de la tienda.',
    ],

    'roles' => [
        'title' => 'Roles',
        'description' => 'Los roles del personal de la tienda y los permisos que dan.',
        'name' => 'Nombre',
        'slug' => 'Slug',
        'permissions' => 'Permisos',
        'staff' => 'Personal',
    ],

    'login' => [
        'title' => 'Entrar',
        'email' => 'Correo',
        'password' => 'Contraseña',
        'remember' => 'Recordarme',
        'submit' => 'Entrar',
        'failed' => 'Esas credenciales no coinciden con ningún usuario.',
    ],

    'setup' => [
        'title' => 'Falta un paso',
        'body' => 'Los roles del personal de la tienda viven en los usuarios de la aplicación, así que el modelo del usuario tiene que llevar el trait de los roles.',
        'trait' => 'Agrega el trait al modelo del usuario de la aplicación:',
        'roles' => 'Luego siembra los roles de la tienda:',
    ],

    'pagination' => [
        'label' => 'Paginación',
        'previous' => 'Anterior',
        'next' => 'Siguiente',
    ],

];
