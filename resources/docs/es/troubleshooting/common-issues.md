---
title: Solución de problemas comunes
summary: Soluciones para los problemas que más afectan a las escuelas: problemas de inicio de sesión, estudiantes perdidos, boletas de calificaciones en blanco y entrega de correo electrónico.
category: troubleshooting
slug: common-issues
order: 1
published: true
updated: 2026-10-27
---

# Solución de problemas comunes

Comprobaciones rápidas de los problemas que surgen con más frecuencia. Si algo aquí no lo resuelve, comuníquese con el soporte a través del sitio web de la plataforma.

## no puedo iniciar sesión

- Confirma que estás en la URL del espacio de trabajo de tu escuela (por ejemplo, `your-school.kairo.co.zw/workspace`), no en la dirección de la plataforma.
- Utilice **Olvidé mi contraseña** en la página de inicio de sesión para restablecerla.
- Si nunca recibió el correo electrónico de activación, verifique el correo no deseado y luego solicite a un administrador que lo reenvíe desde **Administración del sistema → Administración de usuarios → Cuentas de usuario**.

## Un estudiante no aparece en las listas.

- Consulte la tabla **filtros**: un formulario sobrante o un filtro de año oculta a los estudiantes que de otro modo coincidirían.
- Busque por número de admisión o número de identificación de estudiante en lugar de nombre (la ortografía difiere más de lo que imagina).
- Si el estudiante fue admitido a través de Aplicaciones, confirme que la solicitud realmente alcanzó el estado de admisión.

## El motor de facturación automática no creó nada

- Verifique que los estudiantes tengan una **inscripción** (año académico + formulario): la facturación se centra en las inscripciones, no en filas de estudiantes desnudas.
- Verifique que existan **estructuras de tarifas** para ese formulario y año académico.
- Si ya existen facturas para ese ámbito y aún no se han pagado, el motor omite a esos estudiantes a propósito para evitar la doble facturación.

## Las boletas de calificaciones se imprimen con marcas de asunto vacías

- Confirme que **las calificaciones fueron capturadas** en comparación con la evaluación para ese período y materia.
- Confirme que los informes se **generaron después** de ingresar las calificaciones; vuelva a generarlos si las calificaciones llegaron más tarde.
- Compruebe que está imprimiendo el **término** correcto.

## Los correos electrónicos de anuncios no llegaron

- Verifique que el **Canal de entrega** sea **Solo correo electrónico** o **Sistema y correo electrónico**, no solo el Sistema.
- Confirme que la audiencia realmente incluye a los destinatarios (la segmentación basada en roles coincide con el almacenamiento de roles real).
- Revisa la carpeta de spam de la escuela; pídale a TI que permita el dominio de envío de la plataforma.

## Un pago está en la factura incorrecta.

- Registre los pagos solo a través de **Registrar pago** en la fila de la factura, lo que mantiene los saldos y los recibos consistentes.
- Si se capturó un pago con una factura incorrecta, comuníquese con el soporte en lugar de editar la base de datos: el historial de pagos alimenta los recibos y los extractos.

## Una importación CSV del personal informa errores

- Utilice **Descargar plantilla de Excel** en la tabla Empleados para que los nombres de las columnas coincidan exactamente.
- **Se requieren Nombre, Apellido, Correo Electrónico, Número de Teléfono, DNI y Designación**.
- La identificación nacional es la clave coincidente en la reimportación: una identificación nacional idéntica actualiza al empleado existente en lugar de crear un duplicado. Cambie el correo electrónico para actualizarlo.
- El personal contratado debe tener una **Fecha de finalización del contrato**.
- Corrija las filas rechazadas y cárguelas solo; consulte [Importar personal desde una hoja de cálculo](/docs/hr/importing-staff-from-spreadsheet).
