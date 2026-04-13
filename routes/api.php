<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\{
    AuthController,
    UserController,
    RoleController,
    PermissionController,
		CategoryController,
};

Route::prefix('v1')->group(function () {

	// ══════════════════════════════════════════════════════
	// AUTH
	// ══════════════════════════════════════════════════════

	Route::prefix('auth')->name('auth.')->group(function () {

		// ── PÚBLICAS (sin middleware de autenticación) ───

		Route::middleware('throttle:5,1')->group(function () {
			Route::post('login', [AuthController::class, 'login'])
						->name('login');
		});

		Route::post('register', [AuthController::class, 'register'])
					->name('register');

		Route::post('refresh', [AuthController::class, 'refresh'])
					->name('refresh');

		Route::middleware('throttle:3,1')->group(function () {
				Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
							->name('forgot-password');
		});

		Route::post('reset-password', [AuthController::class, 'resetPassword'])
					->name('reset-password');

		// Verificación de email
		Route::get('verify-email/{id}/{token}', [AuthController::class, 'verifyEmail'])
					->name('verify-email');

		// ── PROTEGIDAS (requieren JWT válido) ────────────

		Route::middleware('auth.jwt')->group(function () {
			Route::post('logout',     [AuthController::class, 'logout'])
						->name('logout');

			Route::post('logout-all', [AuthController::class, 'logoutAll'])
						->name('logout-all');

			Route::get('me',          [AuthController::class, 'me'])
						->name('me');
		});
	});

	// ══════════════════════════════════════════════════════
	// USERS
	// ══════════════════════════════════════════════════════

	Route::middleware('auth.jwt')->prefix('users')->name('users.')->group(function () {

		Route::get('/', [UserController::class, 'index'])
					->middleware('permission:users:read')
					->name('index');

		Route::post('/', [UserController::class, 'store'])
					->middleware('permission:users:create')
					->name('store');

		Route::prefix('{user}')->group(function () {

			Route::get('/', [UserController::class, 'show'])
						->middleware('permission:users:read')
						->name('show');

			Route::put('/', [UserController::class, 'update'])
						->middleware('permission:users:update')
						->name('update');

			Route::delete('/', [UserController::class, 'destroy'])
						->middleware('permission:users:delete')
						->name('destroy');

			Route::post('/activate', [UserController::class, 'activate'])
						->middleware('permission:users:update')
						->name('activate');

			Route::put('/password', [UserController::class, 'changePassword'])
						->name('change-password');

			// ── GESTIÓN DE ROLES DEL USUARIO ──────────────

			Route::post('/roles', [UserController::class, 'assignRole'])
						->middleware('permission:users:assign-roles')
						->name('roles.assign');

			Route::delete('/roles/{role}', [UserController::class, 'revokeRole'])
						->middleware('permission:users:assign-roles')
						->name('roles.revoke');
		});
	});

	// ══════════════════════════════════════════════════════
	// ROLES
	// ══════════════════════════════════════════════════════

	Route::middleware('auth.jwt')->prefix('roles')->name('roles.')->group(function () {

		Route::get('/', [RoleController::class, 'index'])
					->middleware('permission:roles:read')
					->name('index');

		Route::post('/', [RoleController::class, 'store'])
					->middleware('permission:roles:create')
					->name('store');

		Route::prefix('{role}')->group(function () {

			Route::get('/', [RoleController::class, 'show'])
						->middleware('permission:roles:read')
						->name('show');

			Route::put('/', [RoleController::class, 'update'])
						->middleware('permission:roles:update')
						->name('update');

			Route::delete('/', [RoleController::class, 'destroy'])
						->middleware('permission:roles:delete')
						->name('destroy');

			Route::put('/permissions', [RoleController::class, 'syncPermissions'])
						->middleware('permission:roles:update')
						->name('permissions.sync');
		});
	});

	// ══════════════════════════════════════════════════════
	// PERMISOS
	// ══════════════════════════════════════════════════════

	Route::middleware('auth.jwt')->prefix('permissions')->name('permissions.')->group(function () {

		Route::get('/', [PermissionController::class, 'index'])
					->middleware('permission:roles:read')
					->name('index');

		Route::get('/by-module', [PermissionController::class, 'byModule'])
					->middleware('permission:roles:read')
					->name('by-module');
	});

	// ══════════════════════════════════════════════════════
	// Categorias
	// ══════════════════════════════════════════════════════

	Route::middleware('auth.jwt')->prefix('category')->name('category.')->group(function () {
	
		Route::get('/', [CategoryController::class, 'index'])
					->middleware('permission:category:read')
					->name('index');

		Route::post('/', [CategoryController::class, 'store'])
					->middleware('permission:category:create')
					->name('store');

		Route::prefix('{category}')->group(function () {

			Route::get('/', [CategoryController::class, 'show'])
						->middleware('permission:category:read')
						->name('show');

			Route::put('/', [CategoryController::class, 'update'])
						->middleware('permission:category:update')
						->name('update');

			Route::delete('/', [CategoryController::class, 'destroy'])
						->middleware('permission:category:delete')
						->name('destroy');

			Route::post('/activate', [CategoryController::class, 'activate'])
						->middleware('permission:category:update')
						->name('activate');

			Route::get('/subcategories', [CategoryController::class, 'subcategories'])
                    ->middleware('permission:category:read')
                    ->name('subcategories');

		});

	});

		Route::middleware('auth.jwt')->prefix('products')->name('products.')->group(function () {
	
		Route::get('/', [ProductsController::class, 'index'])
					->middleware('permission:products:read')
					->name('index');

		Route::post('/', [ProductsController::class, 'store'])
					->middleware('permission:products:create')
					->name('store');

		Route::prefix('{product}')->group(function () {

			Route::get('/', [ProductsController::class, 'show'])
						->middleware('permission:products:read')
						->name('show');

			Route::put('/', [ProductsController::class, 'update'])
						->middleware('permission:products:update')
						->name('update');

			Route::delete('/', [ProductsController::class, 'destroy'])
						->middleware('permission:products:delete')
						->name('destroy');

			Route::post('/activate', [ProductsController::class, 'activate'])
						->middleware('permission:products:update')
						->name('activate');

		});

	});

});
