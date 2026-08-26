# ESTADO DEL PROYECTO SAN MIGUEL

## 1. Estado actual
El sistema ha evolucionado de un MVP básico a un ERP inmobiliario más maduro. Se han implementado con éxito la multi-lotificación, el manejo de planes de pago (cuotas con simulación de mora), un portal público para clientes, el historial de rescisiones y los cierres de caja diarios. 

## 2. Stack tecnológico
- **Laravel:** v9.19
- **PHP:** ^8.0.2
- **Roles:** spatie/laravel-permission v5.7
- **Frontend:** Vite v4.0, Blade
- **PDF:** barryvdh/laravel-dompdf v3.1

## 3. Módulos existentes

| Módulo         | Estado                     | Observaciones |
| -------------- | -------------------------- | ------------- |
| Usuarios       | Completo                   | CRUD básico, control por lotificación. |
| Lotificaciones | Completo                   | Relaciones y scopes globales. Selector de contexto en UI. |
| Bloques        | Completo                   | Conectado a Lotificaciones, ScopeGlobal activo. |
| Lotes          | Completo                   | Incluye historial de estados y rescisiones (Fase 2). |
| Clientes       | Completo                   | CRUD operativo. Portal Público agregado (Fase 6). |
| Ventas         | Completo                   | Lógica de generación automática de Cuotas implementada. |
| Reservas       | Completo                   | Flujo de reservas con vigencia y formalización a Venta (Fase 3). |
| Cuotas y Mora  | Completo                   | Generación, simulación y cobro de mora (5%) automatizado (Fases 4 y 5). |
| Abonos         | Completo                   | Los abonos aplican a cuotas, validando mora y capital. |
| Cierre de Caja | Completo                   | Módulo financiero filtrado por fecha y método de pago (Fase 7). |
| Egresos        | Parcial                    | Existe como "Salidas". |
| Auditoría      | **Pendiente**              | No hay bitácora de acciones críticas o logs de movimientos. |

## 4. Funcionalidades terminadas (Roadmap Completado)
- **Fase 1:** Arquitectura Multi-Lotificación (Contexto de acceso).
- **Fase 2:** Restructuración de Venta-Lote para soportar historial de rescisiones.
- **Fase 3:** Flujo de Reservas (creación, anulación, formalización a venta).
- **Fase 4:** Plan de Pagos / Cuotas automatizadas al vender.
- **Fase 5:** Sistema de Mora (simulación al vuelo y cobro priorizado).
- **Fase 6:** Portal Cliente (Enlaces públicos con token para ver estado de cuenta).
- **Fase 7:** Contabilidad y Cierre de Caja (Separación de concepto de pago y método/banco destino).

## 5. Próximo paso recomendado
Con las fases principales de negocio completadas, las siguientes etapas podrían enfocarse en:
1. **Auditoría (Bitácoras):** Registrar quién hizo qué (ej. "Admin rescindió el contrato #5", "Usuario X exoneró la mora de Y").
2. **Módulo de Egresos Avanzado:** Integrar las salidas de dinero a los reportes financieros para obtener ganancias netas reales.
3. **Roles Granulares:** Asegurar que los vendedores/usuarios normales no puedan ver o modificar Cierres de Caja o configuraciones.
