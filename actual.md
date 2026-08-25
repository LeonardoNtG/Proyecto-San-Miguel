# Estado Actual del Proyecto "San Miguel"

Este documento resume el estado, objetivo y flujo de trabajo del sistema basado en la arquitectura y rutas actuales de la aplicación.

## 1. ¿Qué es y qué hace el proyecto?
El "Proyecto San Miguel" es un **Sistema de Gestión de Ventas Inmobiliarias (Bienes Raíces)**. Está diseñado específicamente para administrar la venta de terrenos (lotes) organizados en bloques y proyectos, gestionar a los clientes que los adquieren y llevar el control financiero de los pagos (abonos) que realizan a lo largo del tiempo.

El sistema funciona como un ERP/CRM interno para la empresa que vende los lotes.

## 2. Roles de Usuario
El sistema cuenta con un control de acceso basado en roles (usando `spatie/laravel-permission`):
*   **Administrador (`admin`):** Tiene control total. Es el único que puede crear/editar/eliminar Proyectos, Bloques, Lotes y Usuarios del sistema.
*   **Usuario estándar (`usuario`):** Probablemente pensado para los vendedores o cajeros. Pueden registrar clientes, hacer ventas y registrar abonos, pero no pueden modificar la estructura física de los lotes ni administrar a otros empleados.

## 3. Flujo Principal del Negocio (Cómo se usa)

El proceso lógico que sigue la aplicación para operar día a día es el siguiente:

### A. Configuración Inicial (Administradores)
1.  **Gestión de Inventario:** El administrador ingresa al sistema y registra la estructura física de los terrenos.
    *   Registra los *Proyectos*.
    *   Dentro de los proyectos, crea *Bloques*.
    *   Dentro de los bloques, crea los *Lotes* individuales (asignándoles medidas, precio, estado, etc.).
2.  **Gestión de Personal:** El administrador crea cuentas para los *Usuarios* (vendedores/cajeros).

### B. Proceso de Venta (Vendedores)
1.  **Registro de Cliente:** Cuando llega un comprador, el usuario lo ingresa al sistema en la sección de Clientes (`/registro`).
2.  **Asignación de Lote (Venta):** El usuario selecciona un Proyecto -> Bloque -> Lote que esté disponible y lo asocia al cliente, registrando la venta.

### C. Proceso Financiero y Cobranza (Cajeros)
1.  **Registro de Abonos:** Los clientes pagan su lote en cuotas. Cuando el cliente realiza un pago, el cajero entra al perfil del cliente (`/abono/{cliente}/registrar`) y registra el dinero recibido.
2.  **Generación de Recibos:** Por cada pago, el sistema puede generar e imprimir un recibo físico o en PDF (`abono/{abono_id}/imprimir`) como comprobante para el cliente.

### D. Control y Reportes (Gerencia)
1.  **Cierre de Caja:** Al final del día (o turno), los usuarios pueden registrar un "Cierre de caja" (`/reportes/cerrar-caja`) para cuadrar el dinero en efectivo/banco recibido con lo registrado en el sistema.
2.  **Reportes Financieros:** El sistema permite exportar reportes detallados en PDF y Excel (`/reportes/financiero`) para ver los ingresos generales, deudas y estado de la cartera.
3.  **Dashboard Gráfico:** Al iniciar sesión (`/dashboard-grafico`), el sistema muestra gráficos visuales con indicadores clave (ventas del mes, lotes disponibles vs vendidos, ingresos, etc.).

## 4. Tecnologías Principales Observadas
*   **Backend:** Laravel (PHP).
*   **Base de datos:** MySQL.
*   **Frontend:** Interfaz web construida probablemente con Blade, compilada mediante Vite (CSS/JS).
*   **Roles y Permisos:** Paquete `Spatie Permission`.
*   **Exportación de Documentos:** Paquete `barryvdh/laravel-dompdf` para recibos y reportes en PDF, y herramientas para Excel.

## 5. Próximos pasos o funcionalidades futuras (Inferidas)
Actualmente el núcleo operativo (Vender lotes y cobrar cuotas) está completo. Los siguientes pasos usuales en este tipo de sistemas, si aún no están implementados, serían:
*   Manejo de mora (cálculo de intereses por pagos atrasados).
*   Módulo de notificaciones (recordatorios de pago por correo o SMS).
*   Contratos autogenerados (generar el documento legal de compra-venta automáticamente en PDF).
