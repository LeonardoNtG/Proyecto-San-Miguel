# ESTADO DEL PROYECTO SAN MIGUEL

## 1. Estado actual
El sistema se encuentra en una etapa operativa básica, sirviendo como un MVP donde se pueden registrar bloques, lotes, clientes, ventas y abonos. Sin embargo, su estructura actual no soporta características de un ERP maduro como multi-lotificación, planes de pago (cuotas), historial de rescisiones ni seguridad granular por lotificación. Hemos completado la auditoría inicial (FASE 0).

## 2. Stack tecnológico
- **Laravel:** v9.19
- **PHP:** ^8.0.2
- **Roles:** spatie/laravel-permission v5.7
- **Frontend:** Vite v4.0, Blade
- **PDF:** barryvdh/laravel-dompdf v3.1

## 3. Arquitectura
Monolito MVC tradicional en Laravel. Autenticación web estándar. Rutas separadas para web y algunas para API (para carga dinámica de selectores). El diseño de base de datos actual presenta limitaciones estructurales para el escalamiento requerido (ej. "proyecto" es un campo de texto, lote pertenece directamente a venta sobrescribiendo historial).

## 4. Módulos existentes

| Módulo         | Estado                     | Observaciones |
| -------------- | -------------------------- | ------------- |
| Usuarios       | Parcial                    | CRUD básico, agregado control por lotificación. |
| Lotificaciones | Completo                   | Migrado de varchar a tabla formal, relaciones y scopes globales. |
| Bloques        | Parcial                    | Conectado a Lotificaciones, ScopeGlobal activo. |
| Lotes          | Parcial                    | Falta historial de estados y rescisiones. |
| Clientes       | Completo                   | CRUD operativo. |
| Ventas         | Parcial                    | Conectado a Lotificaciones. Faltan cuotas. |
| Reservas       | **Pendiente**              | Inexistente. |
| Cuotas         | **Pendiente**              | Inexistente. |
| Abonos         | Parcial                    | Funciona, pero se abona a la venta, no a cuotas específicas. |
| Cobranza       | **Pendiente**              | Faltan estados de cuenta avanzados y control de mora. |
| Caja           | Parcial                    | Existe cierre de caja y salidas, requiere integración a flujo formal. |
| Egresos        | Parcial                    | Existe como "Salidas". |
| Reportes       | Parcial                    | Existen reportes financieros básicos (Excel/PDF). |
| Auditoría      | **Pendiente**              | No hay bitácora de acciones críticas. |

## 5. Base de datos
**Tablas principales actuales:** `users`, `clientes`, `lotificaciones`, `lotificacion_user`, `bloques`, `lotes`, `ventas`, `abonos`, `salidas`, `cierre_cajas`.
**Limitaciones detectadas (Pendientes):**
- `lotes.id_venta` sobrescribe el historial en caso de reventa.

## 6. Roles y permisos
Existen roles `admin` y `usuario`. Ya se configuró el control y contexto (middleware) de acceso por Lotificación.

## 7. Flujo de negocio
Actualmente lineal: Bloque -> Lote -> Cliente -> Venta -> Abonos. Carece de flujos de Reserva y Rescisión.

## 8. Historial y auditoría
No implementado de forma formal. 

## 9. Funcionalidades terminadas
- Autenticación básica.
- CRUD de Clientes, Bloques y Usuarios.
- Registro directo de Ventas.
- Registro directo de Abonos.
- Arquitectura Multi-Lotificación (Contexto de acceso y vistas por lotificación permitida).

## 10. Funcionalidades en desarrollo
- N/A

## 11. Pendientes
1. Restructuración de Venta-Lote para soportar historial (Fase 2).
2. Implementación de Cuotas y Reservas (Fase 3).

## 12. Problemas conocidos
- Pérdida de historial de ventas en lotes al reasignar `id_venta`.
- Abonos sin asignación a cuotas (dificulta el cálculo de mora).

## 13. Últimos cambios
* 2026-08-24: Implementación de la Fase 1. Se creó tabla `lotificaciones`, se vinculó a `bloques` y `ventas` preservando datos, y se añadió Middleware y Global Scope para controlar accesos.

## 14. Último punto de trabajo
**El proyecto quedó en:** Finalización de la Fase 1 (Arquitectura Multi-Lotificación). Las bases de datos y middleware están configurados.

## 15. Próximo paso recomendado
Actualizar las Vistas del Frontend para inyectar el selector de Lotificaciones Activas (Switch de contexto) y luego proceder a la **Fase 2** (Mejora de Lotes e Historial).
