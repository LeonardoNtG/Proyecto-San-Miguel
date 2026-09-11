# Estado Actual y Guía del Proyecto San Miguel (Sistema AMSA)

Este documento resume el objetivo, arquitectura y estado del sistema para que cualquier desarrollador pueda integrarse y continuar el desarrollo.

---

## 1. ¿Qué es el Sistema?
El **Sistema AMSA** es una plataforma ERP/CRM especializada en la gestión de bienes raíces y urbanizaciones. Permite:
- Administrar inventarios de terrenos organizados por Proyectos (Lotificaciones), Bloques y Lotes individuales.
- Registrar ventas a plazos (con generación de tablas de amortización) y de contado.
- Cobrar cuotas, calcular mora y generar recibos oficiales en PDF.
- Cuadrar cajas diarias y conciliar pagos bancarios (transferencias y depósitos).
- Proveer a los clientes un portal web público con token para consultar su estado de cuenta.

---

## 2. Tecnologías y Stack
- **Backend:** PHP 8.x + Laravel
- **Base de Datos:** MySQL (InnoDB)
- **Frontend:** Blade Templates, CSS/JS Vanilla, Vite
- **Seguridad & Roles:** `spatie/laravel-permission` (`admin`, `usuario`)
- **Documentos & Reportes:** `barryvdh/laravel-dompdf` (Recibos, Estados de Cuenta, Cierre de Caja) y `maatwebsite/excel`
- **CI/CD & Hosting:** GitHub Actions con despliegue FTP a BanaHosting en la rama `Production`

---

## 3. Puntos Clave de la Arquitectura
1. **Multi-Tenant (Multi-Lotificación):** Utiliza el trait `\App\Traits\ScopedByLotificacion` en modelos (`Lote`, `Bloque`, `Venta`, `Cliente`, etc.) para filtrar automáticamente los datos según la lotificación seleccionada en sesión.
2. **Motor de Amortización:** El método `\App\Http\Controllers\AbonoController::recalcularCuotas($id_venta)` es el responsable de regenerar y actualizar los estados de las cuotas (`Pagada`, `Parcial`, `Pendiente`, `Mora`) basándose en los abonos registrados.
3. **Módulo de Parámetros:** Disponible en `/configuracion/parametros` para habilitar o deshabilitar opciones operativas como la visualización del traspaso de contratos.
4. **Mantenimiento en Producción:** El script `limpiar.php` (y su réplica en `public/limpiar.php`) permite ejecutar migraciones, limpiar caché y auditar lotes en vivo (`https://proyectosanmiguel.com/AMSAsystem/limpiar.php`).

---

## 4. Estado de los Proyectos e Inventario
- **Lotificación La Campana:** 608 lotes verificados y auditados (Bloques A a la Y).
- **Lotificación Colinas Santa Clara:** 385 lotes.
