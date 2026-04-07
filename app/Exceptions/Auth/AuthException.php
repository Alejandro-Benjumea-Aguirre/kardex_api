<?php declare(strict_types=1);

namespace App\Exceptions\Auth;

// ═══════════════════════════════════════════════════════════
// EXCEPCIONES DEL DOMINIO AUTH
//
// CONCEPTO: ¿Por qué excepciones propias en vez de genéricas?
// ═══════════════════════════════════════════════════════════
//
// throw new \Exception('Credenciales inválidas') → genérica
//   ❌ El Handler de Laravel no sabe qué HTTP status devolver
//   ❌ No podés catchear solo este tipo en un try/catch
//   ❌ No documenta los posibles errores del sistema
//
// throw new InvalidCredentialsException() → específica
//   ✅ El Handler sabe: esto es 401 Unauthorized
//   ✅ Podés catch (InvalidCredentialsException $e) específicamente
//   ✅ Cada excepción documenta un escenario de error del dominio
//   ✅ Podés agregar lógica específica (logging, alertas) por tipo
//
// REGISTRO EN Handler:
//   En app/Exceptions/Handler.php render():
//   if ($e instanceof InvalidCredentialsException) {
//       return response()->json(['error' => $e->getMessage()], 401);
//   }
// ═══════════════════════════════════════════════════════════

class AuthException extends \RuntimeException {}
