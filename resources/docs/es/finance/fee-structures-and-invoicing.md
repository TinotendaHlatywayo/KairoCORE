---
title: Crear estructuras de tarifas y emitir facturas
summary: Defina lo que paga cada formulario, luego genere facturas individualmente o con el motor de facturación automática.
category: finance
slug: fee-structures-and-invoicing
order: 1
published: true
updated: 2026-10-27
---

# Crear estructuras de tarifas y emitir facturas

La facturación de tarifas en el espacio de trabajo tiene dos partes: **estructuras de tarifas** (lo que debe cada formulario o grado) y **facturas de estudiantes** (la factura real por estudiante). Ambos se encuentran en **Finanzas → Facturación e ingresos para estudiantes**.

## 1. Estructuras de tarifas

1. Abra **Finanzas → Facturación e ingresos para estudiantes → Estructuras de tarifas**.
2. Crea una estructura: elige el curso académico, la modalidad/grado al que aplica y el importe.
3. Agregue categorías de tarifas (matrícula, impuestos, actividades) si detalla los cargos: **Categorías de tarifas** está en el mismo grupo.

Las estructuras impulsan el motor de facturación automática y el selector de tarifas del formulario de factura.

## 2. Emitir una factura manualmente

1. Abra **Finanzas → Facturación e ingresos de estudiantes → Facturas**.
2. Haga clic en **Crear**.
3. Elija el **estudiante**, **año académico** y **período**. El formulario filtra las estructuras de tarifas disponibles para aquellas que coincidan con el año del estudiante y el año seleccionado.
4. Seleccione las estructuras de tarifas a incluir. Los totales se calculan automáticamente; se aplica una exención de tarifa (si está configurada) como descuento.
5. Ahorrar. La factura se crea con el estado **No pagado** y un número de factura único.

## 3. Ejecute el motor de facturación automática.

Para toda una cohorte a la vez:

1. En la tabla de facturas, haga clic en **Ejecutar motor de facturación automática**.
2. Elija el objetivo de facturación (qué formas/calificaciones y plazo).
3. El motor crea facturas para cada estudiante elegible que aún no tenga una factura abierta para ese alcance.

Esta es la forma más rápida de facturar un grupo anual el primer día del trimestre.

## 4. Documentos

Cada factura se puede imprimir (**Imprimir factura** en la fila). El diseño de facturas y recibos (logotipo, colores, canales de pago, notas) se controla en **Finanzas → Contabilidad principal y configuración → Plantillas de documentos**.

## 5. Acepta pagos

Registre efectivo, transferencias y dinero móvil en la factura e imprima recibos; consulte [Registrar pagos](/docs/finance/recording-payments). Las familias también pueden pagar a través de **Pagar mediante Paynow** y enviar **Pruebas de pago** para su aprobación.
