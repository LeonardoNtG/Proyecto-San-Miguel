# 📌 Estado Detallado del Proyecto - Sistema AMSA (Proyecto San Miguel)

*Documento de seguimiento y contexto técnico para desarrolladores.*  
*Última actualización: Septiembre 2026*

---

## 1. Resumen Ejecutivo
El sistema **AMSA** es una solución web para la gestión de bienes raíces, urbanizaciones y loteamiento. Se encuentra actualmente en producción en **BanaHosting** bajo la rama `Production` de GitHub.

---

## 2. Estado de Módulos

| Módulo | Estado | Descripción & Notas Técnicas |
| :--- | :--- | :--- |
| **Multi-Lotificación** | ✅ Completo | Implementado mediante Scopes Globales (`ScopedByLotificacion`) y selector de proyecto en sesión. |
| **Inventario (Lotes y Bloques)** | ✅ Completo | Soporte para medidas en $m^2$ y $vrs^2$. Inventario auditado (608 lotes en La Campana, 385 en Colinas Santa Clara). |
| **Clientes** | ✅ Completo | Gestión de expedientes, historial de compras, documentos y generación de tokens para portal público. |
| **Contratos / Ventas** | ✅ Completo | Soporte de contratos de un solo lote y multilotes. Generación automatizada del plan de cuotas. |
| **Abonos y Cobranza** | ✅ Completo | Métodos de pago (Efectivo, Transferencia, Depósito), conciliación de cuentas bancarias y recibos firmados. |
| **Recibos y PDFs** | ✅ Completo | Emisión de comprobantes en PDF (formato estándar y térmico), conversión de montos a letras y marca de agua sutil (30% opacidad). |
| **Cierre de Caja** | ✅ Completo | Apertura, balance diario por turnos y reportes financieros con exportación a PDF y Excel. |
| **Rescisiones y Reasignaciones**| ✅ Completo | Historial de rescisiones con liberación de inventario y reasignación de contratos. |
| **Parámetros del Sistema** | ✅ Completo | Módulo dinámico en `/configuracion/parametros` (permite ocultar/mostrar opciones como Traspasos). |
| **Portal Público de Clientes** | ✅ Completo | Acceso sin login mediante token seguro para consulta de saldos y cuotas. |
| **Diagnóstico en Servidor** | ✅ Completo | Script optimizado `limpiar.php` para limpieza de caché, migraciones y auditoría en BanaHosting. |

---

## 3. Convenciones y Reglas de Desarrollo

1. **Recálculo de Cuotas:** Siempre que se inserte, edite o elimine un Abono o Venta, se debe invocar `\App\Http\Controllers\AbonoController::recalcularCuotas($id_venta)`.
2. **Consultas sin Scope de Lotificación:** Cuando se requiera acceder a datos globales o de migración administrativa, utilizar `withoutGlobalScopes()` o `withoutGlobalScope('lotificacion')`.
3. **Control de Cambios y Despliegues:**
   - La rama principal de producción es `Production`.
   - Cada push a `Production` dispara el workflow de GitHub Actions que sube los cambios por FTP.
   - Después de cada despliegue con cambios de base de datos o vistas, acceder a `https://proyectosanmiguel.com/AMSAsystem/limpiar.php`.
4. **Sincronización de Diagnóstico:** `limpiar.php` en la raíz y `public/limpiar.php` deben mantenerse idénticos.
