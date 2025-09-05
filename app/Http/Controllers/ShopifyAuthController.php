<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ShopifyService;
use Illuminate\Support\Facades\Log;

class ShopifyAuthController extends Controller
{
    public function showConnect()
    {
        if (!session('authenticated')) {
            return redirect()->route('login');
        }
        
        return view('shopify.connect');
    }

    public function redirectToShopify(Request $request)
    {
        if (!session('authenticated')) {
            return redirect()->route('login');
        }

        // Si viene por POST (desde formulario), validar shop
        if ($request->isMethod('POST')) {
            $request->validate([
                'shop' => 'required|string|regex:/^[a-z0-9\-]+$/'
            ]);
            $shop = $request->input('shop');
        } else {
            // Si viene por GET, usar la tienda por defecto del .env
            $defaultShopDomain = config('services.shopify.shop_domain');
            if (!$defaultShopDomain) {
                return redirect()->route('shopify.connect')
                    ->withErrors(['error' => 'Tienda no configurada. Ingresa el nombre de tu tienda.']);
            }
            $shop = str_replace('.myshopify.com', '', $defaultShopDomain);
        }

        $apiKey = config('services.shopify.api_key');
        $scopes = config('services.shopify.scopes');
        $redirectUri = route('shopify.callback');

        if (!$apiKey) {
            return back()->withErrors(['error' => 'API Key de Shopify no configurada. Revisa tu archivo .env']);
        }

        // Guardar shop en sesión para el callback
        session(['shopify_shop' => $shop]);

        $authUrl = ShopifyService::buildAuthUrl($shop, $apiKey, $scopes, $redirectUri);
        
        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        if (!session('authenticated')) {
            return redirect()->route('login');
        }

        $code = $request->input('code');
        $shop = session('shopify_shop');
        $state = $request->input('state');

        // Validar que tenemos un state (Shopify no requiere validación específica para apps de distribución personalizada)

        if (!$code || !$shop) {
            return redirect()->route('shopify.connect')
                ->withErrors(['error' => 'Código de autorización o shop faltante']);
        }

        try {
            $shopifyService = new ShopifyService();
            $accessToken = $shopifyService->exchangeCodeForToken($shop, $code);

            if (!$accessToken) {
                throw new \Exception('No se pudo obtener el token de acceso');
            }

            // Guardar configuración en sesión (en producción esto iría a BD)
            session([
                'shopify_connected' => true,
                'shopify_shop_domain' => $shop . '.myshopify.com',
                'shopify_access_token' => $accessToken
            ]);

            // Limpiar shop temporal
            session()->forget('shopify_shop');

            return redirect()->route('dashboard')
                ->with('success', "¡Conectado exitosamente a {$shop}.myshopify.com!");

        } catch (\Exception $e) {
            Log::error('Shopify OAuth Error: ' . $e->getMessage());
            return redirect()->route('shopify.connect')
                ->withErrors(['error' => 'Error al conectar con Shopify: ' . $e->getMessage()]);
        }
    }

    public function disconnect()
    {
        if (!session('authenticated')) {
            return redirect()->route('login');
        }

        session()->forget(['shopify_connected', 'shopify_shop_domain', 'shopify_access_token']);
        
        return redirect()->route('dashboard')
            ->with('success', 'Desconectado de Shopify exitosamente');
    }
}