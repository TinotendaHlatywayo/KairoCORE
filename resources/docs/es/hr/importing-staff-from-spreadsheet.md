---
title: Importar personal desde una hoja de cálculo
summary: Descargue la plantilla de empleado, complétela y cárguela; volver a cargarla con las mismas identificaciones nacionales actualiza al personal existente en lugar de duplicarlo.
category: hr
slug: importing-staff-from-spreadsheet
order: 2
published: true
updated: 2026-10-27
---

# Importar personal desde una hoja de cálculo

El importador crea registros de personal de forma masiva, con los mismos campos que el asistente **Nuevo empleado**. También es seguro ejecutarlo más de una vez: el personal coincide con **ID nacional**, por lo que una nueva carga actualiza a los empleados existentes en lugar de crear duplicados.

## 1. Descarga la plantilla

1. Abra **Recursos humanos y nómina → Directorio de personal y recursos humanos → Empleados**.
2. Haga clic en **Importar empleados (Excel/CSV)**.
3. En el asistente, haga clic en **Descargar plantilla de Excel**.

La plantilla llega con los encabezados de columna correctos y cinco filas de ejemplo que muestran cuán variados deben verse los datos. Elimina los ejemplos antes de ingresar tu personal real, o guárdalos y corrige los valores.

## 2. Rellénelo

Sólo estas seis columnas deben estar presentes con valores:

| Columna requerida | Notas |
| --- | --- |
| Nombre | |
| Apellido | |
| Correo electrónico | Debe ser único entre el personal y las cuentas del portal |
| Número de teléfono | |
| DNI | La clave coincidente: nunca la cambies por una persona existente |
| Designación | Título del trabajo, p. "Profesor de Matemáticas" |

Todo lo demás es opcional y tiene un valor predeterminado sensato:

| Columna | Predeterminado cuando está en blanco |
| --- | --- |
| Género | Mujer |
| Fecha de nacimiento | 1990-01-01 |
| Estado civil | Soltero |
| Dirección física | No proporcionado |
| Nombre/teléfono del contacto de emergencia | No proporcionado |
| Departamento | En blanco (ver Departamentos a continuación) |
| Rol | Personal Docente |
| Tipo de empleo | Permanente |
| Fecha de incorporación | Hoy |
| Grado salarial | En blanco |
| Estado | Activo |

**La fecha de finalización del contrato es obligatoria cuando el tipo de empleo es contrato.** Las fechas deben ser fechas de Excel o `YYYY-MM-DD`.

### Listas y pares

Algunas columnas contienen más de un valor. Utilice dos puntos entre el nombre y el valor, y un punto y coma entre las entradas:

- `Individual Allowances` → `Teaching Allowance: 150; Transport: 80`
- `Spouse Details` → `Spouse: Rudo Maphosa; Occupation: Nurse`
- `Dependents` → `Child 1: Tadiwa (8); Child 2: Kuda (5)`
- `Next of Kin` → `Name: Rudo Maphosa; Phone: 0771234567; Relationship: Spouse`
- `Custom Fields` → `TSC Number: 123456; Pension Number: PN-88`

## 3. Súbelo

1. Haga clic en **Importar empleados (Excel/CSV)** y elija el archivo completo. Las columnas coinciden automáticamente a partir de los nombres de los encabezados; no hay una pantalla de mapeo.
2. Revise el resumen y confirme la importación.

## 4. ¿Qué sucede al volver a cargar?

Esta es la parte importante para actualizar al personal de forma masiva:

- **Mismo documento nacional de identidad, detalles modificados** → se actualiza el empleado existente. Sin registros nuevos, sin cuentas de portal duplicadas y sin un segundo correo electrónico de activación.
- **Misma identificación nacional con un nuevo correo electrónico** → el correo electrónico en el registro del empleado y su cuenta de inicio de sesión vinculada se actualizan.
- **Nueva identificación nacional** → se crea un nuevo empleado y se aprovisiona una cuenta del portal.
- **El correo electrónico pertenece a otra persona** → la fila se rechaza con un mensaje claro; arregla el correo electrónico y vuelve a subirlo.

Entonces, para actualizar una lista de personal, descargue la plantilla (o su última exportación), cambie solo los correos electrónicos u otros detalles que necesite y cargue el mismo archivo nuevamente.

## Departamentos y grados salariales

- **Departamento** coincide con un departamento existente por nombre. Si no existe, el importador pregunta si desea crearlo u omitir ese valor; al crearlo también se genera un código de departamento.
- **Grado salarial** coincide con un grado existente por nombre. Déjelo en blanco si la persona aún no tiene calificación.

## Cuando las filas fallan

La importación nunca se guarda a medias: las filas con problemas se enumeran en los resultados con el motivo exacto (por ejemplo, falta una fecha de finalización del contrato, un género no válido, un rol desconocido o una fecha incorrecta). Descargue las filas rechazadas como CSV, corríjalas e importe solo ese archivo. Las filas válidas en la misma carga aún se importan.

> Mantenga estable la identificación nacional para el personal existente: esa única columna es lo que hace que una recarga sea una actualización en lugar de un duplicado.
